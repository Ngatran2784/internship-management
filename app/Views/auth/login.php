<?php
/**
 * GIAO DIỆN ĐĂNG NHẬP (AUTH LOGIN)
 * Dự án: Hệ thống quản lý thực tập và đồ án sinh viên
 * Giao diện: Tabler Bootstrap 5 (Single Page Center)
 */

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$pageTitle = "Đăng nhập - Hệ thống quản lý thực tập và đồ án sinh viên";
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Tabler Core CSS & Icons -->
    <link href="<?= $baseUrl ?>/assets/tabler/css/tabler.min.css" rel="stylesheet"/>
    <link href="<?= $baseUrl ?>/assets/tabler/css/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
      @import url('https://rsms.me/inter/inter.css');
      :root {
      	--tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
      	font-feature-settings: "cv03", "cv04", "cv11";
      }
      .brand-icon {
        width: 3.25rem;
        height: 3.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #206bc4;
        color: #ffffff;
        margin-bottom: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(32, 107, 196, 0.2);
      }
    </style>
  </head>
  <body class="d-flex flex-column bg-light">
    <div class="page page-center py-4">
      <div class="container container-tight">
        <!-- Logo & Nhận diện thương hiệu -->
        <div class="text-center mb-4">
          <a href="<?= $baseUrl ?>/" class="d-inline-block text-decoration-none">
            <div class="brand-icon mx-auto">
              <i class="ti ti-school fs-1"></i>
            </div>
            <h1 class="h3 fw-bold text-dark mb-1">HỆ THỐNG QUẢN LÝ THỰC TẬP &amp; ĐỒ ÁN</h1>
            <div class="text-secondary small">Trường Đại học &bull; Cổng thông tin trực tuyến</div>
          </a>
        </div>

        <!-- Khung Card Đăng nhập Tabler -->
        <div class="card card-md shadow-sm border-0">
          <div class="card-body p-4 p-md-5">
            <h2 class="h2 text-center mb-4 text-dark fw-bold">Đăng nhập tài khoản</h2>

            <!-- Khu vực thông báo lỗi (được escape an toàn chống XSS) -->
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                  <div>
                    <i class="ti ti-alert-circle fs-2 me-2"></i>
                  </div>
                  <div>
                    <div class="text-danger fw-medium"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
              </div>
            <?php endif; ?>

            <!-- Form Đăng nhập -->
            <form action="<?= $baseUrl ?>/login" method="POST" autocomplete="off" novalidate>
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)($csrfToken ?? (\App\Controllers\AuthController::csrfToken() ?? '')), ENT_QUOTES, 'UTF-8') ?>">
              <!-- Ô Tên đăng nhập / Mã định danh -->
              <div class="mb-3">
                <label class="form-label required" for="username">Tên đăng nhập hoặc Email</label>
                <div class="input-icon">
                  <span class="input-icon-addon">
                    <i class="ti ti-user fs-2 text-secondary"></i>
                  </span>
                  <input type="text" 
                         id="username" 
                         name="username" 
                         class="form-control" 
                         placeholder="Mã sinh viên / Mã giảng viên / Email" 
                         value="<?= isset($oldUsername) ? htmlspecialchars((string)$oldUsername, ENT_QUOTES, 'UTF-8') : '' ?>" 
                         required 
                         autofocus>
                </div>
              </div>

              <!-- Ô Mật khẩu -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <label class="form-label required mb-0" for="password">Mật khẩu</label>
                  <a href="#!" class="small text-secondary" title="Liên hệ Quản trị viên để đặt lại mật khẩu">Quên mật khẩu?</a>
                </div>
                <div class="input-icon mt-2">
                  <span class="input-icon-addon">
                    <i class="ti ti-lock fs-2 text-secondary"></i>
                  </span>
                  <input type="password" 
                         id="password" 
                         name="password" 
                         class="form-control" 
                         placeholder="Nhập mật khẩu truy cập" 
                         required>
                </div>
              </div>

              <!-- Ghi nhớ đăng nhập -->
              <div class="mb-3">
                <label class="form-check">
                  <input type="checkbox" name="remember" class="form-check-input"/>
                  <span class="form-check-label text-secondary small">Ghi nhớ đăng nhập trên thiết bị này</span>
                </label>
              </div>

              <!-- Nút Đăng nhập -->
              <div class="form-footer mt-4">
                <button type="submit" class="btn btn-primary w-100 py-2 fs-4 fw-medium">
                  <i class="ti ti-login me-2"></i> Đăng nhập
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Chân trang đăng nhập -->
        <div class="text-center text-secondary mt-4">
          <a href="<?= $baseUrl ?>/" class="text-secondary text-decoration-none small">
            <i class="ti ti-arrow-left me-1"></i> Quay về Bảng điều khiển tổng quan
          </a>
        </div>
      </div>
    </div>

    <!-- Tabler Bundle JavaScript -->
    <script src="<?= $baseUrl ?>/assets/tabler/js/tabler.min.js"></script>
  </body>
</html>
