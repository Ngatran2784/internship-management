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

// Admin có đủ mọi quyền; training_dept quản lý đợt
$canManagePeriods = $isAdmin || $isTrainingDept;
$canManageUsers   = $isAdmin;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$currentPath = is_string($currentPath) ? $currentPath : '/';
$basePath = rtrim((string) parse_url($baseUrl ?? '', PHP_URL_PATH), '/');
if ($basePath !== '' && ($currentPath === $basePath || str_starts_with($currentPath, $basePath . '/'))) {
    $currentPath = substr($currentPath, strlen($basePath));
}
$currentPath = '/' . trim($currentPath, '/');
$recordId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$hasRecordId = is_int($recordId) && $recordId > 0;
$hasUserId = $currentPath === '/users/edit' && $hasRecordId;
$hasPeriodId = in_array($currentPath, ['/periods/edit', '/periods/detail'], true) && $hasRecordId;
$userId = $hasUserId ? $recordId : null;
$periodId = $hasPeriodId ? $recordId : null;
$userListActive = $currentPath === '/users';
$userCreateActive = $currentPath === '/users/create';
$userEditActive = $currentPath === '/users/edit' && $hasUserId;
$userProfileActive = $currentPath === '/profile';
$usersGroupOpen = $userListActive || $userCreateActive || $userEditActive || $userProfileActive;
$periodListActive = $currentPath === '/periods';
$periodCreateActive = $currentPath === '/periods/create';
$periodEditActive = $currentPath === '/periods/edit' && $hasPeriodId;
$periodDetailActive = $currentPath === '/periods/detail' && $hasPeriodId;
$periodsGroupOpen = $periodListActive || $periodCreateActive || $periodEditActive || $periodDetailActive;
?>
<aside class="navbar navbar-vertical navbar-expand-lg app-sidebar" data-bs-theme="dark">
    <div class="container-fluid">
        <div class="navbar-brand navbar-brand-autodark px-3 py-3 text-start w-100">
            <a href="<?= $escape($baseUrl) ?>/dashboard" class="d-flex align-items-center text-decoration-none text-reset">
                <span class="avatar avatar-sm bg-primary text-white rounded me-2 flex-shrink-0">
                    <i class="ti ti-school fs-2"></i>
                </span>
                <span class="app-brand-title text-white">
                    <span class="d-block">QL THỰC TẬP &amp; ĐỒ ÁN</span>
                    <small class="text-white-50 fw-normal">Trường Đại học</small>
                </span>
            </a>
        </div>

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
                    <!-- ADMIN                                     -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-users-menu"
                                aria-controls="sidebar-users-menu" aria-expanded="<?= $usersGroupOpen ? 'true' : 'false' ?>">
                            <span class="sidebar-group-title">Quản lý người dùng</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $usersGroupOpen ? ' show' : '' ?>" id="sidebar-users-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $userListActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/users"
                                       <?= $userListActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách người dùng</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $userCreateActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/users/create"
                                       <?= $userCreateActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-plus fs-2"></i></span>
                                        <span class="nav-link-title">Thêm người dùng</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <?php if ($hasUserId): ?>
                                    <a class="nav-link<?= $userEditActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/users/edit?id=<?= $userId ?>"
                                       <?= $userEditActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa người dùng</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true" title="Mở chỉnh sửa từ danh sách người dùng để chọn bản ghi">
                                        <span class="nav-link-icon"><i class="ti ti-user-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa người dùng</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $userProfileActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/profile"
                                       <?= $userProfileActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                                        <span class="nav-link-title">Thông tin cá nhân</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-periods-menu"
                                aria-controls="sidebar-periods-menu" aria-expanded="<?= $periodsGroupOpen ? 'true' : 'false' ?>">
                            <span class="sidebar-group-title">Đợt thực tập &amp; Đồ án</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $periodsGroupOpen ? ' show' : '' ?>" id="sidebar-periods-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $periodListActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods"
                                       <?= $periodListActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đợt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $periodCreateActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/create"
                                       <?= $periodCreateActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-plus fs-2"></i></span>
                                        <span class="nav-link-title">Tạo đợt mới</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <?php if ($hasPeriodId): ?>
                                    <a class="nav-link<?= $periodEditActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/edit?id=<?= $periodId ?>"
                                       <?= $periodEditActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa đợt</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true" title="Mở chi tiết đợt từ danh sách để chọn bản ghi">
                                        <span class="nav-link-icon"><i class="ti ti-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa đợt</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                                <li class="nav-item">
                                    <?php if ($hasPeriodId): ?>
                                    <a class="nav-link<?= $periodDetailActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/detail?id=<?= $periodId ?>"
                                       <?= $periodDetailActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-file-description fs-2"></i></span>
                                        <span class="nav-link-title">Chi tiết đợt</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true" title="Mở chi tiết đợt từ danh sách để chọn bản ghi">
                                        <span class="nav-link-icon"><i class="ti ti-file-description fs-2"></i></span>
                                        <span class="nav-link-title">Chi tiết đợt</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Quản lý vai trò và phân quyền
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Quản lý, phê duyệt đề tài
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Phân công đề tài và giảng viên
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Quản lý đơn vị thực tập
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Thống kê và báo cáo
                        </div>
                    </li>

                    <?php elseif ($canManagePeriods): ?>
                    <!-- ======================================== -->
                    <!-- PHÒNG ĐÀO TẠO (training_dept)            -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>

                    <li class="nav-item sidebar-nav-group">
                        <button class="nav-link sidebar-group-toggle" type="button"
                                data-bs-toggle="collapse" data-bs-target="#sidebar-periods-menu"
                                aria-controls="sidebar-periods-menu" aria-expanded="<?= $periodsGroupOpen ? 'true' : 'false' ?>">
                            <span class="sidebar-group-title">Đợt thực tập &amp; Đồ án</span>
                            <i class="ti ti-chevron-down sidebar-group-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="collapse<?= $periodsGroupOpen ? ' show' : '' ?>" id="sidebar-periods-menu">
                            <ul class="navbar-nav sidebar-submenu">
                                <li class="nav-item">
                                    <a class="nav-link<?= $periodListActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods"
                                       <?= $periodListActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                                        <span class="nav-link-title">Danh sách đợt</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link<?= $periodCreateActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/create"
                                       <?= $periodCreateActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-calendar-plus fs-2"></i></span>
                                        <span class="nav-link-title">Tạo đợt mới</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <?php if ($hasPeriodId): ?>
                                    <a class="nav-link<?= $periodEditActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/edit?id=<?= $periodId ?>"
                                       <?= $periodEditActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa đợt</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true" title="Mở chi tiết đợt từ danh sách để chọn bản ghi">
                                        <span class="nav-link-icon"><i class="ti ti-edit fs-2"></i></span>
                                        <span class="nav-link-title">Chỉnh sửa đợt</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                                <li class="nav-item">
                                    <?php if ($hasPeriodId): ?>
                                    <a class="nav-link<?= $periodDetailActive ? ' active' : '' ?>"
                                       href="<?= $escape($baseUrl) ?>/periods/detail?id=<?= $periodId ?>"
                                       <?= $periodDetailActive ? 'aria-current="page"' : '' ?>>
                                        <span class="nav-link-icon"><i class="ti ti-file-description fs-2"></i></span>
                                        <span class="nav-link-title">Chi tiết đợt</span>
                                    </a>
                                    <?php else: ?>
                                    <span class="nav-link disabled" aria-disabled="true" title="Mở chi tiết đợt từ danh sách để chọn bản ghi">
                                        <span class="nav-link-icon"><i class="ti ti-file-description fs-2"></i></span>
                                        <span class="nav-link-title">Chi tiết đợt</span>
                                    </span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <div class="nav-link text-white-50 small fw-bold text-uppercase px-3 pt-3 pb-1"
                             style="font-size:.68rem;letter-spacing:.08em;pointer-events:none;">
                            Tài khoản
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>

                    <?php elseif ($isStudent): ?>
                    <!-- ======================================== -->
                    <!-- SINH VIÊN                                 -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-calendar-time fs-2"></i></span>
                            <span class="nav-link-title">Xem đợt thực tập</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-file-pencil fs-2"></i></span>
                            <span class="nav-link-title">Đăng ký đề tài</span>
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
                            <span class="nav-link-icon"><i class="ti ti-upload fs-2"></i></span>
                            <span class="nav-link-title">Nộp báo cáo</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-award fs-2"></i></span>
                            <span class="nav-link-title">Kết quả đánh giá</span>
                        </span>
                    </li>

                    <?php elseif ($isLecturer || $isDeptHead): ?>
                    <!-- ======================================== -->
                    <!-- GIẢNG VIÊN / TRƯỞNG BỘ MÔN               -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
                        </a>
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

                    <?php else: ?>
                    <!-- ======================================== -->
                    <!-- THÀNH VIÊN KHÁC (mặc định)               -->
                    <!-- ======================================== -->

                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/dashboard"
                           <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>"
                           href="<?= $escape($baseUrl) ?>/profile"
                           <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-user-circle fs-2"></i></span>
                            <span class="nav-link-title">Hồ sơ cá nhân</span>
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
