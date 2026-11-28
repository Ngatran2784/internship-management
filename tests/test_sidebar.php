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
 * 5. Các mục chưa có route vẫn disabled; chức năng phân công dùng route Backend hiện có
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
t("Route /profile: Mục active là Hồ sơ cá nhân", str_contains($sbProfile, 'href="/CNPM/internship-management/public/profile"') && str_contains($sbProfile, 'aria-current="page"'));

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

foreach (['/milestones', '/milestones/detail?id=5', '/milestones/show?id=5', '/milestones/create', '/milestones/edit?id=5'] as $request) {
    $sbMilestones = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public' . $request, 'milestones');
    preg_match_all('/class="[^"]*nav-link[^"]*\\bactive\\b[^"]*"/', $sbMilestones, $milestoneActiveLinks);
    t("Route {$request}: Active đúng mục Mốc tiến độ",
        count($milestoneActiveLinks[0]) === 1
        && str_contains($sbMilestones, 'href="/CNPM/internship-management/public/milestones"')
        && str_contains($sbMilestones, 'aria-current="page"')
    );
}
$sbMilestoneApi = renderSidebarForTest($adminRoles, '/CNPM/internship-management/public/api/milestones/progress');
preg_match_all('/class="[^"]*nav-link[^"]*\\bactive\\b[^"]*"/', $sbMilestoneApi, $milestoneApiActiveLinks);
t("Route API tiến độ không active menu giao diện", count($milestoneApiActiveLinks[0]) === 0);

