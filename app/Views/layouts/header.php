<?php
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$baseUrl = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
$pageTitle = $pageTitle ?? 'Hệ thống Quản lý Thực tập & Đồ án';
$pageHeading = $pageHeading ?? 'Bảng điều khiển tổng quan';
$pageDescription = $pageDescription ?? 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = $activePage ?? '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Đồng bộ trạng thái đăng nhập từ Session
if (class_exists(\App\Controllers\AuthController::class)) {
    \App\Controllers\AuthController::startSession();
    $isLoggedIn = \App\Controllers\AuthController::check();
    $csrfToken = \App\Controllers\AuthController::csrfToken();
} else {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $isLoggedIn = isset($_SESSION['user_id']);
    $csrfToken = $_SESSION['csrf_token'] ?? '';
}

$currentFullName = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Người dùng');
$currentRoles = $_SESSION['roles'] ?? [];
$roleLabels = [
    'admin'           => 'Quản trị viên',
    'training_dept'   => 'Phòng đào tạo',
    'department_head' => 'Trưởng bộ môn',
    'lecturer'        => 'Giảng viên',
    'student'         => 'Sinh viên',
    'enterprise'      => 'Doanh nghiệp',
];
$displayRole = !empty($currentRoles) ? ($roleLabels[$currentRoles[0]] ?? $currentRoles[0]) : 'Thành viên';
$isAdmin = in_array('admin', $currentRoles, true);
$isStudent = in_array('student', $currentRoles, true);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="Hệ thống quản lý thực tập và đồ án sinh viên">
    <title><?= $escape($pageTitle) ?></title>
    <link href="<?= $escape($baseUrl) ?>/assets/tabler/css/tabler.min.css" rel="stylesheet">
    <link href="<?= $escape($baseUrl) ?>/assets/tabler/css/tabler-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --tblr-font-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body { overflow-x: hidden; }
        .app-page-wrapper { min-height: 100vh; display: flex; flex-direction: column; }
        .app-page-wrapper > .page-body { flex: 1 0 auto; }
        .app-brand-title { font-size: 1.05rem; font-weight: 700; line-height: 1.3; letter-spacing: -.01em; }
        .app-topbar { min-height: 2.875rem; padding-top: 0; padding-bottom: 0; }
        .app-topbar .navbar-nav { min-height: 2.875rem; }
        .app-sidebar { overflow-x: hidden !important; }
        .app-sidebar .navbar-nav { overflow-x: hidden; }
        .app-sidebar .nav-link { white-space: nowrap; padding: 0.5rem 0.875rem; text-overflow: ellipsis; overflow: hidden; }
        .app-sidebar .nav-link-title { font-size: .875rem; font-weight: 500; line-height: 1.35; white-space: nowrap; }
        .app-sidebar .nav-link.disabled { color: rgba(255, 255, 255, .72); opacity: 1; cursor: not-allowed; }
        .app-search { width: min(20rem, 100%); }
        .dashboard-stat-body { padding-top: .7rem !important; padding-bottom: .7rem !important; }
        .app-search .form-control { height: 2rem; }
        .empty-state-icon {
            width: 3rem;
            height: 3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
            color: #64748b;
        }
        @media (min-width: 992px) {
            .navbar-vertical.navbar-expand-lg.app-sidebar { width: 16rem; }
            .navbar-vertical.navbar-expand-lg.app-sidebar ~ .app-page-wrapper { margin-left: 16rem; }
        }
        @media (min-width: 1200px) {
            .app-page-wrapper > .page-header { margin-top: 1.5rem; }
        }
        @media (min-width: 1400px) {
            .navbar-vertical.navbar-expand-lg.app-sidebar { width: 16rem; }
            .navbar-vertical.navbar-expand-lg.app-sidebar ~ .app-page-wrapper { margin-left: 16rem; }
        }
    </style>
</head>
<body>
<div class="page">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="page-wrapper app-page-wrapper">
        <header class="navbar navbar-expand-md app-topbar d-print-none bg-white border-bottom">
            <div class="container-xl">
                <div class="d-none d-lg-block app-search">
                    <label class="visually-hidden" for="quick-search">Tìm kiếm</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                        <input id="quick-search" type="search" class="form-control form-control-sm" placeholder="Tìm kiếm sinh viên, đề tài, giảng viên..." readonly aria-label="Tìm kiếm (chưa khả dụng)" title="Chức năng tìm kiếm chưa được triển khai">
                    </div>
                </div>
                <div class="navbar-nav flex-row ms-auto align-items-center">
                    <div class="nav-item dropdown me-3">
                        <a href="#" class="nav-link px-0 text-secondary" data-bs-toggle="dropdown" aria-label="Thông báo">
                            <i class="ti ti-bell fs-2"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header py-2">
                                    <h2 class="card-title fs-5">Thông báo hệ thống</h2>
                                </div>
                                <div class="card-body text-center py-4 text-secondary">
                                    <i class="ti ti-bell-off fs-1 d-block mb-2 opacity-50"></i>
                                    Chưa có thông báo mới.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="nav-item dropdown">
                    <?php if ($isLoggedIn): ?>
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Tài khoản">
                            <span class="avatar avatar-sm bg-primary-lt">
                                <i class="ti ti-user fs-2"></i>
                            </span>
                            <span class="d-none d-sm-block ps-2 text-start">
                                <span class="fw-medium d-block"><?= $escape($currentFullName) ?></span>
                                <span class="small text-secondary"><?= $escape($displayRole) ?></span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <div class="dropdown-header">
                                <span class="d-block fw-bold text-dark"><?= $escape($currentFullName) ?></span>
                                <span class="small text-secondary"><?= $escape($displayRole) ?></span>
                            </div>
                            <a href="<?= $escape($baseUrl) ?>/dashboard" class="dropdown-item">
                                <i class="ti ti-dashboard me-2"></i>Bảng điều khiển tổng quan
                            </a>
                            <div class="dropdown-divider"></div>
                            <form action="<?= $escape($baseUrl) ?>/logout" method="POST" class="m-0 p-0">
                                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                                <button type="submit" class="dropdown-item text-danger border-0 bg-transparent w-100 text-start">
                                    <i class="ti ti-logout me-2"></i>Đăng xuất
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Tài khoản">
                            <span class="avatar avatar-sm bg-secondary-lt">
                                <i class="ti ti-user fs-2"></i>
                            </span>
                            <span class="d-none d-sm-block ps-2 text-start">
                                <span class="fw-medium d-block">Hệ thống quản lý</span>
                                <span class="small text-secondary">Chưa đăng nhập</span>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?= $escape($baseUrl) ?>/login" class="dropdown-item">
                                <i class="ti ti-login me-2"></i>Đăng nhập tài khoản
                            </a>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>

        <div class="page-header d-print-none py-2">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <div class="page-pretitle text-secondary"><?= $escape($pageDescription) ?></div>
                        <h1 class="page-title"><?= $escape($pageHeading) ?></h1>
                    </div>
                    <?php if ($activePage === 'dashboard'): ?>
                        <div class="col-12 col-sm-auto ms-sm-auto text-sm-end d-print-none">
                            <button type="button" class="btn btn-primary" disabled title="Chức năng đang được phát triển">
                                <i class="ti ti-plus me-1"></i>Tạo đợt thực tập / đồ án mới
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <main class="page-body mt-2">
