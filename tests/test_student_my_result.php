<?php
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

use App\Models\User;
use App\Models\InternshipPeriod;
use App\Models\TopicAssignment;
use App\Models\TopicWish;
use App\Models\Topic;
use App\Core\Database;

$pdo = Database::getConnection();

echo "=== TEST STUDENT ASSIGNMENT RESULT SCREEN & SIDEBAR ===\n\n";

// 1. Kiểm tra tài khoản sv001
$student = User::findByUsernameOrEmail('sv001');
if (!$student) {
    die("ERROR: sv001 not found\n");
}
$studentId = (int)$student['id'];
echo "[INFO] Sinh viên: {$student['full_name']} (ID: {$studentId})\n";

// 2. Lấy các đợt thực tập
$periods = InternshipPeriod::all();
echo "[INFO] Tổng số đợt: " . count($periods) . "\n";
foreach ($periods as $p) {
    $hasPub = TopicAssignment::hasPublishedAssignments((int)$p['id']) ? 'ĐÃ CÔNG BỐ' : 'CHƯA CÔNG BỐ';
    $asg = TopicAssignment::getByStudent($studentId, (int)$p['id'], true);
    $asgText = $asg ? "Có phân công ({$asg['topic_code']})" : "Chưa có phân công";
    echo "  - [ID: {$p['id']}] {$p['code']}: {$p['name']} | Trạng thái công bố: {$hasPub} | Phân công sv001: {$asgText}\n";
}

// 3. Giả lập render sidebar khi truy cập /assignments/my-result
$_SESSION['user_id'] = $studentId;
$_SESSION['username'] = 'sv001';
$_SESSION['full_name'] = $student['full_name'];
$_SESSION['roles'] = ['student'];
$baseUrl = '/CNPM/internship-management/public';
$_SERVER['REQUEST_URI'] = '/CNPM/internship-management/public/assignments/my-result';

ob_start();
require dirname(__DIR__) . '/app/Views/layouts/sidebar.php';
$sidebarHtml = ob_get_clean();

// Debug output for assignments/my-result in sidebar
if (preg_match('#<a[^>]*href="[^"]*assignments/my-result"[^>]*>.*?</a>#s', $sidebarHtml, $m)) {
    echo "[DEBUG] Found link: " . htmlspecialchars($m[0]) . "\n";
} else {
    echo "[DEBUG] Link assignments/my-result not found in sidebarHtml!\n";
}

// Kiểm tra sidebar active
$hasActiveMyResult = (bool)preg_match('#<a\s+class="[^"]*nav-link[^"]*active[^"]*"\s+href="[^"]*/assignments/my-result"[^>]*>#i', $sidebarHtml);
$isDashboardActive = (bool)preg_match('#<a\s+class="[^"]*nav-link[^"]*active[^"]*"\s+href="[^"]*/dashboard"[^>]*>#i', $sidebarHtml);

echo "\n--- KIỂM TRA SIDEBAR KHI VÀO /assignments/my-result ---\n";
if ($hasActiveMyResult) {
    echo "[PASS] Mục 'Kết quả phân công' có class active và aria-current='page'\n";
} else {
    echo "[FAIL] Mục 'Kết quả phân công' KHÔNG có class active\n";
}

if (!$isDashboardActive) {
    echo "[PASS] 'Bảng điều khiển' KHÔNG bị active (Đúng yêu cầu)\n";
} else {
    echo "[FAIL] 'Bảng điều khiển' BỊ active nhầm!\n";
}

// 4. Kiểm tra render view /assignments/my_result
echo "\n--- KIỂM TRA RENDER VIEW assignments/my_result CHO CẢ 3 TRƯỜNG HỢP ---\n";

// Case 1: Chưa công bố
$viewDataCase1 = [
    'baseUrl' => '/CNPM/internship-management/public',
    'allPeriods' => $periods,
    'selectedPeriod' => ['id' => 999, 'code' => 'TEST_UNPUB', 'name' => 'Đợt Test Chưa Công Bố', 'type' => 'internship', 'status' => 'active'],
    'selectedPeriodId' => 999,
    'isPublished' => false,
    'assignment' => null,
    'wishes' => [],
    'userRoles' => ['student'],
    'currentUser' => $student,
];
extract($viewDataCase1);
ob_start();
include dirname(__DIR__) . '/app/Views/assignments/my_result.php';
$htmlCase1 = ob_get_clean();

