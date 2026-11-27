<?php
/**
 * KIỂM THỬ TÍCH HỢP TOÀN BỘ QUY TRÌNH NGHIỆP VỤ (END-TO-END INTEGRATION TEST)
 * Chạy: php tests/test_end_to_end_integration.php
 *
 * Kiểm tra đầy đủ 5 phân hệ cốt lõi:
 * 1. Luồng Phòng Đào tạo (Quản lý đợt thực tập, draft -> active -> closed)
 * 2. Luồng Đề xuất và Xét duyệt đề tài (Giảng viên, Doanh nghiệp, Trưởng bộ môn, PĐT, lịch sử topic_approvals)
 * 3. Luồng Sinh viên Đăng ký nguyện vọng (Hạn đăng ký, tối đa 3 NV, kiểm tra đề tài approved)
 * 4. Luồng Phân công tự động và thủ công (TopicMatchingService, max_students, chống phân công trùng, concurrency)
 * 5. Luồng Kiểm soát dữ liệu giữa 6 vai trò (Bảo mật draft vs published, scoping cá nhân, đợt)
 *
 * TOÀN BỘ KIỂM THỬ CHẠY TRONG TRANSACTION VÀ ROLLBACK Ở CUỐI -> KHÔNG ẢNH HƯỞNG CSDL THẬT.
 */

$root = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($root) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Models\User;
use App\Models\Role;
use App\Models\InternshipPeriod;
use App\Models\Topic;
use App\Models\TopicApproval;
use App\Models\TopicWish;
use App\Models\TopicAssignment;
use App\Services\TopicMatchingService;

echo "==============================================================================\n";
echo "   KIỂM THỬ TÍCH HỢP TOÀN BỘ QUY TRÌNH NGHIỆP VỤ (END-TO-END WORKFLOW TEST)   \n";
echo "==============================================================================\n\n";

$pass = 0;
$fail = 0;

function t(string $title, bool $condition, string $detail = ''): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  [ PASS ] {$title}\n";
    } else {
        $fail++;
        echo "  [ FAIL ] {$title} - {$detail}\n";
    }
}

$pdo = Database::getConnection();
$pdo->beginTransaction();