// ── 4. Kiểm tra các nhóm menu hệ thống trong Admin theo chức năng hoạt động ──
t("Nhóm 1: Bảng điều khiển tồn tại", str_contains($sbDash, 'Bảng điều khiển'));
t("Nhóm 2: Quản lý người dùng tồn tại", str_contains($sbDash, 'Quản lý người dùng'));
t("Nhóm 2 có menu con: Danh sách người dùng, Thêm người dùng, Hồ sơ cá nhân", 
    str_contains($sbDash, 'Danh sách người dùng') && 
    str_contains($sbDash, 'Thêm người dùng') && 
    str_contains($sbDash, 'Hồ sơ cá nhân')
);
t("Nhóm 3: Đợt thực tập & đồ án tồn tại", str_contains($sbDash, 'Đợt thực tập &amp; đồ án'));
t("Nhóm 3 có menu con: Danh sách đợt, Tạo đợt mới", 
    str_contains($sbDash, 'Danh sách đợt') && 
    str_contains($sbDash, 'Tạo đợt mới')
);
t("Quản lý đề tài có danh sách, đề xuất và chờ duyệt",
    str_contains($sbDash, 'Quản lý đề tài') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics"') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics/create"') &&
    str_contains($sbDash, 'href="/CNPM/internship-management/public/topics/pending"')
);
t("Admin thấy liên kết phân công dùng route thật",
    str_contains($sbDash, 'Phân công đề tài')
    && str_contains($sbDash, 'href="/CNPM/internship-management/public/assignments"')
    && str_contains($sbDash, 'href="/CNPM/internship-management/public/assignments#assignment-results"')
    && str_contains($sbDash, 'Kết quả phân công')
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
t("Sinh viên: Không thấy mục nhận xét báo cáo dành cho người hướng dẫn", !str_contains($sbStudent, '/report-reviews'));
t("Quản trị viên thuần: Không thấy mục nhật ký/báo cáo dành riêng cho sinh viên", !str_contains($sbDash, 'href="/CNPM/internship-management/public/internship-logs"'));
t("Sinh viên: Có mục Bảng điều khiển active", str_contains($sbStudent, 'Bảng điều khiển'));
t("Sinh viên: Có danh sách đề tài đã được duyệt", str_contains($sbStudent, 'Danh sách đề tài') && str_contains($sbStudent, 'href="/CNPM/internship-management/public/topics"'));
t("Sinh viên: Không còn nhãn 'Sắp có'", !str_contains($sbStudent, 'Sắp có'));
t("Sinh viên: KHÔNG hiển thị chức năng Phân công đề tài và giảng viên", !str_contains($sbStudent, 'Phân công đề tài và giảng viên'));
t("Sinh viên: KHÔNG hiển thị mục Đăng ký đề tài trùng lặp", !str_contains($sbStudent, 'Đăng ký đề tài'));

// Giảng viên
$sbLecturer = renderSidebarForTest(['lecturer'], '/CNPM/internship-management/public/profile', 'profile');
t("Giảng viên: Active đúng mục Hồ sơ cá nhân", str_contains($sbLecturer, 'Hồ sơ cá nhân') && str_contains($sbLecturer, 'aria-current="page"'));
t("Giảng viên: Không còn nhãn 'Sắp có'", !str_contains($sbLecturer, 'Sắp có'));
t("Giảng viên: Có mục Đề tài đã đề xuất", str_contains($sbLecturer, 'Đề tài đã đề xuất'));
t("Giảng viên: Có liên kết nhật ký và nhận xét", str_contains($sbLecturer, 'href="/CNPM/internship-management/public/internship-logs"') && str_contains($sbLecturer, 'href="/CNPM/internship-management/public/report-reviews"'));
t("Giảng viên: KHÔNG hiển thị chức năng Phân công đề tài và giảng viên của Cán bộ khoa", !str_contains($sbLecturer, 'Phân công đề tài và giảng viên'));
t("Giảng viên: KHÔNG hiển thị Đề tài chờ duyệt", !str_contains($sbLecturer, 'Đề tài chờ duyệt'));

// Cán bộ khoa / Trưởng bộ môn
$sbDeptHead = renderSidebarForTest(['department_head'], '/CNPM/internship-management/public/dashboard', 'dashboard');
t("Trưởng bộ môn: Có menu Xét duyệt đề tài và Đề tài chờ duyệt", str_contains($sbDeptHead, 'Xét duyệt đề tài') && str_contains($sbDeptHead, 'Đề tài chờ duyệt'));
t("Trưởng bộ môn thuần: KHÔNG hiển thị Đề xuất đề tài và Đề tài đã đề xuất", 
    !str_contains($sbDeptHead, 'Đề xuất đề tài') && !str_contains($sbDeptHead, 'Đề tài đã đề xuất')
);
$sbDeptHeadLecturer = renderSidebarForTest(['department_head', 'lecturer'], '/CNPM/internship-management/public/dashboard', 'dashboard');
t("Trưởng bộ môn kiêm Giảng viên: Hiển thị Đề xuất đề tài và Đề tài đã đề xuất", 
    str_contains($sbDeptHeadLecturer, 'Đề xuất đề tài') && str_contains($sbDeptHeadLecturer, 'Đề tài đã đề xuất')
);
t("Cán bộ khoa: Có liên kết phân công dùng route thật", str_contains($sbDeptHead, 'href="/CNPM/internship-management/public/assignments"'));
t("Cán bộ khoa: Không thấy mục Đăng ký nguyện vọng", !str_contains($sbDeptHead, 'Đăng ký nguyện vọng'));

// Phòng đào tạo
$sbTraining = renderSidebarForTest(['training_dept'], '/CNPM/internship-management/public/periods', 'periods');
t("Phòng đào tạo: Không thấy menu Quản lý người dùng", !str_contains($sbTraining, 'Quản lý người dùng'));
t("Phòng đào tạo: Thấy menu Đợt thực tập & đồ án", str_contains($sbTraining, 'Đợt thực tập &amp; đồ án'));
t("Phòng đào tạo: Có quản lý đề tài và chờ duyệt", str_contains($sbTraining, 'Quản lý đề tài') && str_contains($sbTraining, 'href="/CNPM/internship-management/public/topics/pending"'));
t("Phòng đào tạo: Có liên kết phân công theo quyền Backend", str_contains($sbTraining, 'href="/CNPM/internship-management/public/assignments"'));
t("Phòng đào tạo: Không còn tiêu đề 'Chức năng mở rộng' và nhãn 'Sắp có'", 
    !str_contains($sbTraining, 'Chức năng mở rộng') && !str_contains($sbTraining, 'Sắp có')
);

$sbAssignments = renderSidebarForTest(['admin'], '/CNPM/internship-management/public/assignments?period_id=1');
preg_match_all('/class="[^"]*nav-link[^"]*\\bactive\\b[^"]*"/', $sbAssignments, $mAssignments);
t("Admin /assignments: Liên kết đúng route là duy nhất mục active",
    count($mAssignments[0]) === 1
    && str_contains($sbAssignments, 'href="/CNPM/internship-management/public/assignments"')
    && str_contains($sbAssignments, 'aria-current="page"')
);

$sbFacultyStaff = renderSidebarForTest(['faculty_staff'], '/CNPM/internship-management/public/assignments');
t("Cán bộ khoa: Có menu phân công theo quyền Backend",
    str_contains($sbFacultyStaff, 'href="/CNPM/internship-management/public/assignments"')
);
$sbLecturerFacultyStaff = renderSidebarForTest(['lecturer', 'faculty_staff'], '/CNPM/internship-management/public/assignments');
t("Tài khoản giảng viên kiêm cán bộ khoa: Có menu phân công theo quyền Backend",
    str_contains($sbLecturerFacultyStaff, 'href="/CNPM/internship-management/public/assignments"')
);

// ── 7. Kiểm tra mục độc lập 'Đăng ký nguyện vọng' và thứ tự hiển thị ───────────
// Khi sinh viên truy cập /wishes
$sbStudentWishes = renderSidebarForTest(['student'], '/CNPM/internship-management/public/wishes', 'wishes');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbStudentWishes, $mWishes);
t("Sinh viên /wishes: Có duy nhất 1 mục active", count($mWishes[0]) === 1);
t("Sinh viên /wishes: Mục active là Đăng ký nguyện vọng", 
    str_contains($sbStudentWishes, 'href="/CNPM/internship-management/public/wishes"') && 
    str_contains($sbStudentWishes, 'aria-current="page"')
);

