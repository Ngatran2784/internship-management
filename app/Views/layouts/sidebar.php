<?php
$activePage = $activePage ?? '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<aside class="navbar navbar-vertical navbar-expand-lg app-sidebar" data-bs-theme="dark">
    <div class="container-fluid">
        <div class="navbar-brand navbar-brand-autodark px-3 py-3 text-start w-100">
            <a href="<?= $escape($baseUrl) ?>/" class="d-flex align-items-center text-decoration-none text-reset">
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
                    <li class="nav-item">
                        <a class="nav-link<?= $activePage === 'dashboard' ? ' active' : '' ?>" href="<?= $escape($baseUrl) ?>/"<?= $activePage === 'dashboard' ? ' aria-current="page"' : '' ?>>
                            <span class="nav-link-icon"><i class="ti ti-dashboard fs-2"></i></span>
                            <span class="nav-link-title">Bảng điều khiển tổng quan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-calendar-event fs-2"></i></span>
                            <span class="nav-link-title">Quản lý đợt thực tập &amp; đồ án</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link dropdown-toggle w-100 border-0 bg-transparent text-start" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-topics" aria-controls="sidebar-topics" aria-expanded="false">
                            <span class="nav-link-icon"><i class="ti ti-book fs-2"></i></span>
                            <span class="nav-link-title">Đề tài &amp; Nguyện vọng</span>
                        </button>
                        <div class="collapse" id="sidebar-topics">
                            <ul class="navbar-nav flex-column ps-3 py-1">
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Danh mục đề tài</span></li>
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Đề xuất &amp; Duyệt đề tài</span></li>
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Đăng ký nguyện vọng</span></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-arrows-shuffle fs-2"></i></span>
                            <span class="nav-link-title">Ghép cặp sinh viên &amp; đề tài</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link dropdown-toggle w-100 border-0 bg-transparent text-start" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-progress" aria-controls="sidebar-progress" aria-expanded="false">
                            <span class="nav-link-icon"><i class="ti ti-list-check fs-2"></i></span>
                            <span class="nav-link-title">Theo dõi tiến độ &amp; Báo cáo</span>
                        </button>
                        <div class="collapse" id="sidebar-progress">
                            <ul class="navbar-nav flex-column ps-3 py-1">
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Kế hoạch &amp; Mốc tiến độ</span></li>
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Báo cáo định kỳ</span></li>
                                <li class="nav-item"><span class="nav-link disabled" aria-disabled="true">Nhật ký thực tập</span></li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-checklist fs-2"></i></span>
                            <span class="nav-link-title">Đánh giá &amp; Tiêu chí Rubric</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-building fs-2"></i></span>
                            <span class="nav-link-title">Đơn vị &amp; Tin tuyển dụng</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-chart-bar fs-2"></i></span>
                            <span class="nav-link-title">Thống kê &amp; Báo cáo</span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link disabled" aria-disabled="true">
                            <span class="nav-link-icon"><i class="ti ti-settings fs-2"></i></span>
                            <span class="nav-link-title">Quản trị &amp; Phân quyền</span>
                        </span>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</aside>
