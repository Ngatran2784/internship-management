<?php
declare(strict_types=1);

/**
 * KIỂM THỬ TỰ ĐỘNG SIDEBAR & ĐIỀU HƯỚNG TRẠNG THÁI TOÀN BỘ HỆ THỐNG
 * Dự án: Hệ thống quản lý thực tập và đồ án sinh viên
 * 
 * Kiểm tra:
 * 1. Độc quyền trạng thái Active: Đúng duy nhất 1 mục active cho mỗi route
 * 2. Trạng thái Expanded của nhóm cha khi truy cập mục con
 * 3. Các route xem chi tiết / sửa đợt / sửa user active đúng mục Danh sách tương ứng
 * 4. Đầy đủ 8 nhóm menu hệ thống theo chuẩn yêu cầu
 * 5. Các nhóm 4-8 thuộc sprint tiếp theo ở trạng thái disabled đúng chuẩn (không route giả, không 404)
 * 6. Đã xóa bỏ hoàn toàn dòng tiêu đề "Chức năng mở rộng" và nhãn "Sắp", "Coming soon"
 * 7. Không có xung đột CSS giữa header.php và vinhuni-theme.css
 * 8. Kiểm tra phân quyền hiển thị menu (Admin, Training Dept, Student, Lecturer)
 */

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

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

echo PHP_EOL . "==== KIỂM THỬ ĐIỀU HƯỚNG VÀ TRẠNG THÁI SIDEBAR ====" . PHP_EOL . PHP_EOL;

// ── 1. Kiểm tra CSS trong header.php và vinhuni-theme.css ────────────────────
$headerHtml = file_get_contents(dirname(__DIR__) . '/app/Views/layouts/header.php');
$themeCss = file_get_contents(dirname(__DIR__) . '/public/assets/css/vinhuni-theme.css');

t("header.php không chứa khai báo active sidebar xung đột", !str_contains($headerHtml, '.app-sidebar .nav-link.active'));
t("vinhuni-theme.css chứa quy tắc active chuẩn cho a.nav-link.active", str_contains($themeCss, '.app-sidebar a.nav-link.active'));
t("vinhuni-theme.css chứa trạng thái focus-visible phân biệt", str_contains($themeCss, '.nav-link:focus-visible'));
t("vinhuni-theme.css chứa trạng thái disabled chuẩn cho sidebar", str_contains($themeCss, '.app-sidebar .nav-link.disabled'));
t("vinhuni-theme.css phân biệt toggle nhóm cha không dính active", str_contains($themeCss, '.sidebar-group-toggle[aria-expanded="true"]'));

// ── 2. Hàm giả lập render Sidebar cho từng vai trò và URI ────────────────────
function renderSidebarForTest(array $roles, string $requestUri, string $activePage = ''): string {
    $_SESSION['roles'] = $roles;
    $_SERVER['REQUEST_URI'] = $requestUri;
    $baseUrl = '/CNPM/internship-management/public';

    ob_start();
    require dirname(__DIR__) . '/app/Views/layouts/sidebar.php';
    return ob_get_clean() ?: '';
}

// ── 3. Kiểm tra logic Admin trên các Route ───────────────────────────────────
$adminRoles = ['admin'];

// Route /dashboard
$sbDash = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/dashboard', 'dashboard');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbDash, $mDash);
t("Route /dashboard: Có duy nhất 1 mục active", count($mDash[0]) === 1);
t("Route /dashboard: Mục active là Bảng điều khiển", str_contains($sbDash, 'href="/CNPM/internship-management/public/dashboard"') && str_contains($sbDash, 'aria-current="page"'));

// Route /users
$sbUsers = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/users', 'users');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbUsers, $mUsers);
t("Route /users: Có duy nhất 1 mục active", count($mUsers[0]) === 1);
t("Route /users: Mục active là Danh sách người dùng", str_contains($sbUsers, 'href="/CNPM/internship-management/public/users"') && str_contains($sbUsers, 'aria-current="page"'));
t("Route /users: Nhóm Quản lý người dùng được mở (aria-expanded=true)", str_contains($sbUsers, 'data-bs-target="#sidebar-users-menu"') && str_contains($sbUsers, 'aria-expanded="true"'));

// Route /users/create
$sbUserCreate = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/users/create', 'users');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbUserCreate, $mUserCreate);
t("Route /users/create: Có duy nhất 1 mục active", count($mUserCreate[0]) === 1);
t("Route /users/create: Mục active là Thêm người dùng", str_contains($sbUserCreate, 'href="/CNPM/internship-management/public/users/create"') && str_contains($sbUserCreate, 'aria-current="page"'));