// Khi sinh viên truy cập form tạo nguyện vọng /wishes/create
$sbStudentWishCreate = renderSidebarForTest(['student'], '/CNPM/internship-management/public/wishes/create', 'wishes');
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbStudentWishCreate, $mWishCreate);
t("Sinh viên /wishes/create: Có duy nhất 1 mục active", count($mWishCreate[0]) === 1);
t("Sinh viên /wishes/create: Mục active là Đăng ký nguyện vọng", 
    str_contains($sbStudentWishCreate, 'href="/CNPM/internship-management/public/wishes"') && 
    str_contains($sbStudentWishCreate, 'aria-current="page"')
);
t("Sinh viên: Mục Đăng ký nguyện vọng sử dụng icon list-ordered/list-numbers chuẩn Tabler", 
    str_contains($sbStudentWishes, 'ti-list-numbers') || str_contains($sbStudentWishes, 'ti-list-ordered')
);

// Kiểm tra thứ tự hiển thị của Sinh viên: "Danh sách đề tài" -> "Đăng ký nguyện vọng"
$posTopics = strpos($sbStudentWishes, 'Danh sách đề tài');
$posWishes = strpos($sbStudentWishes, 'Đăng ký nguyện vọng');
t("Sinh viên: Thứ tự chuẩn: Danh sách đề tài -> Đăng ký nguyện vọng", 
    $posTopics !== false && $posWishes !== false && $posTopics < $posWishes
);

// Kiểm tra RBAC: Các vai trò không có quyền sinh viên không hiển thị menu này
t("RBAC: Quản trị viên thuần không thấy Đăng ký nguyện vọng", !str_contains($sbDash, 'Đăng ký nguyện vọng'));
t("RBAC: Giảng viên thuần không thấy Đăng ký nguyện vọng", !str_contains($sbLecturer, 'Đăng ký nguyện vọng'));
t("RBAC: Phòng đào tạo thuần không thấy Đăng ký nguyện vọng", !str_contains($sbTraining, 'Đăng ký nguyện vọng'));

// Người dùng kiêm vai trò Admin + Student
$sbAdminStudent = renderSidebarForTest(['admin', 'student'], '/CNPM/internship-management/public/dashboard', 'dashboard');
t("RBAC: Tài khoản kiêm Admin + Student thấy Đăng ký nguyện vọng", str_contains($sbAdminStudent, 'Đăng ký nguyện vọng'));

// ── 8. Kiểm tra liên kết Sprint 5 và trạng thái active theo vai trò ─────────
$sprint5NavCases = [
    [['student'], '/internship-logs/detail?id=1', '/internship-logs', 'Sinh viên: Nhật ký active đúng route'],
    [['student'], '/internship-reports/detail?id=1', '/internship-reports', 'Sinh viên: Báo cáo active đúng route'],
    [['lecturer'], '/internship-logs', '/internship-logs', 'Giảng viên: Nhật ký active đúng route'],
    [['lecturer'], '/report-reviews/create?report_id=1', '/report-reviews', 'Giảng viên: Nhận xét active đúng route'],
    [['enterprise'], '/internship-logs', '/internship-logs', 'Doanh nghiệp: Nhật ký active đúng route'],
    [['enterprise'], '/report-reviews', '/report-reviews', 'Doanh nghiệp: Nhận xét active đúng route'],
];
foreach ($sprint5NavCases as [$roles, $path, $href, $description]) {
    $html = renderSidebarForTest($roles, '/CNPM/internship-management/public' . $path);
    preg_match_all('/class="[^"]*nav-link[^"]*\\bactive\\b[^"]*"/', $html, $activeLinks);
    t($description, count($activeLinks[0]) === 1
        && str_contains($html, 'href="/CNPM/internship-management/public' . $href . '"')
        && str_contains($html, 'aria-current="page"'));
}

