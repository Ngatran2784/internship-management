<?php
$activePage = $activePage ?? '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Lấy danh sách vai trò từ Session
$currentRoles   = $_SESSION['roles'] ?? ($currentRoles ?? []);
$isAdmin        = in_array('admin',           $currentRoles, true);
$isTrainingDept = in_array('training_dept',   $currentRoles, true);
$isDeptHead     = in_array('department_head', $currentRoles, true);
$isLecturer     = in_array('lecturer',        $currentRoles, true);
$isStudent      = in_array('student',         $currentRoles, true);

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
$isTopicCreate = ($currentRoute === '/topics/create');
$isTopicPending = ($currentRoute === '/topics/pending');
$isTopicBrowse = (str_starts_with($currentRoute, '/topics') && !$isTopicCreate && !$isTopicPending);
$isTopicsGroup = ($isTopicBrowse || $isTopicCreate || $isTopicPending);
$isWishes = str_starts_with($currentRoute, '/wishes');
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

                    <!-- 2. Quản lý người dùng -->
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
                                <li class="nav-item">
                                    <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/profile"
                                       <?= $isProfile ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                                        <span class="nav-link-title">Thông tin cá nhân</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- 3. Đợt thực tập & đồ án -->
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

                    <!-- 4. Quản lý vai trò và phân quyền -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-shield-lock fs-2"></i></span>
                            <span class="nav-link-title">Quản lý vai trò và phân quyền</span>
                        </span>
                    </li>

                    <!-- 5. Quản lý, phê duyệt đề tài -->
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
                            </ul>
                        </div>
                    </li>

                    <?php if ($isStudent): ?>
                    <!-- Đăng ký nguyện vọng (nếu tài khoản kiêm vai trò sinh viên) -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isWishes ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/wishes"
                           <?= $isWishes ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-list-numbers ti-list-ordered fs-2"></i></span>
                            <span class="nav-link-title">Đăng ký nguyện vọng</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- 6. Phân công đề tài và giảng viên -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title">Phân công đề tài và giảng viên</span>
                        </span>
                    </li>

                    <!-- 7. Quản lý đơn vị thực tập -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-building-skyscraper fs-2"></i></span>
                            <span class="nav-link-title">Quản lý đơn vị thực tập</span>
                        </span>
                    </li>

                    <!-- 8. Thống kê và báo cáo -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-chart-bar fs-2"></i></span>
                            <span class="nav-link-title">Thống kê và báo cáo</span>
                        </span>
                    </li>

                    <?php elseif ($isDeptHead): ?>
                    <!-- ======================================================== -->
                    <!-- VAI TRÒ: CÁN BỘ KHOA / TRƯỞNG BỘ MÔN (department_head)    -->
                    <!-- ======================================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Thông tin cá nhân</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                            <span class="nav-link-title">Quản lý đợt thực tập &amp; đồ án</span>
                        </span>
                    </li>
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-depthead"
                                aria-controls="sidebar-topics-menu-depthead" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-depthead">
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
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-user-share fs-2"></i></span>
                            <span class="nav-link-title">Phân công đề tài và giảng viên</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-list-check fs-2"></i></span>
                            <span class="nav-link-title">Theo dõi tiến độ</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-chart-bar fs-2"></i></span>
                            <span class="nav-link-title">Thống kê và báo cáo</span>
                        </span>
                    </li>

                    <?php elseif ($canManagePeriods): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: PHÒNG ĐÀO TẠO (training_dept)   -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

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

                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Thông tin cá nhân</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title">Quản lý đề tài</span>
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
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-chart-bar fs-2"></i></span>
                            <span class="nav-link-title">Thống kê và báo cáo</span>
                        </span>
                    </li>

                    <?php elseif ($isLecturer): ?>
                    <!-- ======================================== -->
                    <!-- VAI TRÒ: GIẢNG VIÊN (lecturer)           -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Thông tin cá nhân</span>
                        </a>
                    </li>
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
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-book fs-2"></i></span>
                            <span class="nav-link-title">Đề tài hướng dẫn</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-list-check fs-2"></i></span>
                            <span class="nav-link-title">Theo dõi sinh viên</span>
                        </span>
                    </li>

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

                    <!-- 2. Thông tin cá nhân -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Thông tin cá nhân</span>
                        </a>
                    </li>

                    <!-- 3. Quản lý đề tài (chỉ mục con sinh viên được xem) -->
                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-topics-menu-student"
                                aria-controls="sidebar-topics-menu-student" aria-expanded="<?= $isTopicsGroup ? 'true' : 'false' ?>">
                            <span class="nav-link-icon"><i class="ti ti-file-certificate fs-2"></i></span>
                            <span class="nav-link-title sidebar-group-title">Quản lý đề tài</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron ms-auto" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $isTopicsGroup ? ' show' : '' ?>" id="sidebar-topics-menu-student">
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
                            </ul>
                        </div>
                    </li>

                    <!-- 4. Đăng ký nguyện vọng (Menu chính độc lập) -->
                    <li class="nav-item">
                        <a class="nav-link<?= $isWishes ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/wishes"
                           <?= $isWishes ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-list-numbers ti-list-ordered fs-2"></i></span>
                            <span class="nav-link-title">Đăng ký nguyện vọng</span>
                        </a>
                    </li>

                    <!-- 5. Xem đợt thực tập -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-calendar-time fs-2"></i></span>
                            <span class="nav-link-title">Xem đợt thực tập</span>
                        </span>
                    </li>

                    <!-- 6. Theo dõi tiến độ -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-list-check fs-2"></i></span>
                            <span class="nav-link-title">Theo dõi tiến độ</span>
                        </span>
                    </li>

                    <!-- 7. Nộp báo cáo -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-upload fs-2"></i></span>
                            <span class="nav-link-title">Nộp báo cáo</span>
                        </span>
                    </li>

                    <!-- 8. Kết quả đánh giá -->
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-award fs-2"></i></span>
                            <span class="nav-link-title">Kết quả đánh giá</span>
                        </span>
                    </li>

                    <?php else: ?>
                    <!-- ======================================== -->
                    <!-- CÁC VAI TRÒ KHÁC                         -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $isDashboard ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isProfile ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $isProfile ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Thông tin cá nhân</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $isTopicBrowse ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/topics" <?= $isTopicBrowse ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-files fs-2"></i></span>
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
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-info-circle fs-2"></i></span>
                            <span class="nav-link-title">Chức năng theo vai trò</span>
                        </span>
                    </li>

                    <?php endif; ?>

                </ul>
            </nav>
        </div>
    </div>
</aside>