// Route /users/edit?id=5 (Route sửa)
$sbUserEdit = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/users/edit?id=5', 'users');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbUserEdit, $mUserEdit);
t("Route /users/edit: Có duy nhất 1 mục active", count($mUserEdit[0]) === 1);
t("Route /users/edit: Active mục Danh sách người dùng tương ứng", str_contains($sbUserEdit, 'href="/CNPM/internship-management/public/users"') && str_contains($sbUserEdit, 'aria-current="page"'));

// Route /profile
$sbProfile = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/profile', 'profile');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbProfile, $mProfile);
t("Route /profile: Có duy nhất 1 mục active", count($mProfile[0]) === 1);
t("Route /profile: Mục active là Thông tin cá nhân", str_contains($sbProfile, 'href="/CNPM/internship-management/public/profile"') && str_contains($sbProfile, 'aria-current="page"'));

// Route /periods
$sbPeriods = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/periods', 'periods');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbPeriods, $mPeriods);
t("Route /periods: Có duy nhất 1 mục active", count($mPeriods[0]) === 1);
t("Route /periods: Mục active là Danh sách đợt", str_contains($sbPeriods, 'href="/CNPM/internship-management/public/periods"') && str_contains($sbPeriods, 'aria-current="page"'));

// Route /periods/create
$sbPeriodCreate = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/periods/create', 'periods');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbPeriodCreate, $mPeriodCreate);
t("Route /periods/create: Có duy nhất 1 mục active", count($mPeriodCreate[0]) === 1);
t("Route /periods/create: Mục active là Tạo đợt mới", str_contains($sbPeriodCreate, 'href="/CNPM/internship-management/public/periods/create"') && str_contains($sbPeriodCreate, 'aria-current="page"'));

// Route /periods/detail?id=11
$sbPeriodDetail = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/periods/detail?id=11', 'periods');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbPeriodDetail, $mPeriodDetail);
t("Route /periods/detail: Có duy nhất 1 mục active", count($mPeriodDetail[0]) === 1);
t("Route /periods/detail: Active mục Danh sách đợt tương ứng", str_contains($sbPeriodDetail, 'href="/CNPM/internship-management/public/periods"') && str_contains($sbPeriodDetail, 'aria-current="page"'));

// Route /periods/edit?id=11
$sbPeriodEdit = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/periods/edit?id=11', 'periods');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbPeriodEdit, $mPeriodEdit);
t("Route /periods/edit: Có duy nhất 1 mục active", count($mPeriodEdit[0]) === 1);
t("Route /periods/edit: Active mục Danh sách đợt tương ứng", str_contains($sbPeriodEdit, 'href="/CNPM/internship-management/public/periods"') && str_contains($sbPeriodEdit, 'aria-current="page"'));

// Route topics
foreach ([
    ['/topics', '/topics'],
    ['/topics/create', '/topics/create'],
    ['/topics/pending', '/topics/pending'],
    ['/topics/detail?id=5', '/topics'],
] as [$request, $activeHref]) {
    $sbTopic = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public' . $request, 'topics');
    preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbTopic, $matches);
    t("Route {$request}: Có duy nhất 1 mục active", count($matches[0]) === 1);
    t("Route {$request}: Active đúng mục đề tài", str_contains($sbTopic, 'href="/CNPM/internship-management/public' . $activeHref . '"') && str_contains($sbTopic, 'aria-current="page"'));
}

// ── 4. Kiểm tra đầy đủ 8 nhóm menu hệ thống trong Admin theo đúng danh sách ──
t("Nhóm 1: Bảng điều khiển tồn tại", str_contains($sbDash, 'Bảng điều khiển'));
t("Nhóm 2: Quản lý người dùng tồn tại", str_contains($sbDash, 'Quản lý người dùng'));
t("Nhóm 2 có menu con: Danh sách người dùng, Thêm người dùng, Thông tin cá nhân", 
    str_contains($sbDash, 'Danh sách người dùng') && 
    str_contains($sbDash, 'Thêm người dùng') && 
    str_contains($sbDash, 'Thông tin cá nhân')
);
t("Nhóm 3: Đợt thực tập & đồ án tồn tại", str_contains($sbDash, 'Đợt thực tập &amp; đồ án'));
t("Nhóm 3 có menu con: Danh sách đợt, Tạo đợt mới", 
    str_contains($sbDash, 'Danh sách đợt') && 
    str_contains($sbDash, 'Tạo đợt mới')
);
t("Nhóm 4: Quản lý vai trò và phân quyền tồn tại ở trạng thái disabled", 
    str_contains($sbDash, 'Quản lý vai trò và phân quyền') && str_contains($sbDash, 'aria-disabled="true"')
);
t("Nhóm 5: Quản lý đề tài có danh sách, đề xuất và chờ duyệt",
    str_contains($sbDash, 'Quản lý đề tài') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics"') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics/create"') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics/pending"')
);
t("Nhóm 6: Phân công đề tài và giảng viên tồn tại ở trạng thái disabled", 
    str_contains($sbDash, 'Phân công đề tài và giảng viên') && str_contains($sbDash, 'aria-disabled="true"')
);
t("Nhóm 7: Quản lý đơn vị thực tập tồn tại ở trạng thái disabled", 
    str_contains($sbDash, 'Quản lý đơn vị thực tập') && str_contains($sbDash, 'aria-disabled="true"')
);
t("Nhóm 8: Thống kê và báo cáo tồn tại ở trạng thái disabled", 
    str_contains($sbDash, 'Thống kê và báo cáo') && str_contains($sbDash, 'aria-disabled="true"')
);

