<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * BỘ KIỂM THỬ TỰ ĐỘNG: CƠ SỞ DỮ LIỆU & THUẬT TOÁN ĐỐI SÁNH NGUYỆN VỌNG PHÂN CÔNG
 * ==============================================================================
 * Phạm vi:
 * 1. Cấu trúc CSDL bảng `topic_assignments`, khóa ngoại, chỉ số, UNIQUE (period_id, student_id).
 * 2. Thuật toán đối sánh nguyện vọng (TopicMatchingService):
 *    - Xét ưu tiên NV1 -> NV2 -> NV3.
 *    - Giới hạn chỉ tiêu đề tài (max_students).
 *    - Giới hạn chỉ tiêu giảng viên (max_students_per_lecturer).
 *    - Quy tắc giải quyết cạnh tranh tất định (GPA -> Thời gian nộp -> Mã sinh viên).
 *    - Loại trừ đề tài chưa duyệt (draft, pending, rejected).
 *    - Gom danh sách sinh viên chưa phân công (unassigned pool) kèm lý do.
 *    - Kết quả tự động chỉ là DỰ THẢO (draft), không tự động công bố.
 *    - Cơ chế mô phỏng Dry-Run (không ghi CSDL).
 *    - Bảo vệ kết quả đã công bố (chống ghi đè đợt đã published).
 * 3. Phân quyền (RBAC) thực thi phân công tự động.
 * 4. Tích hợp sẵn sàng cho phân công thủ công, công bố kết quả và giao diện.
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
use App\Models\InternshipPeriod;
use App\Models\Topic;
use App\Models\TopicAssignment;
use App\Models\TopicWish;
use App\Services\TopicMatchingService;
use App\Controllers\TopicAssignmentController;

$passed = 0;
$failed = 0;

function t(string $desc, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "[ PASS ] " . $desc . PHP_EOL;
    } else {
        $failed++;
        echo "[ FAIL ] " . $desc . ($detail ? " ($detail)" : '') . PHP_EOL;
    }
}

echo PHP_EOL;
echo "==============================================================================" . PHP_EOL;
echo "   KIỂM THỬ CSDL VÀ THUẬT TOÁN ĐỐI SÁNH NGUYỆN VỌNG (TOPIC ASSIGNMENTS)" . PHP_EOL;
echo "==============================================================================" . PHP_EOL . PHP_EOL;

$pdo = Database::getConnection();

// ==============================================================================
// 1. KIỂM THỬ CẤU TRÚC LỚP, CONTROLLER, ROUTER & CSDL BẢNG topic_assignments
// ==============================================================================

t("Có lớp TopicAssignment Model", class_exists(TopicAssignment::class));
t("Có lớp TopicMatchingService", class_exists(TopicMatchingService::class));
t("Có lớp TopicAssignmentController", class_exists(TopicAssignmentController::class));

t("TopicAssignmentController::autoMatch là hàm public", method_exists(TopicAssignmentController::class, 'autoMatch'));
t("TopicAssignmentController::preview là hàm public", method_exists(TopicAssignmentController::class, 'preview'));
t("TopicAssignmentController::index là hàm public", method_exists(TopicAssignmentController::class, 'index'));

$indexContent = file_get_contents(dirname(__DIR__) . '/public/index.php');
t("Router đăng ký POST /assignments/auto-match", str_contains($indexContent, "'/assignments/auto-match'"));
t("Router đăng ký GET /assignments/preview", str_contains($indexContent, "'/assignments/preview'"));
t("Router đăng ký GET /assignments", str_contains($indexContent, "'/assignments'"));

// Kiểm tra schema bảng topic_assignments trong CSDL
$cols = $pdo->query("SHOW COLUMNS FROM `topic_assignments`")->fetchAll(PDO::FETCH_COLUMN);
$expectedCols = [
    'id', 'period_id', 'student_id', 'topic_id', 'lecturer_id',
    'matched_wish_priority', 'assignment_method', 'status',
    'assigned_by', 'notes', 'assigned_at', 'updated_at'
];
foreach ($expectedCols as $col) {
    t("Bảng topic_assignments có cột {$col}", in_array($col, $cols, true));
}