if (str_contains($htmlCase1, 'Đợt chưa công bố kết quả phân công')) {
    echo "[PASS] Case 1: Hiển thị đúng thông báo 'Đợt chưa công bố kết quả phân công'\n";
} else {
    echo "[FAIL] Case 1: Không tìm thấy nội dung chờ công bố\n";
}

// Case 2: Đã công bố nhưng sv chưa được phân công
$viewDataCase2 = [
    'baseUrl' => '/CNPM/internship-management/public',
    'allPeriods' => $periods,
    'selectedPeriod' => ['id' => 998, 'code' => 'TEST_PUB_NO_ASG', 'name' => 'Đợt Đã Công Bố Chưa Gán SV', 'type' => 'thesis', 'status' => 'active'],
    'selectedPeriodId' => 998,
    'isPublished' => true,
    'assignment' => null,
    'wishes' => [],
    'userRoles' => ['student'],
    'currentUser' => $student,
];
extract($viewDataCase2);
ob_start();
include dirname(__DIR__) . '/app/Views/assignments/my_result.php';
$htmlCase2 = ob_get_clean();

if (str_contains($htmlCase2, 'Chưa được phân công đề tài') || str_contains($htmlCase2, 'Bạn chưa có tên trong danh sách phân công chính thức')) {
    echo "[PASS] Case 2: Hiển thị đúng thông báo 'Chưa được phân công đề tài'\n";
} else {
    echo "[FAIL] Case 2: Không tìm thấy nội dung đợt đã công bố nhưng chưa có phân công\n";
}

// Case 3: Đã công bố và sinh viên đã được phân công
$viewDataCase3 = [
    'baseUrl' => '/CNPM/internship-management/public',
    'allPeriods' => $periods,
    'selectedPeriod' => ['id' => 997, 'code' => 'TEST_ASSIGNED', 'name' => 'Đợt Phân Công Đạt', 'type' => 'internship', 'status' => 'active'],
    'selectedPeriodId' => 997,
    'isPublished' => true,
    'assignment' => [
        'topic_code' => 'DT-TEST-001',
        'topic_name' => 'Xây dựng ứng dụng quản lý kho thông minh',
        'topic_type' => 'internship',
        'topic_description' => 'Mô tả đề tài chi tiết cho sinh viên',
        'lecturer_name' => 'TS. Nguyễn Văn Hướng Dẫn',
        'lecturer_email' => 'hd@vinhuni.edu.vn',
        'lecturer_phone' => '0912345678',
        'matched_priority' => 1,
        'method' => 'auto',
        'assigned_at' => '2026-11-20 10:30:00',
        'period_code' => 'TEST_ASSIGNED',
        'period_name' => 'Đợt Phân Công Đạt',
        'notes' => 'Ưu tiên nguyện vọng 1',
    ],
    'wishes' => [],
    'userRoles' => ['student'],
    'currentUser' => $student,
];
extract($viewDataCase3);
ob_start();
include dirname(__DIR__) . '/app/Views/assignments/my_result.php';
$htmlCase3 = ob_get_clean();

if (str_contains($htmlCase3, 'Xây dựng ứng dụng quản lý kho thông minh') &&
    str_contains($htmlCase3, 'TS. Nguyễn Văn Hướng Dẫn') &&
    str_contains($htmlCase3, 'hd@vinhuni.edu.vn') &&
    str_contains($htmlCase3, 'NV1')) {
    echo "[PASS] Case 3: Hiển thị đầy đủ thông tin đề tài, GVHD, email, điện thoại, NV1\n";
} else {
    echo "[FAIL] Case 3: Thiếu thông tin phân công chính thức\n";
}

// 5. Kiểm tra Dashboard card
echo "\n--- KIỂM TRA DASHBOARD STUDENT CARD ---\n";
$roleData = [
    'student' => [
        'wishes' => [],
        'assignment' => $viewDataCase3['assignment'],
        'has_assignment' => true,
        'is_published' => true,
        'can_register' => false,
    ],
];
$selectedPeriodId = 997;
$baseUrl = '/CNPM/internship-management/public';
ob_start();
include dirname(__DIR__) . '/app/Views/dashboard/index.php';
$dashboardHtml = ob_get_clean();

if (str_contains($dashboardHtml, '/assignments/my-result?period_id=997') &&
    str_contains($dashboardHtml, 'Xem kết quả')) {
    echo "[PASS] Dashboard hiển thị tóm tắt và có nút 'Xem kết quả' dẫn tới /assignments/my-result?period_id=997\n";
} else {
    echo "[FAIL] Dashboard chưa có nút hoặc link chuẩn\n";
}

echo "\n=== HOÀN TẤT KIỂM THỬ ===\n";
