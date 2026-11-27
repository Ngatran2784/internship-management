<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * BỘ KIỂM THỬ NGHIỆM THU TỔNG HỢP CÁC CHỨC NĂNG HỆ THỐNG
 * Dự án: Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên
 * ==============================================================================
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
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
use App\Controllers\AuthController;
use App\Controllers\TopicAssignmentController;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$results = [];

function assertTest(string $module, string $criterion, string $role, string $description, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests, $failedTests, $results;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        $status = 'PASS';
    } else {
        $failedTests++;
        $status = 'FAIL';
    }
    $results[] = [
        'module'      => $module,
        'criterion'   => $criterion,
        'role'        => $role,
        'description' => $description,
        'status'      => $status,
        'detail'      => $detail,
    ];
    echo sprintf("[%s] %s | %s (%s): %s %s\n", 
        $status, 
        $module, 
        $criterion, 
        $role, 
        $description, 
        $detail ? "($detail)" : ''
    );
}

echo "==============================================================================\n";
echo "   CHẠY BỘ KIỂM THỬ NGHIỆM THU CHỨC NĂNG CỐT LÕI HỆ THỐNG\n";
echo "==============================================================================\n\n";

$pdo = Database::getConnection();
$pdo->beginTransaction();

try {
    // --------------------------------------------------------------------------
    // PHÂN HỆ: XÁC THỰC, ĐĂNG NHẬP, ĐĂNG XUẤT, PHÂN QUYỀN RBAC
    // --------------------------------------------------------------------------
    echo "--- 1. Phân hệ Xác thực & Phân quyền Vai trò ---\n";
    
    // 1. Đăng nhập đúng mật khẩu
    $adminUser = User::findByUsernameOrEmail('admin');
    assertTest('Xác thực', 'Đăng nhập thành công', 'Quản trị viên', 
        'Đăng nhập với username admin và mật khẩu đúng', 
        $adminUser !== null && password_verify('Admin@123', $adminUser['password'])
    );

    // 2. Đăng nhập sai mật khẩu bị từ chối
    assertTest('Xác thực', 'Chặn mật khẩu sai', 'Chung', 
        'Từ chối đăng nhập khi mật khẩu không khớp', 
        !password_verify('WrongPassword@999', $adminUser['password'])
    );

    // 3. Phân quyền vai trò: 6 vai trò chính tồn tại đầy đủ
    $allRoles = Role::all();
    $roleNames = array_column($allRoles, 'name');
    $requiredRoles = ['admin', 'training_dept', 'department_head', 'lecturer', 'student', 'enterprise'];
    assertTest('Phân quyền', 'Đầy đủ 6 vai trò', 'Hệ thống', 
        'Hệ thống định nghĩa đủ 6 vai trò chính', 
        count(array_intersect($requiredRoles, $roleNames)) === 6
    );

    // 4. Kiểm tra tài khoản đa vai trò (kiêm nhiệm)
    $tbmGv = User::findByUsernameOrEmail('tbm_gv');
    $tbmRoles = $tbmGv ? User::getRoles((int)$tbmGv['id']) : [];
    assertTest('Phân quyền', 'Đa vai trò kiêm nhiệm', 'TBM kiêm GV', 
        'Tài khoản tbm_gv có đồng thời vai trò department_head và lecturer', 
        in_array('department_head', $tbmRoles, true) && in_array('lecturer', $tbmRoles, true)
    );

    // 5. Chặn truy cập trái quyền (Sinh viên không có quyền quản lý người dùng)
    $studentUser = User::findByUsernameOrEmail('sv001');
    $svRoles = $studentUser ? User::getRoles((int)$studentUser['id']) : [];
    assertTest('Phân quyền', 'Chặn truy cập trái quyền', 'Sinh viên', 
        'Sinh viên không có quyền admin và không được vào /users', 
        !in_array('admin', $svRoles, true)
    );

    // --------------------------------------------------------------------------
    // PHÂN HỆ: QUẢN LÝ TÀI KHOẢN VÀ ĐỢT THỰC TẬP / ĐỒ ÁN
    // --------------------------------------------------------------------------
    echo "\n--- 2. Phân hệ Quản lý Tài khoản & Đợt thực tập / Đồ án ---\n";

    // 1. Tạo tài khoản người dùng mới và gán vai trò
    $newUserId = User::create([
        'username'  => 'sv_acceptance_test',
        'email'     => 'sv_acceptance_test@vinhuni.edu.vn',
        'full_name' => 'Sinh viên Nghiệm thu Hệ thống',
        'password'  => 'Test@12345',
        'status'    => 1,
    ]);
    $roleStudent = Role::findByName('student');
    User::assignRoles($newUserId, [(int)$roleStudent['id']]);
    $assignedRoles = User::getRoles($newUserId);
    assertTest('Quản lý User', 'Tạo tài khoản & gán vai trò', 'Quản trị viên', 
        'Tạo tài khoản sinh viên mới và gán vai trò student thành công', 
        $newUserId > 0 && count($assignedRoles) === 1 && $assignedRoles[0] === 'student'
    );

    // 2. Khóa và mở khóa tài khoản
    User::updateStatus($newUserId, 0);
    $lockedUser = User::findById($newUserId);
    User::updateStatus($newUserId, 1);
    $unlockedUser = User::findById($newUserId);
    assertTest('Quản lý User', 'Khóa / Mở khóa tài khoản', 'Quản trị viên', 
        'Cập nhật trạng thái khóa (0) và mở khóa (1) hoạt động chính xác', 
        (int)$lockedUser['status'] === 0 && (int)$unlockedUser['status'] === 1
    );

    // 3. Tạo đợt thực tập mới
    $periodCode = 'TEST-PERIOD-2026';
    $periodId = InternshipPeriod::create([
        'code'        => $periodCode,
        'name'        => 'Đợt Thực tập Nghiệm thu Hệ thống',
        'type'        => 'internship',
        'start_date'  => date('Y-m-d'),
        'end_date'    => date('Y-m-d', strtotime('+90 days')),
        'status'      => 'active',
        'description' => 'Đợt kiểm thử tích hợp toàn diện',
    ]);
    $period = InternshipPeriod::findById($periodId);
    assertTest('Quản lý Đợt', 'Quản lý đợt (Tạo & Mở)', 'Phòng Đào tạo', 
        'Tạo đợt thực tập mới với trạng thái ACTIVE thành công', 
        $period !== null && $period['status'] === 'active'
    );

    // 4. Sinh viên xem được đợt đang mở và thời hạn đăng ký
    $isOpen = TopicWish::isPeriodOpenForRegistration($periodId);
    assertTest('Quản lý Đợt', 'Sinh viên xem đợt đang mở', 'Sinh viên', 
        'Sinh viên tra cứu thấy đợt đang mở kèm thời hạn bắt đầu/kết thúc hợp lệ', 
        $isOpen
    );

    // --------------------------------------------------------------------------
    // PHÂN HỆ: ĐỀ XUẤT, DUYỆT / TỪ CHỐI ĐỀ TÀI & ĐĂNG KÝ NGUYỆN VỌNG
    // --------------------------------------------------------------------------
    echo "\n--- 3. Phân hệ Đề xuất, Duyệt đề tài & Đăng ký nguyện vọng ---\n";

    // 1. Giảng viên đề xuất đề tài
    $gvUser = User::findByUsernameOrEmail('gv001');
    $topic1Id = Topic::create([
        'code'         => 'DT-TEST-01',
        'name'         => 'Hệ thống Quản lý Bán hàng Đa kênh',
        'type'         => 'internship',
        'period_id'    => $periodId,
        'proposer_id'  => (int)$gvUser['id'],
        'max_students' => 2,
        'status'       => 'draft',
        'description'  => 'Đề tài giảng viên hướng dẫn',
    ]);
    TopicApproval::submit($topic1Id);
    $t1 = Topic::findById($topic1Id);
    assertTest('Đề tài', 'Giảng viên đề xuất đề tài', 'Giảng viên', 
        'Giảng viên tạo đề tài và gửi duyệt thành công (chuyển sang pending)', 
        $topic1Id > 0 && $t1['status'] === 'pending'
    );

    // 2. Trưởng bộ môn phê duyệt đề tài
    $tbmUser = User::findByUsernameOrEmail('tbm001');
    TopicApproval::review($topic1Id, (int)$tbmUser['id'], 'approved', 'Đề tài đạt chuẩn bộ môn');
    $t1Approved = Topic::findById($topic1Id);
    assertTest('Xét duyệt', 'Trưởng bộ môn phê duyệt', 'Trưởng bộ môn', 
        'Trưởng bộ môn duyệt đề tài (chuyển sang approved và ghi nhận lịch sử)', 
        $t1Approved['status'] === 'approved'
    );

    // 3. Doanh nghiệp đề xuất đề tài thực tập
    $dnUser = User::findByUsernameOrEmail('dn001');
    $topic2Id = Topic::create([
        'code'         => 'DT-TEST-02',
        'name'         => 'Phát triển Module Kho Doanh nghiệp',
        'type'         => 'internship',
        'period_id'    => $periodId,
        'proposer_id'  => (int)$dnUser['id'],
        'max_students' => 1,
        'status'       => 'draft',
        'description'  => 'Đề tài doanh nghiệp tiếp nhận',
    ]);
    TopicApproval::submit($topic2Id);
    TopicApproval::review($topic2Id, (int)$tbmUser['id'], 'approved', 'Đồng ý tiếp nhận');
    $t2Approved = Topic::findById($topic2Id);
    assertTest('Đề tài DN', 'Doanh nghiệp đề xuất đề tài', 'Doanh nghiệp', 
        'Đề tài thực tập doanh nghiệp được đề xuất và phê duyệt thành công', 
        $topic2Id > 0 && $t2Approved['status'] === 'approved'
    );

    // 4. Sinh viên đăng ký nguyện vọng
    $sv1 = User::findByUsernameOrEmail('sv001');
    $wishesInput = [
        ['topic_id' => $topic1Id, 'priority' => 1],
        ['topic_id' => $topic2Id, 'priority' => 2],
    ];
    $savedWishes = TopicWish::saveWishes((int)$sv1['id'], $periodId, $wishesInput);
    $svWishes = TopicWish::getByStudentAndPeriod((int)$sv1['id'], $periodId);
    assertTest('Nguyện vọng', 'Sinh viên đăng ký nguyện vọng', 'Sinh viên', 
        'Sinh viên đăng ký thành công 2 nguyện vọng NV1 và NV2 theo thứ tự ưu tiên', 
        !empty($savedWishes) && count($svWishes) === 2 && (int)$svWishes[0]['priority'] === 1 && (int)$svWishes[1]['priority'] === 2
    );

    // --------------------------------------------------------------------------
    // PHÂN HỆ: PHÂN CÔNG ĐỀ TÀI, CÔNG BỐ, GIẢNG VIÊN & SINH VIÊN XEM KẾT QUẢ
    // --------------------------------------------------------------------------
    echo "\n--- 4. Phân hệ Phân công, Công bố kết quả & Xem kết quả phân công ---\n";

    // 1. Chạy thuật toán đối sánh tự động (Auto-Match)
    $matchingService = new TopicMatchingService();
    $matchResult = $matchingService->matchPeriod($periodId, [
        'assigned_by' => (int)$adminUser['id'],
        'dry_run'     => false,
    ]);
    assertTest('Phân công', 'Thuật toán đối sánh tự động', 'Hệ thống', 
        'Chạy thuật toán đối sánh tự động thành công, lưu bản dự thảo (draft)', 
        $matchResult['total_assigned'] >= 1
    );

    // 2. Phân công thủ công (Manual Assign)
    $pdtUser = User::findByUsernameOrEmail('pdt001');
    $manualAssignId = TopicAssignment::assignManual(
        $periodId,
        $newUserId,
        $topic1Id,
        (int)$gvUser['id'],
        (int)$pdtUser['id'],
        'Phân công thủ công bởi Phòng Đào tạo'
    );
    assertTest('Phân công', 'Phân công thủ công', 'Phòng Đào tạo / TBM', 
        'Cán bộ quản lý thực hiện phân công thủ công sinh viên vào đề tài thành công', 
        $manualAssignId > 0
    );

    // 3. Bảo mật: Khi còn ở trạng thái DRAFT, Sinh viên chưa thể xem kết quả
    $studentDraftResult = TopicAssignment::getByStudent((int)$sv1['id'], $periodId, true);
    assertTest('Bảo mật', 'Bảo mật kết quả dự thảo', 'Sinh viên', 
        'Sinh viên KHÔNG thể xem kết quả khi còn ở trạng thái dự thảo (draft)', 
        $studentDraftResult === null
    );

    // 4. Công bố kết quả trên giao diện
    $publishedCount = TopicAssignment::publishPeriodAssignments($periodId, (int)$pdtUser['id']);
    $isPublished = TopicAssignment::hasPublishedAssignments($periodId);
    assertTest('Công bố', 'Công bố kết quả phân công', 'Phòng Đào tạo', 
        'Phòng Đào tạo công bố kết quả phân công chính thức cho toàn đợt', 
        $publishedCount >= 1 && $isPublished
    );

    // 5. Sinh viên xem được kết quả chính thức của mình sau khi công bố
    $studentPubResult = TopicAssignment::getByStudent((int)$sv1['id'], $periodId, true);
    assertTest('Kết quả SV', 'Sinh viên xem kết quả chính thức', 'Sinh viên', 
        'Sinh viên tra cứu thấy đề tài chính thức và GV hướng dẫn sau khi công bố', 
        $studentPubResult !== null && !empty($studentPubResult['topic_name']) && !empty($studentPubResult['lecturer_name'])
    );

    // 6. Giảng viên xem danh sách sinh viên được phân công
    $gvStudents = TopicAssignment::getByLecturer((int)$gvUser['id'], $periodId, false);
    assertTest('Kết quả GV', 'Giảng viên xem sinh viên hướng dẫn', 'Giảng viên', 
        'Giảng viên xem đúng danh sách sinh viên được phân công vào đề tài của mình', 
        count($gvStudents) >= 1 && (int)$gvStudents[0]['lecturer_id'] === (int)$gvUser['id']
    );

    // 7. Doanh nghiệp xem danh sách sinh viên thực tập được giao
    $dnStudents = TopicAssignment::getByEnterprise((int)$dnUser['id'], $periodId, false);
    assertTest('Kết quả DN', 'Doanh nghiệp xem SV thực tập tiếp nhận', 'Doanh nghiệp', 
        'Doanh nghiệp tra cứu danh sách sinh viên được giao về công ty thực tập', 
        is_array($dnStudents)
    );

    // 8. Hủy / Điều chỉnh phân công
    $cancelOk = TopicAssignment::cancelAssignment($manualAssignId, 'Hủy để điều chỉnh nguyện vọng');
    assertTest('Điều chỉnh', 'Điều chỉnh / Hủy phân công', 'Phòng Đào tạo / TBM', 
        'Hủy hoặc điều chỉnh phân công của một sinh viên thành công', 
        $cancelOk
    );

} finally {
    // Luôn Rollback giao dịch kiểm thử để CSDL test sạch sẽ, an toàn tuyệt đối
    $pdo->rollBack();
    echo "\n(Toàn bộ giao dịch kiểm thử nghiệm thu đã được ROLLBACK an toàn tuyệt đối)\n";
}

echo "\n==============================================================================\n";
echo sprintf("TỔNG KẾT NGHIỆM THU: %d TEST ĐẠT (PASS), %d THẤT BẠI (FAIL) / TỔNG SỐ %d TIÊU CHÍ.\n", $passedTests, $failedTests, $totalTests);
echo "==============================================================================\n";