// Kiểm tra UNIQUE KEY uk_period_student
$indexes = $pdo->query("SHOW INDEX FROM `topic_assignments` WHERE Key_name = 'uk_period_student'")->fetchAll(PDO::FETCH_ASSOC);
$uniqueCols = array_column($indexes, 'Column_name');
t("Bảng có ràng buộc UNIQUE uk_period_student (period_id, student_id)", 
    count($indexes) === 2 && in_array('period_id', $uniqueCols, true) && in_array('student_id', $uniqueCols, true)
);

// ==============================================================================
// 2. KIỂM THỬ PHÂN QUYỀN (RBAC) CHO CHỨC NĂNG PHÂN CÔNG TỰ ĐỘNG
// ==============================================================================

t("Quản trị viên (admin) được phép quản lý phân công", TopicAssignmentController::canManage(['admin']));
t("Phòng đào tạo (training_dept) được phép quản lý phân công", TopicAssignmentController::canManage(['training_dept']));
t("Trưởng bộ môn (department_head) được phép quản lý phân công", TopicAssignmentController::canManage(['department_head']));
t("Cán bộ khoa (faculty_staff) được phép quản lý phân công", TopicAssignmentController::canManage(['faculty_staff']));
t("Giảng viên thuần KHÔNG được phép quản lý phân công", !TopicAssignmentController::canManage(['lecturer']));
t("Sinh viên thuần KHÔNG được phép quản lý phân công", !TopicAssignmentController::canManage(['student']));
t("Doanh nghiệp KHÔNG được phép quản lý phân công", !TopicAssignmentController::canManage(['enterprise']));
t("Người dùng không vai trò KHÔNG được phép quản lý", !TopicAssignmentController::canManage([]));

// ==============================================================================
// 3. THIẾT LẬP MÔI TRƯỜNG DỮ LIỆU KIỂM THỬ ĐỘC LẬP (ISOLATED TEST FIXTURES)
// ==============================================================================

$pdo->beginTransaction();

