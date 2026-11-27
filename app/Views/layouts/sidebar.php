<?php
$activePage = $activePage ?? '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Lấy danh sách vai trò từ Session
$currentRoles   = $_SESSION['roles'] ?? ($currentRoles ?? []);
$isAdmin        = in_array('admin',           $currentRoles, true);
$isTrainingDept = in_array('training_dept',   $currentRoles, true);
$isDeptHead     = in_array('department_head', $currentRoles, true);
$isFacultyStaff = in_array('faculty_staff',    $currentRoles, true);
$isLecturer     = in_array('lecturer',        $currentRoles, true);
$isStudent      = in_array('student',         $currentRoles, true);
$isEnterprise   = in_array('enterprise',      $currentRoles, true);

// Phân quyền cơ bản
$canManagePeriods = $isAdmin || $isTrainingDept;
$canManageUsers   = $isAdmin;

// ── Chuẩn hóa Route hiện tại (chính xác tuyệt đối, không nhầm lẫn khi có query / index.php) ──
$rawUri  = $_SERVER['REQUEST_URI'] ?? '/';
$rawPath = parse_url($rawUri, PHP_URL_PATH);
$rawPath = is_string($rawPath) ? $rawPath : '/';

$basePath = rtrim((string) parse_url($baseUrl ?? '', PHP_URL_PATH), '/');
if ($basePath !== '' && ($rawPath === $basePath || str_starts_with($rawPath, $basePath . '/'))) {
    $rawPath = substr($rawPath, strlen($basePath));
}

// Bỏ tiền tố /index.php nếu có
if (str_starts_with($rawPath, '/index.php')) {
    $rawPath = substr($rawPath, strlen('/index.php'));
}

$currentRoute = '/' . trim($rawPath, '/');

// Xác định mục Active và Expanded cho từng nhóm (Độc quyền - chỉ duy nhất 1 mục active)
$isDashboard = ($currentRoute === '/dashboard' || $currentRoute === '/' || ($currentRoute === '' && $activePage === 'dashboard'));

// Nhóm Người dùng
$isUserList   = ($currentRoute === '/users' || $currentRoute === '/users/edit');
$isUserCreate = ($currentRoute === '/users/create');
$isProfile    = ($currentRoute === '/profile');
$isUsersGroup = ($isUserList || $isUserCreate || $isProfile || str_starts_with($currentRoute, '/users'));

// Nhóm Đợt thực tập & Đồ án
$isPeriodList   = ($currentRoute === '/periods' || $currentRoute === '/periods/detail' || $currentRoute === '/periods/edit');
$isPeriodCreate = ($currentRoute === '/periods/create');
$isPeriodsGroup = ($isPeriodList || $isPeriodCreate || str_starts_with($currentRoute, '/periods'));

// Nhóm Đề tài: giữ duy nhất một mục active trên mỗi route.
$isTopicMy = (str_starts_with($currentRoute, '/topics') && isset($_GET['proposer']) && $_GET['proposer'] === 'me');
$isTopicCreate = ($currentRoute === '/topics/create');
$isTopicPending = ($currentRoute === '/topics/pending');
$isTopicBrowse = (str_starts_with($currentRoute, '/topics') && !$isTopicCreate && !$isTopicPending && !$isTopicMy);
$isTopicsGroup = ($isTopicBrowse || $isTopicCreate || $isTopicPending || $isTopicMy);
$isWishes = str_starts_with($currentRoute, '/wishes');
$isMyResult = ($currentRoute === '/assignments/my-result' || $currentRoute === '/assignments/result' || ($activePage ?? '') === 'my-result' || ($activePage ?? '') === 'assignment-result');
$isSupervisorStudents = str_starts_with($currentRoute, '/assignments/my-students') || str_starts_with($currentRoute, '/assignments/supervisor-students');
$isEnterpriseStudents = str_starts_with($currentRoute, '/assignments/enterprise-students');
$isAssignments = str_starts_with($currentRoute, '/assignments') && !$isMyResult && !$isSupervisorStudents && !$isEnterpriseStudents;