// ── 8. Kiểm tra chống trùng lặp menu 'Đề tài đã đề xuất' & Vai trò Doanh nghiệp ──
$countAdminPureMy = substr_count($sbDash, 'Đề tài đã đề xuất');
t("RBAC: Quản trị viên thuần KHÔNG thấy mục 'Đề tài đã đề xuất'", $countAdminPureMy === 0);

$sbAdminLecturer = renderSidebarForTest(['admin', 'lecturer'], '/CNPM/internship-management/public/dashboard', 'dashboard');
$countAdminLecturerMy = substr_count($sbAdminLecturer, 'Đề tài đã đề xuất');
t("RBAC: Quản trị viên kiêm Giảng viên thấy mục 'Đề tài đã đề xuất' đúng 1 lần duy nhất", $countAdminLecturerMy === 1);

$countDeptHeadMy = substr_count($sbDeptHead, 'Đề tài đã đề xuất');
t("Trưởng bộ môn thuần: KHÔNG thấy mục 'Đề tài đã đề xuất'", $countDeptHeadMy === 0);

$countLecturerMy = substr_count($sbLecturer, 'Đề tài đã đề xuất');
t("Giảng viên: Mục 'Đề tài đã đề xuất' xuất hiện đúng 1 lần (không trùng lặp)", $countLecturerMy === 1);

// Route /topics?proposer=me: Active chính xác đối với tài khoản có quyền đề xuất
$_GET['proposer'] = 'me';
$sbTopicMy = renderSidebarForTest(['lecturer'], '/CNPM/internship-management/public/topics?proposer=me', 'topics');
unset($_GET['proposer']);
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbTopicMy, $mTopicMy);
t("Route /topics?proposer=me: Có duy nhất 1 mục active", count($mTopicMy[0]) === 1);
t("Route /topics?proposer=me: Mục active đúng là 'Đề tài đã đề xuất'", 
    str_contains($sbTopicMy, 'href="/CNPM/internship-management/public/topics?proposer=me"') &&
    str_contains($sbTopicMy, 'aria-current="page"')
);

// Doanh nghiệp (Enterprise)
$sbEnterprise = renderSidebarForTest(['enterprise'], '/CNPM/internship-management/public/dashboard', 'dashboard');
t("Doanh nghiệp: Có menu Bảng điều khiển, Đề tài thực tập", 
    str_contains($sbEnterprise, 'Bảng điều khiển') && str_contains($sbEnterprise, 'Đề tài thực tập')
);
t("Doanh nghiệp: Có mục 'Đề xuất đề tài thực tập' và 'Đề tài đã đề xuất' (đã gộp tra cứu)", 
    str_contains($sbEnterprise, 'Đề xuất đề tài thực tập') && str_contains($sbEnterprise, 'Đề tài đã đề xuất')
);
t("Doanh nghiệp: Mục 'Đề tài đã đề xuất' xuất hiện đúng 1 lần duy nhất", 
    substr_count($sbEnterprise, 'Đề tài đã đề xuất') === 1
);

// Route /topics?proposer=me đối với Doanh nghiệp: Active đúng mục 'Đề tài đã đề xuất'
$_GET['proposer'] = 'me';
$sbEnterpriseMy = renderSidebarForTest(['enterprise'], '/CNPM/internship-management/public/topics?proposer=me', 'topics');
unset($_GET['proposer']);
preg_match_all('/class="[^"]*nav-link[^"]*\bactive\b[^"]*"/', $sbEnterpriseMy, $mEntMy);
t("Doanh nghiệp /topics?proposer=me: Có duy nhất 1 mục active", count($mEntMy[0]) === 1);
t("Doanh nghiệp /topics?proposer=me: Mục active đúng là 'Đề tài đã đề xuất'", 
    str_contains($sbEnterpriseMy, 'href="/CNPM/internship-management/public/topics?proposer=me"') &&
    str_contains($sbEnterpriseMy, 'aria-current="page"')
);

echo PHP_EOL . "----------------------------------------------" . PHP_EOL;
echo "KẾT QUẢ KIỂM THỬ SIDEBAR: {$passed} PASS, {$failed} FAIL." . PHP_EOL;
echo "==============================================" . PHP_EOL;

if ($failed > 0) {
    exit(1);
}