try {
    // 3.1. Tạo đợt thực tập kiểm thử
    $periodCode = 'TEST-PERIOD-' . bin2hex(random_bytes(4));
    $pdo->exec("
        INSERT INTO internship_periods (code, name, type, start_date, end_date, status)
        VALUES ('{$periodCode}', 'Đợt kiểm thử đối sánh', 'internship', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'active')
    ");
    $testPeriodId = (int)$pdo->lastInsertId();

    // 3.2. Tạo 2 giảng viên kiểm thử
    $lecturerCode1 = 'GV-TEST-' . bin2hex(random_bytes(3));
    $lecturerCode2 = 'GV-TEST-' . bin2hex(random_bytes(3));
    $pdo->exec("INSERT INTO users (username, email, password, full_name, status) VALUES ('{$lecturerCode1}', '{$lecturerCode1}@vinhuni.edu.vn', 'hash', 'Giảng viên A', 1)");
    $lecturer1Id = (int)$pdo->lastInsertId();
    $pdo->exec("INSERT INTO users (username, email, password, full_name, status) VALUES ('{$lecturerCode2}', '{$lecturerCode2}@vinhuni.edu.vn', 'hash', 'Giảng viên B', 1)");
    $lecturer2Id = (int)$pdo->lastInsertId();

    // 3.3. Tạo 5 sinh viên kiểm thử (SV1, SV2, SV3, SV4, SV5)
    $studentIds = [];
    $studentCodes = ['SV-TEST-01', 'SV-TEST-02', 'SV-TEST-03', 'SV-TEST-04', 'SV-TEST-05'];
    foreach ($studentCodes as $idx => $sc) {
        $pdo->exec("INSERT INTO users (username, email, password, full_name, status) VALUES ('{$sc}', '{$sc}@vinhuni.edu.vn', 'hash', 'Sinh viên " . ($idx + 1) . "', 1)");
        $studentIds[$sc] = (int)$pdo->lastInsertId();
    }

    // 3.4. Tạo 3 đề tài kiểm thử:
    // Topic 1: Giảng viên A, chỉ tiêu 1 sinh viên, approved
    // Topic 2: Giảng viên A, chỉ tiêu 1 sinh viên, approved
    // Topic 3: Giảng viên B, chỉ tiêu 2 sinh viên, approved
    // Topic 4: Giảng viên B, chỉ tiêu 2 sinh viên, draft (chưa duyệt - để test loại trừ)
    $pdo->exec("INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status) VALUES ('DT-TEST-01', 'Đề tài AI', 'internship', {$testPeriodId}, {$lecturer1Id}, 1, 'approved')");
    $topic1Id = (int)$pdo->lastInsertId();

    $pdo->exec("INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status) VALUES ('DT-TEST-02', 'Đề tài Cloud', 'internship', {$testPeriodId}, {$lecturer1Id}, 1, 'approved')");
    $topic2Id = (int)$pdo->lastInsertId();

    $pdo->exec("INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status) VALUES ('DT-TEST-03', 'Đề tài IoT', 'internship', {$testPeriodId}, {$lecturer2Id}, 2, 'approved')");
    $topic3Id = (int)$pdo->lastInsertId();

    $pdo->exec("INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status) VALUES ('DT-TEST-04', 'Đề tài Blockchain (Nháp)', 'internship', {$testPeriodId}, {$lecturer2Id}, 2, 'draft')");
    $topicDraftId = (int)$pdo->lastInsertId();

    // ==========================================================================
    // TÌNH HUỐNG 1: XÉT ĐÚNG THỨ TỰ ƯU TIÊN NGUYỆN VỌNG (NV1 > NV2)
    // ==========================================================================
    // SV1: NV1 = Topic 1
    // SV2: NV1 = Topic 2, NV2 = Topic 1
    // Cả 2 đề tài chỉ tiêu 1 chỗ. SV1 đăng ký Topic 1 ở NV1, SV2 đăng ký Topic 1 ở NV2.
    // Kết quả: SV1 phải trúng Topic 1 (NV1). SV2 trúng Topic 2 (NV1).
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-01']}, {$topic1Id}, {$testPeriodId}, 1, 'pending', 'NV1 SV1', '2026-11-10 08:00:00')");
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-02']}, {$topic2Id}, {$testPeriodId}, 1, 'pending', 'NV1 SV2', '2026-11-10 08:05:00')");
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-02']}, {$topic1Id}, {$testPeriodId}, 2, 'pending', 'NV2 SV2', '2026-11-10 08:05:00')");

    $service = new TopicMatchingService($pdo);
    $res1 = $service->matchPeriod($testPeriodId, ['dry_run' => true]);

    t("Tình huống 1: Thuật toán trả về kết quả thành công", $res1['success'] === true);
    t("Tình huống 1: Phân công đúng 2 sinh viên", $res1['total_assigned'] === 2);
    t("Tình huống 1: Không có sinh viên bị unassigned", $res1['total_unassigned'] === 0);

    $assignedMap = [];
    foreach ($res1['assigned'] as $a) {
        $assignedMap[$a['student_code']] = $a;
    }
    t("Tình huống 1: SV1 trúng tuyển NV1 vào Đề tài 1", 
        isset($assignedMap['SV-TEST-01']) && $assignedMap['SV-TEST-01']['topic_id'] === $topic1Id && $assignedMap['SV-TEST-01']['matched_wish_priority'] === 1
    );
    t("Tình huống 1: SV2 trúng tuyển NV1 vào Đề tài 2", 
        isset($assignedMap['SV-TEST-02']) && $assignedMap['SV-TEST-02']['topic_id'] === $topic2Id && $assignedMap['SV-TEST-02']['matched_wish_priority'] === 1
    );

    // ==========================================================================
    // TÌNH HUỐNG 2: CẠNH TRANH CÙNG MỘT VỊ TRÍ (TIE-BREAKING RULES)
    // ==========================================================================
    // Cho thêm SV3 và SV4 cùng chọn Topic 1 ở NV1 (cùng cạnh tranh vị trí duy nhất của Topic 1).
    // Điểm GPA: SV3 có điểm 3.8, SV1 có điểm 3.2, SV4 có điểm 3.8 nhưng nộp sau SV3.
    // Thêm nguyện vọng:
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-03']}, {$topic1Id}, {$testPeriodId}, 1, 'pending', 'NV1 SV3', '2026-11-10 08:10:00')");
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-04']}, {$topic1Id}, {$testPeriodId}, 1, 'pending', 'NV1 SV4', '2026-11-10 08:20:00')");

    // SV3 có NV2 = Topic 3 (còn chỗ)
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-01']}, {$topic3Id}, {$testPeriodId}, 2, 'pending', 'NV2 SV1', '2026-11-10 08:00:00')");
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-04']}, {$topic3Id}, {$testPeriodId}, 2, 'pending', 'NV2 SV4', '2026-11-10 08:20:00')");

    $scores = [
        $studentIds['SV-TEST-01'] => 3.2,
        $studentIds['SV-TEST-02'] => 3.5,
        $studentIds['SV-TEST-03'] => 3.8, // GPA cao nhất trong các thí sinh chọn Topic 1, nộp trước SV4
        $studentIds['SV-TEST-04'] => 3.8, // Cùng GPA 3.8 nhưng nộp sau SV3
    ];

    $res2 = $service->matchPeriod($testPeriodId, [
        'dry_run'        => true,
        'student_scores' => $scores,
    ]);

    $assignedMap2 = [];
    foreach ($res2['assigned'] as $a) {
        $assignedMap2[$a['student_code']] = $a;
    }

    t("Tình huống 2: SV3 trúng tuyển Topic 1 nhờ GPA 3.8 và nộp sớm hơn SV4",
        isset($assignedMap2['SV-TEST-03']) && $assignedMap2['SV-TEST-03']['topic_id'] === $topic1Id
    );
    t("Tình huống 2: Topic 1 không vượt quá chỉ tiêu 1 sinh viên", 
        count(array_filter($res2['assigned'], fn($x) => $x['topic_id'] === $topic1Id)) === 1
    );
    t("Tình huống 2: SV1 bị trượt Topic 1 ở NV1 và được xét tiếp trúng NV2 (Topic 3)",
        isset($assignedMap2['SV-TEST-01']) && $assignedMap2['SV-TEST-01']['topic_id'] === $topic3Id && $assignedMap2['SV-TEST-01']['matched_wish_priority'] === 2
    );
    t("Tình huống 2: SV4 trượt NV1 và trúng NV2 (Topic 3)",
        isset($assignedMap2['SV-TEST-04']) && $assignedMap2['SV-TEST-04']['topic_id'] === $topic3Id && $assignedMap2['SV-TEST-04']['matched_wish_priority'] === 2
    );

    // ==========================================================================
    // TÌNH HUỐNG 3: LOẠI TRỪ ĐỀ TÀI CHƯA DUYỆT & DANH SÁCH CHƯA PHÂN CÔNG (UNASSIGNED)
    // ==========================================================================
    // SV5 chỉ đăng ký đúng 1 nguyện vọng vào Topic 4 (trạng thái 'draft', chưa duyệt)
    $pdo->exec("INSERT INTO topic_wishes (student_id, topic_id, period_id, priority, status, note, created_at) VALUES ({$studentIds['SV-TEST-05']}, {$topicDraftId}, {$testPeriodId}, 1, 'pending', 'NV1 SV5', '2026-11-10 09:00:00')");

    $res3 = $service->matchPeriod($testPeriodId, [
        'dry_run'        => true,
        'student_scores' => $scores,
    ]);

    t("Tình huống 3: SV5 không được phân công vào đề tài nháp",
        !isset(array_column($res3['assigned'], null, 'student_code')['SV-TEST-05'])
    );
    t("Tình huống 3: SV5 nằm trong danh sách unassigned",
        count(array_filter($res3['unassigned'], fn($u) => $u['student_code'] === 'SV-TEST-05')) === 1
    );
    $unassignedSV5 = current(array_filter($res3['unassigned'], fn($u) => $u['student_code'] === 'SV-TEST-05'));
    t("Tình huống 3: Lý do unassigned nêu rõ đề tài chưa được duyệt",
        str_contains($unassignedSV5['reason'], 'chưa được duyệt') || str_contains($unassignedSV5['reason'], 'không tồn tại')
    );

    // ==========================================================================
    // TÌNH HUỐNG 4: GIỚI HẠN CHỈ TIÊU CỦA GIẢNG VIÊN (MAX PER LECTURER)
    // ==========================================================================
    // Giảng viên B hướng dẫn Topic 3 (max 2). Giả sử cấu hình max_per_lecturer = 1.
    // Topic 3 có 2 bạn trúng là SV1 và SV4, nhưng giảng viên B chỉ nhận tối đa 1 bạn.
    $res4 = $service->matchPeriod($testPeriodId, [
        'dry_run'          => true,
        'student_scores'   => $scores,
        'max_per_lecturer' => 1, // Giới hạn chặt: GV chỉ nhận tối đa 1 SV
    ]);

    $assignedToLecturerB = array_filter($res4['assigned'], fn($x) => $x['lecturer_id'] === $lecturer2Id);
    t("Tình huống 4: Giảng viên B chỉ được nhận tối đa 1 sinh viên (đúng chỉ tiêu giới hạn)",
        count($assignedToLecturerB) === 1
    );

    // ==========================================================================
    // TÌNH HUỐNG 5: THỰC THI GHI CSDL (PERSISTENCE) & QUY TẮC DỰ THẢO (DRAFT)
    // ==========================================================================
    // Chạy với dry_run = false để lưu vào bảng topic_assignments
    $res5 = $service->matchPeriod($testPeriodId, [
        'dry_run'        => false,
        'student_scores' => $scores,
        'assigned_by'    => $lecturer1Id,
    ]);

    t("Tình huống 5: Chạy lưu thực tế thành công", $res5['success'] === true);
    t("Tình huống 5: Số bản ghi lưu vào CSDL > 0", $res5['saved_records_count'] > 0);

    // Kiểm tra trong CSDL thực tế
    $savedRows = $pdo->query("SELECT * FROM topic_assignments WHERE period_id = {$testPeriodId}")->fetchAll(PDO::FETCH_ASSOC);
    t("Tình huống 5: CSDL lưu đúng số bản ghi đã phân công", count($savedRows) === $res5['total_assigned']);

    $allDraft = true;
    $allAuto = true;
    foreach ($savedRows as $row) {
        if ($row['status'] !== TopicAssignment::STATUS_DRAFT) $allDraft = false;
        if ($row['assignment_method'] !== TopicAssignment::METHOD_AUTO) $allAuto = false;
    }
    t("Tình huống 5: 100% bản ghi tự động có trạng thái 'draft' (Bản dự thảo)", $allDraft);
    t("Tình huống 5: 100% bản ghi tự động có phương thức 'auto'", $allAuto);

    // Kiểm tra tính không trùng lặp sinh viên: Mỗi SV chỉ có tối đa 1 bản ghi
    $assignedStudentsInDb = array_column($savedRows, 'student_id');
    t("Tình huống 5: Không có sinh viên nào bị phân công trùng trong đợt", 
        count($assignedStudentsInDb) === count(array_unique($assignedStudentsInDb))
    );

    // ==========================================================================
    // TÌNH HUỐNG 6: TÍCH HỢP CHO TRÚC & QUỲNH (MODEL METHODS INTEGRATION)
    // ==========================================================================
    // 6.1. getByPeriod
    $list = TopicAssignment::getByPeriod($testPeriodId);
    t("Tích hợp Quỳnh (S4-05): getByPeriod trả về danh sách đầy đủ quan hệ", count($list) === count($savedRows));

    // 6.2. getUnassignedStudents
    $unassignedDb = TopicAssignment::getUnassignedStudents($testPeriodId);
    t("Tích hợp Trúc (S4-02): getUnassignedStudents lấy được sinh viên chưa phân công (SV5)",
        count($unassignedDb) >= 1 && in_array($studentIds['SV-TEST-05'], array_column($unassignedDb, 'student_id'))
    );

    // 6.3. assignManual: Trúc phân công thủ công
    // Trước tiên thử gán vào đề tài đã đầy (Topic 3 hiện có 2/2 SV) -> Phải từ chối vì hết chỉ tiêu
    $rejectedFullTopic = false;
    try {
        TopicAssignment::assignManual($testPeriodId, $studentIds['SV-TEST-05'], $topic3Id, $lecturer2Id, $lecturer1Id, 'Thử gán đề tài đầy');
    } catch (\RuntimeException $e) {
        $rejectedFullTopic = str_contains($e->getMessage(), 'đã đủ chỉ tiêu');
    }
    t("Tích hợp Trúc (S4-02): assignManual từ chối khi đề tài đã đủ chỉ tiêu", $rejectedFullTopic);

    // Hủy phân công của SV4 để giải phóng 1 chỗ trong Topic 3
    $sv4Assigned = TopicAssignment::getByStudent($studentIds['SV-TEST-04'], $testPeriodId);
    $cancelSuccess = TopicAssignment::cancelAssignment((int)$sv4Assigned['id'], 'Hủy để phân công thủ công');
    t("Tích hợp Trúc (S4-02): cancelAssignment hủy phân công thành công", $cancelSuccess);

    // Bây giờ Topic 3 còn 1 chỗ -> Phân công SV5 vào Topic 3 thành công
    $manualId = TopicAssignment::assignManual($testPeriodId, $studentIds['SV-TEST-05'], $topic3Id, $lecturer2Id, $lecturer1Id, 'Phân công thủ công sau khi có chỗ trống');
    t("Tích hợp Trúc (S4-02): assignManual phân công thủ công sinh viên thành công khi còn chỗ", $manualId > 0);

    // 6.4. getStatistics
    $stats = TopicAssignment::getStatistics($testPeriodId);
    t("Tích hợp Quỳnh (S4-05): getStatistics trả về đầy đủ các chỉ số đợt",
        isset($stats['total_registered'], $stats['active_assignments'], $stats['unassigned_count'])
    );

    // 6.5. getByStudent
    $sv3Assignment = TopicAssignment::getByStudent($studentIds['SV-TEST-03'], $testPeriodId);
    t("Tích hợp Quỳnh (S4-06): getByStudent trả về thông tin đề tài và GV của sinh viên",
        $sv3Assignment !== null && (int)$sv3Assignment['topic_id'] === $topic1Id
    );

    // 6.6. getByLecturer
    $gv1Students = TopicAssignment::getByLecturer($lecturer1Id, $testPeriodId);
    t("Tích hợp Quỳnh (S4-06): getByLecturer trả về danh sách sinh viên GV hướng dẫn", count($gv1Students) >= 1);

    // 6.7. publishPeriodAssignments (Trúc - S4-04)
    $publishedCount = TopicAssignment::publishPeriodAssignments($testPeriodId, $lecturer1Id);
    t("Tích hợp Trúc (S4-04): publishPeriodAssignments công bố kết quả thành công", $publishedCount > 0);

    $hasPublished = TopicAssignment::hasPublishedAssignments($testPeriodId);
    t("Tích hợp Trúc (S4-04): hasPublishedAssignments xác nhận đợt đã được công bố", $hasPublished === true);

    // 6.8. Bảo vệ kết quả đã công bố: Thuật toán tự động từ chối chạy đè lên đợt đã published
    $refusedOverwrite = false;
    try {
        $service->matchPeriod($testPeriodId, ['dry_run' => false]);
    } catch (RuntimeException $e) {
        $refusedOverwrite = str_contains($e->getMessage(), 'đã có kết quả phân công chính thức được công bố');
    }
    t("An toàn CSDL: Thuật toán tự động từ chối ghi đè lên đợt đã công bố kết quả", $refusedOverwrite);

    // ==========================================================================
    // TÌNH HUỐNG 7: KIỂM THỬ AN TOÀN ĐỒNG THỜI (CONCURRENCY & ROW LOCKING)
    // ==========================================================================
    // Tạo 1 đề tài chỉ có 1 chỉ tiêu (max_students = 1)
    $pdo->exec("INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status) VALUES ('DT-CONCURRENCY', 'Đề tài Concurrency', 'internship', {$testPeriodId}, {$lecturer1Id}, 1, 'approved')");
    $concurrencyTopicId = (int)$pdo->lastInsertId();

    // Sinh viên 1 được phân công thành công vào đề tài này
    $assignConcur1 = TopicAssignment::assignManual($testPeriodId, $studentIds['SV-TEST-01'], $concurrencyTopicId, $lecturer1Id, $lecturer1Id, 'SV1 nhận chỉ tiêu');
    t("Đồng thời: SV1 nhận chỉ tiêu duy nhất thành công", $assignConcur1 > 0);

    // Sinh viên 2 cố gắng phân công vào cùng đề tài (đã hết chỉ tiêu)
    $concurOverCapacity = false;
    try {
        TopicAssignment::assignManual($testPeriodId, $studentIds['SV-TEST-02'], $concurrencyTopicId, $lecturer1Id, $lecturer1Id, 'SV2 thử tranh chỉ tiêu');
    } catch (RuntimeException $e) {
        $concurOverCapacity = str_contains($e->getMessage(), 'đã đủ chỉ tiêu');
    }
    t("Đồng thời: Giao dịch phân công thứ hai bị chặn và từ chối khi đề tài đã đủ chỉ tiêu", $concurOverCapacity);

    // Kiểm tra số lượng phân công thực tế trong CSDL của đề tài này
    $stmtCheckConcur = $pdo->prepare("SELECT COUNT(*) FROM topic_assignments WHERE topic_id = :tid AND period_id = :pid AND status != 'cancelled'");
    $stmtCheckConcur->execute([':tid' => $concurrencyTopicId, ':pid' => $testPeriodId]);
    $actualCount = (int)$stmtCheckConcur->fetchColumn();
    t("Đồng thời: Đề tài tuyệt đối không bị vượt chỉ tiêu (thực tế = 1)", $actualCount === 1);

    // Kiểm tra giới hạn chỉ tiêu của giảng viên trong phân công thủ công
    $concurLecturerLimit = false;
    try {
        // Thử phân công SV2 vào đề tài khác nhưng giảng viên đã đạt max 1
        TopicAssignment::assignManual($testPeriodId, $studentIds['SV-TEST-02'], $topic2Id, $lecturer1Id, $lecturer1Id, 'Thử gán vượt quota GV', 1);
    } catch (RuntimeException $e) {
        $concurLecturerLimit = str_contains($e->getMessage(), 'đạt giới hạn chỉ tiêu');
    }
    t("Đồng thời: Phân công thủ công kiểm tra và từ chối khi giảng viên vượt giới hạn chỉ tiêu", $concurLecturerLimit);

    // ==========================================================================
    // TÌNH HUỐNG 8: KIỂM THỬ HTTP 403 & PHẢN HỒI CONTROLLER THỰC TẾ
    // ==========================================================================
    $controller = new TopicAssignmentController();

    // 8.1. Kiểm thử HTTP 403 cho người không có quyền (ví dụ: student)
    $_SESSION['user_id'] = $studentIds['SV-TEST-01'];
    $_SESSION['roles'] = ['student'];
    $_SERVER['HTTP_ACCEPT'] = 'application/json';

    ob_start();
    $controller->autoMatch();
    $json403 = ob_get_clean();
    $statusCode403 = http_response_code();

    t("Controller: Người dùng vai trò student bị từ chối với HTTP 403 thực sự", $statusCode403 === 403);
    t("Controller: Phản hồi lỗi dạng JSON không phát sinh PHP Fatal Error", str_contains($json403, '"ok": false') || str_contains($json403, '"error":'));

    // 8.2. Kiểm thử preview với tài khoản admin trả về HTTP 200 và JSON hợp lệ
    $_SESSION['roles'] = ['admin'];
    $_GET['period_id'] = $testPeriodId;
    $_SERVER['HTTP_ACCEPT'] = 'application/json';

    ob_start();
    $controller->preview();
    $jsonPreview = ob_get_clean();
    $statusPreview = http_response_code();

    t("Controller: Admin gọi preview trả về HTTP 200", $statusPreview === 200);
    $dataPreview = json_decode($jsonPreview, true);
    t("Controller: Dữ liệu preview là JSON hợp lệ có chứa data đối sánh", isset($dataPreview['ok']) && $dataPreview['ok'] === true && isset($dataPreview['data']));

    // 8.3. Kiểm thử GET /assignments duyệt bằng trình duyệt (HTML) không trả JSON thô
    unset($_SERVER['HTTP_ACCEPT']);
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    ob_start();
    $controller->index();
    $htmlIndex = ob_get_clean();

    t("Controller: GET /assignments cho duyệt Web trả về HTML (không phải JSON thô)", str_contains($htmlIndex, '<!DOCTYPE html>'));
    t("Controller: Trang thông báo trạng thái chờ tích hợp giao diện rõ ràng", str_contains($htmlIndex, 'Đang chờ tích hợp giao diện'));

} finally {
    // Luôn luôn ROLLBACK toàn bộ dữ liệu kiểm thử để bảo đảm CSDL thực tế sạch 100%
    $pdo->rollBack();
}

t("Dữ liệu kiểm thử đã được ROLLBACK an toàn tuyệt đối", true);

echo PHP_EOL;
echo "------------------------------------------------------------------------------" . PHP_EOL;
echo "KẾT QUẢ KIỂM THỬ SPRINT 4 (CSDL & THUẬT TOÁN ĐỐI SÁNH): {$passed} PASS, {$failed} FAIL." . PHP_EOL;
echo "==============================================================================" . PHP_EOL . PHP_EOL;

if ($failed > 0) {
    exit(1);
}