try {
    $suffix = time() . '_' . mt_rand(1000, 9999);

    // =========================================================================
    // PHÂN HỆ 1: PHÒNG ĐÀO TẠO - TẠO VÀ QUẢN LÝ ĐỢT THỰC TẬP / ĐỒ ÁN
    // =========================================================================
    echo "--- 1. Kiểm tra luồng Phòng Đào tạo (Quản lý đợt) ---\n";

    // 1.1. Tạo đợt ở trạng thái DRAFT
    $periodCode = "E2E-HK1-{$suffix}";
    $periodId = InternshipPeriod::create([
        'code'        => $periodCode,
        'name'        => "Đợt Thực tập Tốt nghiệp E2E Test {$suffix}",
        'type'        => InternshipPeriod::TYPE_INTERNSHIP,
        'start_date'  => date('Y-m-d'),
        'end_date'    => date('Y-m-d', strtotime('+30 days')),
        'status'      => InternshipPeriod::STATUS_DRAFT,
        'description' => "Đợt kiểm thử tích hợp toàn quy trình",
    ]);

    t("PĐT tạo đợt thực tập mới thành công", $periodId > 0);
    $pDraft = InternshipPeriod::findById($periodId);
    t("Đợt mới tạo có đúng trạng thái DRAFT", $pDraft['status'] === InternshipPeriod::STATUS_DRAFT);
    t("Đợt DRAFT chưa mở tiếp nhận đăng ký nguyện vọng", !TopicWish::isPeriodOpenForRegistration($periodId));

    // 1.2. Kích hoạt đợt sang ACTIVE
    $updatedActive = InternshipPeriod::updateStatus($periodId, InternshipPeriod::STATUS_ACTIVE);
    t("PĐT kích hoạt đợt sang trạng thái ACTIVE", $updatedActive);
    $pActive = InternshipPeriod::findById($periodId);
    t("Đợt ACTIVE trong thời hạn hợp lệ cho phép đăng ký nguyện vọng", TopicWish::isPeriodOpenForRegistration($periodId));

    // 1.3. Tạo đợt thứ hai độc lập để kiểm tra phân tách dữ liệu đợt
    $periodCode2 = "E2E-HK2-{$suffix}";
    $periodId2 = InternshipPeriod::create([
        'code'        => $periodCode2,
        'name'        => "Đợt Đồ án Tốt nghiệp E2E Đợt 2 {$suffix}",
        'type'        => InternshipPeriod::TYPE_GRADUATION_PROJECT,
        'start_date'  => date('Y-m-d', strtotime('+60 days')),
        'end_date'    => date('Y-m-d', strtotime('+90 days')),
        'status'      => InternshipPeriod::STATUS_DRAFT,
        'description' => "Đợt đồ án thứ hai",
    ]);
    t("Tạo đợt thứ hai độc lập thành công", $periodId2 > 0 && $periodId2 !== $periodId);


    // =========================================================================
    // PHÂN HỆ 2: ĐỀ XUẤT VÀ XÉT DUYỆT ĐỀ TÀI (GIẢNG VIÊN, DOANH NGHIỆP, TBM, PĐT)
    // =========================================================================
    echo "\n--- 2. Kiểm tra luồng Đề xuất và Xét duyệt đề tài ---\n";

    // Khởi tạo tài khoản kiểm thử
    $roleLecturer = Role::findByName('lecturer');
    $roleEnterprise = Role::findByName('enterprise');
    $roleDeptHead = Role::findByName('department_head');
    $roleTraining = Role::findByName('training_dept');
    $roleStudent = Role::findByName('student');

    $gv1Id = User::createWithRoles(['username' => "e2e_gv1_{$suffix}", 'email' => "gv1_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'ThS. Nguyễn Văn GV1'], [(int)$roleLecturer['id']]);
    $dn1Id = User::createWithRoles(['username' => "e2e_dn1_{$suffix}", 'email' => "dn1_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Công ty Phần mềm F-Test'], [(int)$roleEnterprise['id']]);
    $tbmId = User::createWithRoles(['username' => "e2e_tbm_{$suffix}", 'email' => "tbm_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'TS. Trưởng Bộ Môn CNPM'], [(int)$roleDeptHead['id'], (int)$roleLecturer['id']]);
    $pdtId = User::createWithRoles(['username' => "e2e_pdt_{$suffix}", 'email' => "pdt_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Cán bộ Phòng Đào tạo'], [(int)$roleTraining['id']]);

    // 2.1. Giảng viên đề xuất đề tài 1
    $topic1Id = Topic::create([
        'code'         => "DT1-{$suffix}",
        'name'         => "Xây dựng hệ thống Microservices bằng Go và Docker",
        'description'  => "Đề tài thực tập backend",
        'type'         => Topic::TYPE_INTERNSHIP,
        'period_id'    => $periodId,
        'proposer_id'  => $gv1Id,
        'max_students' => 2,
        'status'       => Topic::STATUS_DRAFT,
    ]);
    t("Giảng viên tạo đề tài 1 (chỉ tiêu 2 SV) thành công", $topic1Id > 0);
    $t1 = Topic::findById($topic1Id);
    t("Đề tài mới tạo mặc định ở trạng thái DRAFT", $t1['status'] === Topic::STATUS_DRAFT);

    // 2.2. Doanh nghiệp đề xuất đề tài 2
    $topic2Id = Topic::create([
        'code'         => "DT2-{$suffix}",
        'name'         => "Phát triển ứng dụng di động Flutter kết nối Firebase",
        'description'  => "Đề tài thực tập mobile tại doanh nghiệp",
        'type'         => Topic::TYPE_INTERNSHIP,
        'period_id'    => $periodId,
        'proposer_id'  => $dn1Id,
        'max_students' => 1,
        'status'       => Topic::STATUS_DRAFT,
    ]);
    t("Doanh nghiệp tạo đề tài 2 (chỉ tiêu 1 SV) thành công", $topic2Id > 0);

    // 2.3. Gửi duyệt đề tài (draft -> pending)
    TopicApproval::submit($topic1Id);
    $t1Pending = Topic::findById($topic1Id);
    t("Giảng viên gửi duyệt Đề tài 1: Trạng thái chuyển thành PENDING", $t1Pending['status'] === Topic::STATUS_PENDING);

    TopicApproval::submit($topic2Id);
    $t2Pending = Topic::findById($topic2Id);
    t("Doanh nghiệp gửi duyệt Đề tài 2: Trạng thái chuyển thành PENDING", $t2Pending['status'] === Topic::STATUS_PENDING);

    // 2.4. Trưởng bộ môn xét duyệt Đề tài 1
    TopicApproval::review($topic1Id, $tbmId, TopicApproval::DECISION_APPROVED, "Đề tài đạt chuẩn khối lượng chuyên môn");
    $t1Approved = Topic::findById($topic1Id);
    t("Trưởng bộ môn phê duyệt Đề tài 1: Trạng thái chuyển thành APPROVED", $t1Approved['status'] === Topic::STATUS_APPROVED);

    // Kiểm tra lịch sử xét duyệt Đề tài 1
    $hist1 = TopicApproval::history($topic1Id);
    t("Lịch sử xét duyệt Đề tài 1 ghi nhận đúng người duyệt (TBM)", count($hist1) === 1 && (int)$hist1[0]['reviewer_id'] === $tbmId);
    t("Lịch sử xét duyệt ghi nhận đúng nhận xét", str_contains($hist1[0]['comments'], "đạt chuẩn khối lượng"));

    // 2.5. Phòng Đào tạo xét duyệt Đề tài 2
    TopicApproval::review($topic2Id, $pdtId, TopicApproval::DECISION_APPROVED, "Đơn vị thực tập uy tín, chấp thuận chỉ tiêu");
    $t2Approved = Topic::findById($topic2Id);
    t("Phòng Đào tạo phê duyệt Đề tài 2: Trạng thái chuyển thành APPROVED", $t2Approved['status'] === Topic::STATUS_APPROVED);

    // 2.6. Thử duyệt lại đề tài đã duyệt -> Phải bị chặn ngoại lệ
    $reReviewBlocked = false;
    try {
        TopicApproval::review($topic1Id, $tbmId, TopicApproval::DECISION_APPROVED, "Duyệt lần 2");
    } catch (InvalidArgumentException $e) {
        $reReviewBlocked = true;
    }
    t("Chống duyệt lặp: Không thể xét duyệt đề tài đã ở trạng thái APPROVED", $reReviewBlocked);


    // =========================================================================
    // PHÂN HỆ 3: SINH VIÊN ĐĂNG KÝ NGUYỆN VỌNG
    // =========================================================================
    echo "\n--- 3. Kiểm tra luồng Sinh viên đăng ký nguyện vọng ---\n";

    // Khởi tạo 3 sinh viên
    $sv1Id = User::createWithRoles(['username' => "e2e_sv1_{$suffix}", 'email' => "sv1_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Nguyễn Thị Sinh Viên 1'], [(int)$roleStudent['id']]);
    $sv2Id = User::createWithRoles(['username' => "e2e_sv2_{$suffix}", 'email' => "sv2_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Trần Văn Sinh Viên 2'], [(int)$roleStudent['id']]);
    $sv3Id = User::createWithRoles(['username' => "e2e_sv3_{$suffix}", 'email' => "sv3_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Lê Hoàng Sinh Viên 3'], [(int)$roleStudent['id']]);

    // 3.1. Danh sách đề tài mở cho sinh viên đăng ký trong đợt
    $openTopics = TopicWish::getApprovedTopicsForPeriod($periodId);
    t("Sinh viên chỉ thấy đúng các đề tài đã APPROVED trong đợt", count($openTopics) === 2);

    // 3.2. SV1 đăng ký 2 nguyện vọng: NV1 -> Đề tài 1, NV2 -> Đề tài 2
    $wishesSv1 = [
        ['topic_id' => $topic1Id, 'priority' => 1],
        ['topic_id' => $topic2Id, 'priority' => 2],
    ];
    $savedWishesSv1 = TopicWish::saveWishes($sv1Id, $periodId, $wishesSv1);
    t("SV1 lưu thành công 2 nguyện vọng hợp lệ", count($savedWishesSv1) === 2);

    // 3.3. SV2 đăng ký 1 nguyện vọng: NV1 -> Đề tài 1
    $wishesSv2 = [
        ['topic_id' => $topic1Id, 'priority' => 1],
    ];
    $savedWishesSv2 = TopicWish::saveWishes($sv2Id, $periodId, $wishesSv2);
    t("SV2 lưu thành công NV1 vào Đề tài 1", count($savedWishesSv2) === 1);

    // 3.4. SV3 đăng ký 2 nguyện vọng: NV1 -> Đề tài 2, NV2 -> Đề tài 1
    $wishesSv3 = [
        ['topic_id' => $topic2Id, 'priority' => 1],
        ['topic_id' => $topic1Id, 'priority' => 2],
    ];
    $savedWishesSv3 = TopicWish::saveWishes($sv3Id, $periodId, $wishesSv3);
    t("SV3 lưu thành công NV1 vào Đề tài 2, NV2 vào Đề tài 1", count($savedWishesSv3) === 2);

    // 3.5. Kiểm tra thay đổi thứ tự nguyện vọng của SV1
    $updatedWishesSv1 = [
        ['topic_id' => $topic2Id, 'priority' => 1], // Đổi NV1 thành Đề tài 2
        ['topic_id' => $topic1Id, 'priority' => 2], // Đổi NV2 thành Đề tài 1
    ];
    TopicWish::saveWishes($sv1Id, $periodId, $updatedWishesSv1);
    $checkWishes = TopicWish::getByStudentAndPeriod($sv1Id, $periodId);
    t("SV1 cập nhật thay đổi thứ tự nguyện vọng thành công (NV1 = Đề tài 2)", (int)$checkWishes[0]['topic_id'] === $topic2Id);

    // Khôi phục lại đúng thứ tự ban đầu cho SV1 để kiểm tra đối sánh chính xác
    TopicWish::saveWishes($sv1Id, $periodId, $wishesSv1);

    // 3.6. Chặn đăng ký đề tài của đợt khác
    $wrongPeriodBlocked = false;
    try {
        TopicWish::saveWishes($sv1Id, $periodId2, [
            ['topic_id' => $topic1Id, 'priority' => 1]
        ]);
    } catch (InvalidArgumentException $e) {
        $wrongPeriodBlocked = true;
    }
    t("Chặn đăng ký: Từ chối khi đăng ký vào đợt không mở hoặc đề tài không khớp đợt", $wrongPeriodBlocked);


    // =========================================================================
    // PHÂN HỆ 4: THUẬT TOÁN ĐỐI SÁNH VÀ PHÂN CÔNG ĐỀ TÀI
    // =========================================================================
    echo "\n--- 4. Kiểm tra luồng Phân công đề tài ---\n";

    // 4.1. Thống kê trước phân công
    $statsBefore = TopicAssignment::getStatistics($periodId);
    t("Thống kê: Đếm đúng 3 sinh viên duy nhất nộp nguyện vọng", (int)$statsBefore['total_registered'] === 3);
    t("Thống kê: Trước phân công số sinh viên chưa phân công = 3", (int)$statsBefore['unassigned_count'] === 3);

    // 4.2. Chạy thuật toán đối sánh tự động
    $matchingService = new TopicMatchingService();
    $matchResult = $matchingService->matchPeriod($periodId, [
        'max_per_lecturer' => 5,
        'dry_run'          => false,
        'assigned_by'      => $pdtId,
    ]);

    t("Thuật toán phân công tự động chạy thành công", $matchResult['total_assigned'] === 3);
    t("Toàn bộ 3 sinh viên đều được phân công, 0 sinh viên bị unassigned", $matchResult['total_unassigned'] === 0);

    // 4.3. Kiểm tra chi tiết kết quả phân công:
    // Đề tài 1 (max=2): SV1 (NV1) và SV2 (NV1)
    // Đề tài 2 (max=1): SV3 (NV1)
    $assignments = TopicAssignment::getByPeriod($periodId);
    t("Tổng số bản ghi phân công trong đợt = 3", count($assignments) === 3);

    $asgByStudent = [];
    foreach ($assignments as $a) {
        $asgByStudent[(int)$a['student_id']] = $a;
        t("Bản ghi phân công tự động có trạng thái 'draft'", $a['status'] === TopicAssignment::STATUS_DRAFT);
    }

    t("SV1 được phân công đúng Đề tài 1 (NV1)", (int)$asgByStudent[$sv1Id]['topic_id'] === $topic1Id && (int)$asgByStudent[$sv1Id]['matched_wish_priority'] === 1);
    t("SV2 được phân công đúng Đề tài 1 (NV1)", (int)$asgByStudent[$sv2Id]['topic_id'] === $topic1Id && (int)$asgByStudent[$sv2Id]['matched_wish_priority'] === 1);
    t("SV3 được phân công đúng Đề tài 2 (NV1)", (int)$asgByStudent[$sv3Id]['topic_id'] === $topic2Id && (int)$asgByStudent[$sv3Id]['matched_wish_priority'] === 1);

    // 4.4. Kiểm tra giới hạn chỉ tiêu (max_students = 1 của Đề tài 2)
    // Thử phân công thủ công thêm 1 sinh viên thứ 4 vào Đề tài 2 -> Phải bị từ chối
    $sv4Id = User::createWithRoles(['username' => "e2e_sv4_{$suffix}", 'email' => "sv4_{$suffix}@test.vn", 'password' => 'Pass@123', 'full_name' => 'Sinh Viên 4'], [(int)$roleStudent['id']]);
    $overCapacityBlocked = false;
    try {
        TopicAssignment::assignManual($periodId, $sv4Id, $topic2Id, $dn1Id, $pdtId);
    } catch (RuntimeException $e) {
        $overCapacityBlocked = str_contains($e->getMessage(), 'đủ chỉ tiêu');
    }
    t("Chống vượt chỉ tiêu: Từ chối phân công khi đề tài đã đủ sinh viên", $overCapacityBlocked);

    // 4.5. Kiểm tra ràng buộc duy nhất (UNIQUE uk_period_student)
    $uniqueEnforced = false;
    try {
        $stmtDup = $pdo->prepare("INSERT INTO topic_assignments (period_id, student_id, topic_id, lecturer_id, status, assigned_at) VALUES (?, ?, ?, ?, 'draft', NOW())");
        $stmtDup->execute([$periodId, $sv1Id, $topic2Id, $dn1Id]);
    } catch (\PDOException $e) {
        $uniqueEnforced = str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() == 23000;
    }
    t("Ràng buộc CSDL: UNIQUE uk_period_student chặn 1 sinh viên có 2 phân công trong đợt", $uniqueEnforced);


    // =========================================================================
    // PHÂN HỆ 5: BẢO MẬT VÀ PHÂN QUYỀN DỮ LIỆU GIỮA CÁC VAI TRÒ
    // =========================================================================
    echo "\n--- 5. Kiểm tra kết quả và phân quyền giữa 6 vai trò ---\n";

    // 5.1. Khi kết quả còn là DRAFT: Sinh viên KHÔNG được xem
    $sv1DraftView = TopicAssignment::getByStudent($sv1Id, $periodId, true);
    t("Bảo mật phân công: Sinh viên tra cứu khi còn DRAFT nhận kết quả NULL", $sv1DraftView === null);

    // 5.2. Giảng viên xem danh sách sinh viên hướng dẫn của mình
    $gv1Students = TopicAssignment::getByLecturer($gv1Id, $periodId);
    t("Giảng viên GV1 xem đúng 2 sinh viên được phân công vào đề tài của mình", count($gv1Students) === 2);
    $gv1StudentIds = array_map(fn($s) => (int)$s['student_id'], $gv1Students);
    t("Danh sách sinh viên của GV1 gồm đúng SV1 và SV2", in_array($sv1Id, $gv1StudentIds, true) && in_array($sv2Id, $gv1StudentIds, true));

    // 5.3. Doanh nghiệp chỉ xem đề tài và sinh viên của doanh nghiệp mình
    $dnTopics = Topic::all(['proposer_id' => $dn1Id, 'period_id' => $periodId]);
    t("Doanh nghiệp DN1 chỉ thấy đề tài của chính mình", count($dnTopics) === 1 && (int)$dnTopics[0]['id'] === $topic2Id);
    $dnStudents = TopicAssignment::getByLecturer($dn1Id, $periodId);
    t("Doanh nghiệp DN1 chỉ thấy sinh viên (SV3) hướng dẫn tại doanh nghiệp", count($dnStudents) === 1 && (int)$dnStudents[0]['student_id'] === $sv3Id);

    // 5.4. Trưởng bộ môn kiểm tra đề tài còn chỗ trong đợt
    $approvedWithSlots = TopicAssignment::getApprovedTopicsWithSlots($periodId);
    t("Trưởng bộ môn tra cứu danh sách đề tài còn chỗ trong đợt thành công", count($approvedWithSlots) === 2);
    foreach ($approvedWithSlots as $slotTopic) {
        if ((int)$slotTopic['topic_id'] === $topic1Id) {
            t("Đề tài 1 hiển thị remaining_slots = 0 (2/2 chỗ đã phân công)", (int)$slotTopic['remaining_slots'] === 0);
        }
        if ((int)$slotTopic['topic_id'] === $topic2Id) {
            t("Đề tài 2 hiển thị remaining_slots = 0 (1/1 chỗ đã phân công)", (int)$slotTopic['remaining_slots'] === 0);
        }
    }

    // 5.5. Phòng Đào tạo công bố kết quả phân công (publish)
    $publishSuccess = TopicAssignment::publishPeriodAssignments($periodId);
    t("Phòng Đào tạo công bố kết quả phân công thành công", $publishSuccess);
    t("Xác nhận đợt đã có kết quả công bố chính thức", TopicAssignment::hasPublishedAssignments($periodId));

    // 5.6. Sau khi công bố: Sinh viên xem được kết quả chính thức của mình
    $sv1PublishedView = TopicAssignment::getByStudent($sv1Id, $periodId, true);
    t("Sau khi công bố: SV1 xem được thông tin đề tài chính thức", $sv1PublishedView !== null && (int)$sv1PublishedView['topic_id'] === $topic1Id);
    t("SV1 xem đúng giảng viên hướng dẫn (GV1)", (int)$sv1PublishedView['lecturer_id'] === $gv1Id);

    $sv3PublishedView = TopicAssignment::getByStudent($sv3Id, $periodId, true);
    t("Sau khi công bố: SV3 xem được thông tin đề tài thực tập doanh nghiệp", $sv3PublishedView !== null && (int)$sv3PublishedView['topic_id'] === $topic2Id);

    // 5.7. Bảo vệ CSDL: Khi đã công bố, thuật toán tự động từ chối chạy đè
    $reMatchBlocked = false;
    try {
        $matchingService->matchPeriod($periodId, ['dry_run' => false]);
    } catch (\RuntimeException $e) {
        $reMatchBlocked = str_contains($e->getMessage(), 'kết quả phân công chính thức được công bố');
    }
    t("Bảo vệ tính toàn vẹn: Không thể chạy đè phân công khi đợt đã công bố kết quả", $reMatchBlocked);

} finally {
    // Luôn luôn Rollback để giữ CSDL sạch sẽ tuyệt đối
    $pdo->rollBack();
    echo "\n(Toàn bộ dữ liệu kiểm thử E2E đã được ROLLBACK an toàn tuyệt đối, CSDL không bị thay đổi)\n";
}

echo "------------------------------------------------------------------------------\n";
echo "KẾT QUẢ KIỂM THỬ TÍCH HỢP QUY TRÌNH (E2E): {$pass} PASS, {$fail} FAIL.\n";
echo "==============================================================================\n";
exit($fail > 0 ? 1 : 0);