// ── 5. Kiểm tra loại bỏ hoàn toàn tiêu đề 'Chức năng mở rộng' và nhãn 'Sắp' ──
t("Admin: Không còn tiêu đề 'Chức năng mở rộng'", !str_contains($sbDash, 'Chức năng mở rộng'));
t("Admin: Không còn bất kỳ nhãn 'Sắp', 'Sắp có' nào", !str_contains($sbDash, 'Sắp có') && !str_contains($sbDash, 'badge bg-secondary-lt'));
t("Admin: Không có liên kết giả (href='#', route ảo)", !preg_match('/href=["\'](?:#|javascript:)/i', $sbDash));

// ── 6. Kiểm tra phân quyền cho các vai trò khác ─────────────────────────────
// Sinh viên
$sbStudent = renderSidebarForTest(['student'], '/CNPM/internship-management/public/dashboard', 'dashboard');
t("Sinh viên: Không thấy menu Quản lý người dùng", !str_contains($sbStudent, 'Quản lý người dùng'));
t("Sinh viên: Không thấy menu Tạo đợt mới", !str_contains($sbStudent, 'Tạo đợt mới'));
t("Sinh viên: Có mục Bảng điều khiển active", str_contains($sbStudent, 'Bảng điều khiển'));
t("Sinh viên: Có danh sách đề tài đã được duyệt", str_contains($sbStudent, 'Danh sách đề tài') && str_contains($sbStudent, 'href="/CNPM/internship-management/public/topics"'));
t("Sinh viên: Không còn nhãn 'Sắp có'", !str_contains($sbStudent, 'Sắp có'));
t("Sinh viên: Có các mục nhiệm vụ ở trạng thái disabled chuẩn", str_contains($sbStudent, 'Đăng ký đề tài') && str_contains($sbStudent, 'disabled'));

// Giảng viên
$sbLecturer = renderSidebarForTest(['lecturer'], '/CNPM/internship-management/public/profile', 'profile');
t("Giảng viên: Active đúng mục Thông tin cá nhân", str_contains($sbLecturer, 'Thông tin cá nhân') && str_contains($sbLecturer, 'aria-current="page"'));
t("Giảng viên: Không còn nhãn 'Sắp có'", !str_contains($sbLecturer, 'Sắp có'));
t("Giảng viên: Có các mục nhiệm vụ giảng dạy disabled", str_contains($sbLecturer, 'Đề tài hướng dẫn') && str_contains($sbLecturer, 'disabled'));

// Phòng đào tạo
$sbTraining = renderSidebarForTest(['training_dept'], '/CNPM/internship-management/public/periods', 'periods');
t("Phòng đào tạo: Không thấy menu Quản lý người dùng", !str_contains($sbTraining, 'Quản lý người dùng'));
t("Phòng đào tạo: Thấy menu Đợt thực tập & đồ án", str_contains($sbTraining, 'Đợt thực tập &amp; đồ án'));
t("Phòng đào tạo: Có quản lý đề tài và chờ duyệt", str_contains($sbTraining, 'Quản lý đề tài') && str_contains($sbTraining, 'href="/CNPM/internship-management/public/topics/pending"'));
t("Phòng đào tạo: Không còn tiêu đề 'Chức năng mở rộng' và nhãn 'Sắp có'", 
    !str_contains($sbTraining, 'Chức năng mở rộng') && !str_contains($sbTraining, 'Sắp có')
);

echo PHP_EOL . "----------------------------------------------" . PHP_EOL;
echo "KẾT QUẢ KIỂM THỬ SIDEBAR: {$passed} PASS, {$failed} FAIL." . PHP_EOL;
echo "==============================================" . PHP_EOL;

if ($failed > 0) {
    exit(1);
}