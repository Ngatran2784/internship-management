<?php
$activePage = $activePage ?? '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Lấy danh sách vai trò từ Session hoặc biến đã truyền từ Header
$currentRoles = $_SESSION['roles'] ?? ($currentRoles ?? []);
$isAdmin = in_array('admin', $currentRoles, true);
$isStudent = in_array('student', $currentRoles, true);
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

        <button class="navbar-toggler d-lg-none me-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Mở hoặc đóng menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="sidebar-menu">
            <nav aria-label="Điều hướng chính" class="w-100">
                <ul class="navbar-nav pt-lg-2">
                    <?php if ($isAdmin): ?>
                        <!-- ========================================== -->
                        <!-- MENU DÀNH CHO QUẢN TRỊ VIÊN (ADMIN)        -->
                        <!-- ========================================== -->
                        <li class="nav-item">
                            <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= $escape($baseUrl) ?>/dashboard"<?= $activePage === 'dashboard' ? ' aria-current="page"' : '' ?>>
                                <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                                <span class="nav-link-title">Bảng điều khiển</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-users fs-2"></i></span>
                                <span class="nav-link-title">Quản lý người dùng</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-shield-lock fs-2"></i></span>
                                <span class="nav-link-title">Quản lý vai trò và phân quyền</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                                <span class="nav-link-title">Quản lý đợt thực tập và đồ án</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-book fs-2"></i></span>
                                <span class="nav-link-title">Quản lý, phê duyệt đề tài</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-user-check fs-2"></i></span>
                                <span class="nav-link-title">Phân công đề tài và giảng viên</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-building fs-2"></i></span>
                                <span class="nav-link-title">Quản lý đơn vị thực tập</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-chart-bar fs-2"></i></span>
                                <span class="nav-link-title">Thống kê và báo cáo</span>
                            </span>
                        </li>

                    <?php elseif ($isStudent): ?>
                        <!-- ========================================== -->
                        <!-- MENU DÀNH CHO SINH VIÊN (STUDENT)          -->
                        <!-- ========================================== -->
                        <li class="nav-item">
                            <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= $escape($baseUrl) ?>/dashboard"<?= $activePage === 'dashboard' ? ' aria-current="page"' : '' ?>>
                                <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                                <span class="nav-link-title">Bảng điều khiển</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-user fs-2"></i></span>
                                <span class="nav-link-title">Hồ sơ cá nhân</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-calendar-time fs-2"></i></span>
                                <span class="nav-link-title">Xem đợt thực tập và đồ án</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-file-pencil fs-2"></i></span>
                                <span class="nav-link-title">Đăng ký đề tài, nguyện vọng</span>
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
                                <span class="nav-link-title">Nhật ký và nộp báo cáo</span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true">
                                <span class="nav-link-icon"><i class="ti ti-award fs-2"></i></span>
                                <span class="nav-link-title">Xem kết quả đánh giá</span>
                            </span>
                        </li>

                    <?php else: ?>
                        <!-- ========================================== -->
                        <!-- MENU MẶC ĐỊNH CHO THÀNH VIÊN KHÁC          -->
                        <!-- ========================================== -->
                        <li class="nav-item">
                            <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= $escape($baseUrl) ?>/dashboard"<?= $activePage === 'dashboard' ? ' aria-current="page"' : '' ?>>
                                <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                                <span class="nav-link-title">Bảng điều khiển</span>
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