if (!function_exists('renderDemoItem')) {
    /**
     * Helper hàm render một mục menu dự kiến sau Sprint 4
     * Hiển thị đồng nhất tên mục và biểu tượng, không kèm badge/tooltip hay hộp thoại popup
     */
    function renderDemoItem(
        string $title,
        string $icon,
        string $status = '',
        string $sprint = '',
        string $desc = '',
        ?string $existing = null,
        ?string $link = null,
        string $linkText = ''
    ): string {
        return '<li class="nav-item">
            <a role="button" tabindex="0" class="nav-link text-reset" href="javascript:void(0)" style="cursor: pointer;">
                <span class="nav-link-icon"><i class="ti ' . $icon . ' fs-2"></i></span>
                <span class="nav-link-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>
            </a>
        </li>';
    }
}
?>
<aside class="navbar navbar-vertical navbar-expand-lg app-sidebar" data-bs-theme="dark">
    <div class="container-fluid">
        <!-- Logo và Tên trường Đại học Vinh -->
        <div class="navbar-brand navbar-brand-autodark px-3 py-3 text-start w-100">
            <a href="<?= $escape($baseUrl) ?>/dashboard" class="d-flex align-items-center text-decoration-none text-reset">
                <img src="<?= $escape($baseUrl) ?>/assets/images/vinhuni-logo.png"
                     alt="Logo Trường Đại học Vinh"
                     class="vinhuni-logo-sidebar me-2 flex-shrink-0">
                <span class="app-brand-title text-white text-truncate">
                    <span class="d-block fw-bold text-uppercase" style="font-size: 0.88rem; letter-spacing: 0.02em;">TRƯỜNG ĐẠI HỌC VINH</span>
                    <small class="text-white-50 fw-semibold d-block" style="font-size: 0.72rem;">QUẢN LÝ THỰC TẬP &amp; ĐỒ ÁN</small>
                </span>
            </a>
        </div>

        <!-- Nút đóng/mở trên màn hình di động -->
        <button class="navbar-toggler d-lg-none me-3" type="button" data-bs-toggle="collapse"
                data-bs-target="#sidebar-menu" aria-controls="sidebar-menu"
                aria-expanded="false" aria-label="Mở hoặc đóng menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <nav aria-label="Điều hướng chính" class="w-100">
                <ul class="navbar-nav pt-lg-2">

                    <?php if ($isAdmin): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: QUẢN TRỊ VIÊN (ADMIN)           -->
                    <!-- ======================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Quản lý người dùng -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-users-menu"
                                aria-controls="sidebar-users-menu" aria-expanded="<?= $isUsersGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý người dùng</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isUsersGroup ? ' show' : '' ?>" id="sidebar-users-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isUserList ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/users"
                                       <?= $isUserList ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách người dùng</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isUserCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/users/create"
                                       <?= $isUserCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-plus fs-2"></i></span>
                                        <span class="nav-link-title">Thêm người dùng</span>
                                    </a>
                                </li>
                                <?= renderDemoItem(
                                    'Nhập tài khoản từ Excel',
                                    'ti-file-spreadsheet',
                                    'chua_trien_khai',
                                    'Mở rộng',
                                    'Cho phép Quản trị viên nhập danh sách sinh viên, giảng viên hàng loạt từ tệp Excel (.xlsx), tự động đối soát tài khoản và cấp mật khẩu mặc định.'
                                ) ?>
                                <?= renderDemoItem(
                                    'Vai trò và phân quyền',
                                    'ti-shield-lock',
                                    'dang_trien_khai',
                                    'Sprint 1 & Mở rộng',
                                    'Phân quyền theo vai trò (Admin, PDT, TBM, GV, SV, Doanh nghiệp). Đã có tính năng gán vai trò trực tiếp trong màn hình Sửa người dùng (/users/edit). Ma trận phân quyền chi tiết động theo từng hành động đang được xây dựng.',
                                    'Đã có: Gán vai trò cho người dùng tại màn hình Quản lý người dùng (/users/edit).',
                                    $baseUrl . '/users',
                                    'Đi đến Quản lý người dùng'
                                ) ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Cấu hình hệ thống (Mở sẵn theo yêu cầu kiểm tra) -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-system-config-menu"
                                aria-controls="sidebar-system-config-menu" aria-expanded="true">
                            <span class="nav-link-icon"><i class="ti ti-settings fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Cấu hình hệ thống</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse show" id="sidebar-system-config-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <?= renderDemoItem(
                                    'Năm học và học kỳ',
                                    'ti-calendar',
                                    'chua_trien_khai',
                                    'Mở rộng',
                                    'Quản lý danh mục Năm học (2024-2025, 2025-2026...) và Học kỳ dùng chung toàn trường. Hiện tại thông tin năm học/học kỳ chỉ được ghi tự do trong Mã đợt và Tên đợt tại màn hình Tạo/Sửa đợt, chưa có màn quản lý danh mục độc lập.',
                                    'Hiện tại: Năm học và học kỳ được ghi trong Mã và Tên đợt tại Quản lý đợt (/periods/create).',
                                    $baseUrl . '/periods',
                                    'Xem danh sách đợt'
                                ) ?>
                                <?= renderDemoItem(
                                    'Ngành và chuyên ngành',
                                    'ti-school',
                                    'chua_trien_khai',
                                    'Mở rộng',
                                    'Quản lý danh mục Khoa, Ngành và Chuyên ngành đào tạo toàn trường, phục vụ phân luồng đề tài và sinh viên đủ điều kiện.'
                                ) ?>
                                <?= renderDemoItem(
                                    'Mẫu biểu',
                                    'ti-template',
                                    'chua_trien_khai',
                                    'Mở rộng',
                                    'Quản lý kho biểu mẫu chuẩn của Nhà trường (phiếu tiếp nhận thực tập, phiếu giao đề tài, phiếu chấm điểm, biên bản bảo vệ).'
                                ) ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 5. Đợt thực tập & đồ án -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-periods-menu"
                                aria-controls="sidebar-periods-menu" aria-expanded="<?= $isPeriodsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Đợt thực tập &amp; đồ án</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isPeriodsGroup ? ' show' : '' ?>" id="sidebar-periods-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isPeriodList ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods"
                                       <?= $isPeriodList ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đợt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isPeriodCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/create"
                                       <?= $isPeriodCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-plus fs-2"></i></span>
                                        <span class="nav-link-title">Tạo đợt mới</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 6. Quản lý đề tài -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu"
                                aria-controls="sidebar-topics-menu" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-list fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đề tài</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/create" <?= $isTopicCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-plus fs-2"></i></span>
                                        <span class="nav-link-title">Đề xuất đề tài</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicPending ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/pending" <?= $isTopicPending ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-checklist fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài chờ duyệt</span>
                                    </a>
                                </li>
                                <?php if ($isLecturer): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicMy ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics?proposer=me" <?= $isTopicMy ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-folder fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài đã đề xuất</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 7. Phân công đề tài & Kết quả -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-assignments-menu-adm"
                                aria-controls="sidebar-assignments-menu-adm" aria-expanded="<?= $isAssignments ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Phân công đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isAssignments ? ' show' : '' ?>" id="sidebar-assignments-menu-adm">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isAssignments ? ' active' : '' ?>"
                                       data-assignment-nav="list"
                                       href="<?= $escape($baseUrl) ?>/assignments"
                                       <?= $isAssignments ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                                        <span class="nav-link-title">Quản lý phân công</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link"
                                       data-assignment-nav="results"
                                       href="<?= $escape($baseUrl) ?>/assignments#assignment-results">
                                        <span class="nav-link-icon"><i class="ti ti-list-check fs-2"></i></span>
                                        <span class="nav-link-title">Kết quả phân công</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 8. Nhật ký hệ thống & Sao lưu -->
                    <?= renderDemoItem(
                        'Nhật ký hệ thống',
                        'ti-history',
                        'chua_trien_khai',
                        'Mở rộng',
                        'Ghi nhận nhật ký kiểm toán (audit log) toàn hệ thống: ai đã thêm/sửa/xóa tài khoản, duyệt đề tài, kích hoạt phân công vào thời điểm nào.',
                        'Đã có: Lưu vết người duyệt trong bảng topic_approvals và người phân công trong topic_assignments.'
                    ) ?>
                    <?= renderDemoItem(
                        'Sao lưu và phục hồi',
                        'ti-database-export',
                        'chua_trien_khai',
                        'Mở rộng',
                        'Tạo bản sao lưu dữ liệu toàn bộ hệ thống định kỳ (dump database), khôi phục dữ liệu khi có sự cố và chốt đóng dữ liệu cuối kỳ.'
                    ) ?>

                    <?php if ($isStudent): ?>
                    <!-- Phần tích hợp khi tài khoản kiêm nhiệm Sinh viên -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isWishes ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/wishes"
                           <?= $isWishes ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-list-numbers ti-list-ordered fs-2"></i></span>
                            <span class="nav-link-title">Nguyện vọng của tôi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isMyResult ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/assignments/my-result"
                           <?= $isMyResult ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-check fs-2"></i></span>
                            <span class="nav-link-title">Kết quả phân công</span>
                        </a>
                    </li>
                    <?= renderDemoItem('Tiến độ của tôi', 'ti-timeline', 'chua_trien_khai', 'Sprint 5', 'Xem tiến độ thực hiện đồ án và các mốc công việc (milestones) cần hoàn thành.') ?>
                    <?= renderDemoItem('Nhật ký thực tập', 'ti-notebook', 'chua_trien_khai', 'Sprint 5', 'Viết và cập nhật nhật ký thực tập/đồ án hằng tuần.') ?>
                    <?= renderDemoItem('Nộp báo cáo', 'ti-upload', 'chua_trien_khai', 'Sprint 5', 'Nộp bài báo cáo định kỳ theo các đợt nộp.') ?>
                    <?= renderDemoItem('Kết quả đánh giá', 'ti-award', 'chua_trien_khai', 'Sprint 6', 'Xem kết quả điểm quá trình, điểm phản biện và hội đồng bảo vệ.') ?>
                    <?php endif; ?>

                    <?php elseif ($isTrainingDept): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: PHÒNG ĐÀO TẠO (training_dept)   -->
                    <!-- ======================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Quản lý đợt thực tập và đồ án -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-periods-menu-pdt"
                                aria-controls="sidebar-periods-menu-pdt" aria-expanded="<?= $isPeriodsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Đợt thực tập &amp; đồ án</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isPeriodsGroup ? ' show' : '' ?>" id="sidebar-periods-menu-pdt">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isPeriodList ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods"
                                       <?= $isPeriodList ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đợt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isPeriodCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/create"
                                       <?= $isPeriodCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-plus fs-2"></i></span>
                                        <span class="nav-link-title">Tạo đợt mới</span>
                                    </a>
                                </li>
                                <?= renderDemoItem(
                                    'Điều kiện tham gia',
                                    'ti-checkup-list',
                                    'chua_trien_khai',
                                    'Sprint 2',
                                    'Cấu hình tiêu chí điều kiện tham gia (số tín chỉ tích lũy tối thiểu, điều kiện tiên quyết môn học) cho từng đợt thực tập hoặc đồ án.'
                                ) ?>
                                <?= renderDemoItem(
                                    'Danh sách sinh viên đủ điều kiện',
                                    'ti-user-check',
                                    'chua_trien_khai',
                                    'Sprint 2',
                                    'Quản lý và xét duyệt danh sách sinh viên đủ tiêu chuẩn tham gia nộp nguyện vọng trong đợt.'
                                ) ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Quản lý đề tài -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-pdt"
                                aria-controls="sidebar-topics-menu-pdt" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-pdt">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicPending ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/pending" <?= $isTopicPending ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-checklist fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài chờ duyệt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-list fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đề tài</span>
                                    </a>
                                </li>
                                <?php if ($isLecturer): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/create" <?= $isTopicCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-plus fs-2"></i></span>
                                        <span class="nav-link-title">Đề xuất đề tài</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicMy ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics?proposer=me" <?= $isTopicMy ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-folder fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài đã đề xuất</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 5. Phân công và công bố kết quả -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-assignments-menu-pdt"
                                aria-controls="sidebar-assignments-menu-pdt" aria-expanded="<?= $isAssignments ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Phân công &amp; công bố kết quả</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isAssignments ? ' show' : '' ?>" id="sidebar-assignments-menu-pdt">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isAssignments ? ' active' : '' ?>"
                                       data-assignment-nav="list"
                                       href="<?= $escape($baseUrl) ?>/assignments"
                                       <?= $isAssignments ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-arrows-shuffle fs-2"></i></span>
                                        <span class="nav-link-title">Quản lý phân công</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link"
                                       data-assignment-nav="results"
                                       href="<?= $escape($baseUrl) ?>/assignments#assignment-results">
                                        <span class="nav-link-icon"><i class="ti ti-speakerphone fs-2"></i></span>
                                        <span class="nav-link-title">Công bố kết quả</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 6. Thông báo, Lịch bảo vệ, Thống kê -->
                    <?= renderDemoItem(
                        'Thông báo',
                        'ti-bell',
                        'chua_trien_khai',
                        'Sprint 5/6',
                        'Gửi thông báo toàn trường theo đợt, nhắc hạn nộp báo cáo, lịch họp hội đồng và gửi email thông báo tự động.'
                    ) ?>
                    <?= renderDemoItem(
                        'Lịch bảo vệ',
                        'ti-calendar-time',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Bố trí lịch bảo vệ đồ án tốt nghiệp, phân phòng thi và phân bổ danh sách sinh viên bảo vệ theo hội đồng.'
                    ) ?>
                    <?= renderDemoItem(
                        'Thống kê và báo cáo',
                        'ti-chart-bar',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Báo cáo thống kê toàn diện: số đề tài đăng ký, tỷ lệ sinh viên được ghép đúng nguyện vọng 1/2/3, phân bố điểm đánh giá và xuất báo cáo PDF/Excel.'
                    ) ?>

                    <?php elseif ($isDeptHead || $isFacultyStaff): ?>
                    <!-- ======================================================== -->
                    <!-- VAI TRÒ: CÁN BỘ KHOA / TRƯỞNG BỘ MÔN (department_head)    -->
                    <!-- ======================================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Xét duyệt & Đề tài của bộ môn -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-depthead"
                                aria-controls="sidebar-topics-menu-depthead" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Xét duyệt đề tài &amp; bộ môn</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-depthead">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicPending ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/pending" <?= $isTopicPending ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-checklist fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài chờ duyệt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-list fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài của bộ môn</span>
                                    </a>
                                </li>
                                <?php if ($isLecturer): ?>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/create" <?= $isTopicCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-plus fs-2"></i></span>
                                        <span class="nav-link-title">Đề xuất đề tài</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicMy ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics?proposer=me" <?= $isTopicMy ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-folder fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài đã đề xuất</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Phân công của bộ môn -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-assignments-menu-depthead"
                                aria-controls="sidebar-assignments-menu-depthead" aria-expanded="<?= $isAssignments ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Phân công của bộ môn</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isAssignments ? ' show' : '' ?>" id="sidebar-assignments-menu-depthead">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isAssignments ? ' active' : '' ?>"
                                       data-assignment-nav="list"
                                       href="<?= $escape($baseUrl) ?>/assignments"
                                       <?= $isAssignments ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                                        <span class="nav-link-title">Phân công của bộ môn</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= ($currentRoute === '/assignments/create') ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/assignments/create">
                                        <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                                        <span class="nav-link-title">Bố trí GVHD / GVPB</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 5. Xét duyệt nơi thực tập & Tiến độ bộ môn -->
                    <?= renderDemoItem(
                        'Xét duyệt nơi thực tập',
                        'ti-building-check',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Xét duyệt đơn vị thực tập doanh nghiệp do sinh viên tự tìm kiếm và đề xuất.'
                    ) ?>
                    <?= renderDemoItem(
                        'Tiến độ của bộ môn',
                        'ti-chart-line',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Theo dõi tiến độ thực hiện đề tài và nộp báo cáo định kỳ của toàn bộ sinh viên thuộc bộ môn quản lý.'
                    ) ?>

                    <?php elseif ($isLecturer): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: GIẢNG VIÊN (lecturer)           -->
                    <!-- ======================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Quản lý đề tài -->
                    <?php if ($isFacultyStaff): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $isAssignments ? ' active' : '' ?>"
                           data-assignment-nav="list"
                           href="<?= $escape($baseUrl) ?>/assignments"
                           <?= $isAssignments ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title">Phân công của bộ môn</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-lecturer"
                                aria-controls="sidebar-topics-menu-lecturer" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-lecturer">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/create" <?= $isTopicCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-plus fs-2"></i></span>
                                        <span class="nav-link-title">Đề xuất đề tài</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicMy ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics?proposer=me" <?= $isTopicMy ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-folder fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài đã đề xuất</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-list fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đề tài</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Sinh viên được phân công & Tiến độ hướng dẫn -->
                    <li class="nav-item">
                        <a class="nav-link<?= str_starts_with($currentRoute, '/assignments/my-students') ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/assignments/my-students">
                            <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                            <span class="nav-link-title">Sinh viên được phân công</span>
                        </a>
                    </li>
                    <?= renderDemoItem(
                        'Mốc tiến độ',
                        'ti-milestone',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Thiết lập kế hoạch làm việc, mốc công việc (milestones) định kỳ cho từng sinh viên/nhóm thực hiện đồ án.'
                    ) ?>
                    <?= renderDemoItem(
                        'Nhật ký và báo cáo',
                        'ti-notebook',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Xem và xác nhận nhật ký gặp mặt/hướng dẫn, đọc bài nộp báo cáo tiến độ và góp ý trực tuyến cho sinh viên.'
                    ) ?>
                    <?= renderDemoItem(
                        'Chấm hướng dẫn',
                        'ti-certificate',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Phiếu đánh giá, nhận xét và chấm điểm quá trình thực hiện đồ án của sinh viên theo rubric.'
                    ) ?>
                    <?= renderDemoItem(
                        'Chấm phản biện',
                        'ti-award',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Phiếu nhận xét và chấm điểm phản biện độc lập cho các đề tài đồ án được phân công.'
                    ) ?>

                    <?php elseif ($isStudent): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: SINH VIÊN (student)             -->
                    <!-- ======================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Đợt thực tập và đồ án -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $escape($baseUrl) ?>/dashboard#current-periods">
                            <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                            <span class="nav-link-title">Đợt thực tập và đồ án</span>
                        </a>
                    </li>

                    <!-- 4. Danh sách đề tài -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title">Danh sách đề tài</span>
                        </a>
                    </li>

                    <!-- 5. Nguyện vọng của tôi -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isWishes ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/wishes"
                           <?= $isWishes ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-list-numbers ti-list-ordered fs-2"></i></span>
                            <span class="nav-link-title">Nguyện vọng của tôi</span>
                        </a>
                    </li>

                    <!-- 6. Đăng ký nơi thực tập & Kết quả phân công -->
                    <?= renderDemoItem(
                        'Đăng ký hoặc khai báo nơi thực tập',
                        'ti-building',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Khai báo thông tin đơn vị doanh nghiệp tiếp nhận thực tập do sinh viên tự liên hệ để bộ môn xét duyệt.'
                    ) ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $isMyResult ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/assignments/my-result"
                           <?= $isMyResult ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-check fs-2"></i></span>
                            <span class="nav-link-title">Kết quả phân công</span>
                        </a>
                    </li>

                    <!-- 7. Tiến độ, Nhật ký, Báo cáo, Đánh giá -->
                    <?= renderDemoItem(
                        'Tiến độ của tôi',
                        'ti-timeline',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Xem kế hoạch thực hiện đồ án/thực tập, các mốc thời gian (milestones) cần hoàn thành.'
                    ) ?>
                    <?= renderDemoItem(
                        'Nhật ký thực tập',
                        'ti-notebook',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Viết nhật ký công việc hằng tuần, ghi nhận các khó khăn và nhận phản hồi xác nhận từ người hướng dẫn.'
                    ) ?>
                    <?= renderDemoItem(
                        'Nộp báo cáo',
                        'ti-upload',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Upload file báo cáo đề cương, báo cáo giữa kỳ và báo cáo tổng kết theo các đợt nộp.'
                    ) ?>
                    <?= renderDemoItem(
                        'Kết quả đánh giá',
                        'ti-award',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Xem điểm số quá trình, điểm phản biện, điểm hội đồng và nhận xét đánh giá chi tiết theo rubric.'
                    ) ?>

                    <?php elseif ($isEnterprise): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: DOANH NGHIỆP (enterprise)       -->
                    <!-- ======================================== -->

                    <!-- 1. Bảng điều khiển -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <!-- 2. Hồ sơ doanh nghiệp -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-building fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ doanh nghiệp</span>
                        </a>
                    </li>

                    <!-- 3. Đề tài thực tập -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-enterprise"
                                aria-controls="sidebar-topics-menu-enterprise" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Đề tài thực tập</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-enterprise">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $isTopicCreate ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics/create" <?= $isTopicCreate ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-plus fs-2"></i></span>
                                        <span class="nav-link-title">Đề xuất đề tài thực tập</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= ($isTopicMy || $isTopicBrowse) ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/topics?proposer=me" <?= ($isTopicMy || $isTopicBrowse) ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-folder fs-2"></i></span>
                                        <span class="nav-link-title">Đề tài đã đề xuất</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Vị trí thực tập & Ứng tuyển -->
                    <?= renderDemoItem(
                        'Vị trí thực tập',
                        'ti-briefcase',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Đăng thông tin tuyển dụng, chỉ tiêu tiếp nhận và yêu cầu kỹ năng cho các vị trí thực tập tại doanh nghiệp.'
                    ) ?>
                    <?= renderDemoItem(
                        'Hồ sơ ứng tuyển',
                        'ti-id',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Duyệt danh sách hồ sơ sinh viên ứng tuyển vào vị trí thực tập tại doanh nghiệp.'
                    ) ?>

                    <!-- 5. Sinh viên thực tập & Theo dõi -->
                    <li class="nav-item">
                        <a class="nav-link<?= str_starts_with($currentRoute, '/assignments/enterprise-students') ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/assignments/enterprise-students">
                            <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                            <span class="nav-link-title">Sinh viên thực tập được giao</span>
                        </a>
                    </li>
                    <?= renderDemoItem(
                        'Xác nhận nhật ký',
                        'ti-check',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Xác nhận chuyên cần và ký nhận xét nhật ký làm việc hằng tuần của sinh viên tại đơn vị.'
                    ) ?>
                    <?= renderDemoItem(
                        'Nhận xét báo cáo',
                        'ti-message-dots',
                        'chua_trien_khai',
                        'Sprint 5',
                        'Nhận xét tiến độ và góp ý trực tuyến cho các bản báo cáo thực tập của sinh viên.'
                    ) ?>
                    <?= renderDemoItem(
                        'Đánh giá thực tập',
                        'ti-stars',
                        'chua_trien_khai',
                        'Sprint 6',
                        'Phiếu đánh giá trực tuyến của người hướng dẫn tại doanh nghiệp (thái độ, kỷ luật, năng lực chuyên môn).'
                    ) ?>

                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </div>
</aside>

<script>
(() => {
    // Chỉ kích hoạt xử lý chuyển tab theo hash khi đang ở trang phân công đề tài (/assignments)
    if (window.location.pathname.includes('/assignments')) {
        const assignmentLinks = document.querySelectorAll('[data-assignment-nav]');
        if (assignmentLinks.length) {
            const updateAssignmentNav = () => {
                const selected = window.location.hash === '#assignment-results' ? 'results' : 'list';
                assignmentLinks.forEach((link) => {
                    const active = link.dataset.assignmentNav === selected;
                    link.classList.toggle('active', active);
                    if (active) link.setAttribute('aria-current', 'page');
                    else link.removeAttribute('aria-current');
                });
            };
            updateAssignmentNav();
            window.addEventListener('hashchange', updateAssignmentNav);
        }
    }
})();
</script>
