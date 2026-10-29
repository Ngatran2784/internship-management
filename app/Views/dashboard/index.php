<?php
/**
 * GIAO DIỆN BẢNG ĐIỀU KHIỂN TỔNG QUAN (DASHBOARD)
 * Dự án: Hệ thống quản lý thực tập và đồ án sinh viên
 * 
 * Bố cục: Chuẩn Tabler UI (Sidebar Dark Theme + Header Light + Empty States)
 * Phân công:
 * - Khung giao diện do TV1 tích hợp từ mẫu Tabler chính thức.
 * - TV3 sẽ chịu trách nhiệm bóc tách layouts (header, sidebar, footer) và các trang nghiệp vụ chi tiết.
 */

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$pageTitle = "Bảng điều khiển tổng quan - Hệ thống quản lý thực tập và đồ án sinh viên";
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
        overflow-x: hidden;
      }
      /* Tối ưu Sidebar để không tràn ngang và không ngắt dòng bất thường */
      .navbar-vertical {
        width: 17rem;
      }
      @media (min-width: 992px) {
        .navbar-vertical.navbar-expand-lg {
          width: 17.5rem;
        }
        .page-wrapper {
          margin-left: 17.5rem;
        }
      }
      .brand-title {
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.3;
        letter-spacing: -0.01em;
      }
      .nav-link-title {
        font-size: 0.9rem;
        font-weight: 500;
      }
      .navbar-vertical .navbar-nav .nav-link {
        padding: 0.65rem 1rem;
      }
      .empty-state-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #f1f5f9;
        color: #64748b;
        margin-bottom: 1rem;
      }
    </style>
  </head>
  <body>
    <div class="page">
      <!-- ================= SIDEBAR (NAVBAR DỌC TỐI MÀU) ================= -->
      <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/sidebar.php -->
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          
          <!-- Logo & Tên Website -->
          <div class="navbar-brand navbar-brand-autodark px-3 py-3 text-start w-100">
            <a href="<?= $baseUrl ?>/" class="d-flex align-items-center text-decoration-none text-reset">
              <span class="avatar avatar-sm bg-primary text-white rounded me-2 flex-shrink-0">
                <i class="ti ti-school fs-2"></i>
              </span>
              <div class="brand-title text-white">
                <div>QL THỰC TẬP &amp; ĐỒ ÁN</div>
                <small class="text-white-50 fw-normal fs-6 d-block" style="font-size: 0.75rem !important;">Trường Đại học</small>
              </div>
            </a>
          </div>

          <!-- User info trên mobile -->
          <div class="navbar-nav flex-row d-lg-none">
            <div class="nav-item">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0">
                <span class="avatar avatar-sm bg-secondary-lt"><i class="ti ti-user"></i></span>
              </a>
            </div>
          </div>

          <!-- Danh mục Chức năng (Đúng theo tài liệu giảng viên giao) -->
          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-2">
              <!-- 1. Tổng quan -->
              <li class="nav-item active">
                <a class="nav-link" href="<?= $baseUrl ?>/">
                  <span class="nav-link-icon d-md-none d-lg-inline-block text-primary">
                    <i class="ti ti-dashboard fs-2"></i>
                  </span>
                  <span class="nav-link-title">Bảng điều khiển tổng quan</span>
                </a>
              </li>

              <!-- 2. Quản lý đợt -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-calendar-event fs-2"></i>
                  </span>
                  <span class="nav-link-title">Quản lý đợt thực tập &amp; đồ án</span>
                </a>
              </li>

              <!-- 3. Đề tài & Nguyện vọng -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#sidebar-topics" data-bs-toggle="collapse" role="button" aria-expanded="false">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-book fs-2"></i>
                  </span>
                  <span class="nav-link-title">Đề tài &amp; Nguyện vọng</span>
                </a>
                <div class="collapse" id="sidebar-topics">
                  <ul class="nav nav-pills flex-column ps-3 py-1">
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Danh mục đề tài</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Đề xuất &amp; Duyệt đề tài</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Đăng ký nguyện vọng</a>
                    </li>
                  </ul>
                </div>
              </li>

              <!-- 4. Ghép cặp (Matching) -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-arrows-shuffle fs-2"></i>
                  </span>
                  <span class="nav-link-title">Ghép cặp sinh viên &amp; đề tài</span>
                </a>
              </li>

              <!-- 5. Tiến độ & Báo cáo -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#sidebar-progress" data-bs-toggle="collapse" role="button" aria-expanded="false">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-list-check fs-2"></i>
                  </span>
                  <span class="nav-link-title">Theo dõi tiến độ &amp; Báo cáo</span>
                </a>
                <div class="collapse" id="sidebar-progress">
                  <ul class="nav nav-pills flex-column ps-3 py-1">
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Kế hoạch &amp; Mốc tiến độ</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Báo cáo định kỳ</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link text-white-50 py-1" href="#!">Nhật ký thực tập</a>
                    </li>
                  </ul>
                </div>
              </li>

              <!-- 6. Đánh giá & Rubric -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-checklist fs-2"></i>
                  </span>
                  <span class="nav-link-title">Đánh giá &amp; Tiêu chí Rubric</span>
                </a>
              </li>

              <!-- 7. Doanh nghiệp thực tập -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-building fs-2"></i>
                  </span>
                  <span class="nav-link-title">Đơn vị &amp; Tin tuyển dụng</span>
                </a>
              </li>

              <!-- 8. Thống kê & Báo cáo -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-chart-bar fs-2"></i>
                  </span>
                  <span class="nav-link-title">Thống kê &amp; Báo cáo</span>
                </a>
              </li>

              <!-- 9. Quản trị hệ thống & Phân quyền -->
              <li class="nav-item">
                <a class="nav-link" href="#!" title="Chức năng đang được phát triển">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-settings fs-2"></i>
                  </span>
                  <span class="nav-link-title">Quản trị &amp; Phân quyền</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </aside>

      <!-- ================= HEADER TOP NAVBAR (SÁNG MÀU) ================= -->
      <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/header.php -->
      <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none bg-white border-bottom">
        <div class="container-xl">
          <div class="collapse navbar-collapse" id="navbar-menu">
            <!-- Ô tìm kiếm nhanh -->
            <form action="./" method="get" autocomplete="off" novalidate style="width: 320px;">
              <div class="input-icon">
                <span class="input-icon-addon">
                  <i class="ti ti-search fs-3"></i>
                </span>
                <input type="text" value="" class="form-control" placeholder="Tìm kiếm sinh viên, đề tài, giảng viên..." aria-label="Tìm kiếm">
              </div>
            </form>
          </div>

          <div class="navbar-nav flex-row order-md-last align-items-center">
            <!-- Thông báo -->
            <div class="nav-item dropdown me-3">
              <a href="#" class="nav-link px-0 text-secondary" data-bs-toggle="dropdown" tabindex="-1" aria-label="Thông báo">
                <i class="ti ti-bell fs-2"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card" style="width: 300px;">
                <div class="card">
                  <div class="card-header py-2">
                    <h3 class="card-title fs-5">Thông báo hệ thống</h3>
                  </div>
                  <div class="card-body text-center py-4 text-muted">
                    <i class="ti ti-bell-off fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    Không có thông báo mới nào.
                  </div>
                </div>
              </div>
            </div>

            <!-- Khu vực tài khoản người dùng -->
            <!-- TODO [TV2]: Hiển thị thông tin tài khoản thật sau khi hoàn thành AuthController -->
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Tài khoản">
                <span class="avatar avatar-sm bg-primary-lt">
                  <i class="ti ti-user fs-2"></i>
                </span>
                <div class="d-none d-xl-block ps-2 text-start">
                  <div class="fw-medium">Hệ thống quản lý</div>
                  <div class="small text-secondary mt-1">Chưa đăng nhập</div>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <!-- TODO [TV2]: Gắn route /login khi TV2 hoàn thiện -->
                <a href="#!" class="dropdown-item">
                  <i class="ti ti-login me-2"></i> Đăng nhập tài khoản
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- ================= PAGE CONTENT WRAPPER ================= -->
      <div class="page-wrapper">
        <!-- Tiêu đề trang -->
        <div class="page-header d-print-none py-3">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <div class="page-pretitle text-secondary">
                  HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN
                </div>
                <h1 class="page-title text-dark">
                  Bảng điều khiển tổng quan
                </h1>
              </div>
              <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                  <button type="button" class="btn btn-primary d-none d-sm-inline-block" disabled title="Chức năng đang được phát triển">
                    <i class="ti ti-plus fs-2 me-1"></i>
                    Tạo đợt thực tập / đồ án mới
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Thân trang Dashboard -->
        <div class="page-body mt-2">
          <div class="container-xl">
            <!-- Hàng Thẻ Thống Kê (Dashboard Cards) -->
            <div class="row row-deck row-cards mb-4">
              <!-- Thẻ 1: Sinh viên tham gia -->
              <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader text-secondary">Sinh viên tham gia</div>
                    </div>
                    <div class="d-flex align-items-baseline my-2">
                      <div class="h2 mb-0 me-2 text-muted">Chưa có dữ liệu</div>
                    </div>
                    <div class="text-secondary small">
                      <i class="ti ti-info-circle me-1"></i>Chưa mở đợt đăng ký mới
                    </div>
                  </div>
                </div>
              </div>

              <!-- Thẻ 2: Đề tài -->
              <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader text-secondary">Đề tài đồ án</div>
                    </div>
                    <div class="d-flex align-items-baseline my-2">
                      <div class="h2 mb-0 me-2 text-muted">Chưa có dữ liệu</div>
                    </div>
                    <div class="text-secondary small">
                      <i class="ti ti-info-circle me-1"></i>Chưa có đề tài nào được duyệt
                    </div>
                  </div>
                </div>
              </div>

              <!-- Thẻ 3: Doanh nghiệp liên kết -->
              <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader text-secondary">Doanh nghiệp thực tập</div>
                    </div>
                    <div class="d-flex align-items-baseline my-2">
                      <div class="h2 mb-0 me-2 text-muted">Chưa có dữ liệu</div>
                    </div>
                    <div class="text-secondary small">
                      <i class="ti ti-info-circle me-1"></i>Đang cập nhật danh sách đơn vị
                    </div>
                  </div>
                </div>
              </div>

              <!-- Thẻ 4: Tiến độ báo cáo -->
              <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader text-secondary">Tiến độ báo cáo</div>
                    </div>
                    <div class="d-flex align-items-baseline my-2">
                      <div class="h2 mb-0 me-2 text-muted">Chưa có dữ liệu</div>
                    </div>
                    <div class="text-secondary small">
                      <i class="ti ti-info-circle me-1"></i>Chưa đến hạn nộp báo cáo
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bảng Dữ Liệu: Trạng thái trống (Empty State) Chuẩn Tabler -->
            <div class="card shadow-sm">
              <div class="card-header border-bottom">
                <h3 class="card-title text-dark">Tiến độ thực tập &amp; đồ án sinh viên</h3>
              </div>
              <div class="card-body py-5 text-center">
                <div class="empty-state-icon">
                  <i class="ti ti-clipboard-list fs-1"></i>
                </div>
                <h3 class="text-dark fw-bold mb-2">Chưa có đề tài hoặc báo cáo nào</h3>
                <p class="text-secondary max-w-sm mx-auto mb-4" style="max-width: 450px;">
                  Hệ thống hiện tại chưa ghi nhận danh sách đề tài hay tiến độ báo cáo của sinh viên. Dữ liệu sẽ tự động hiển thị tại đây khi quản trị viên khởi tạo đợt thực tập/đồ án và sinh viên đăng ký đề tài.
                </p>
                <div class="d-flex justify-content-center gap-2">
                  <button type="button" class="btn btn-outline-secondary" disabled title="Chức năng đang được phát triển">
                    <i class="ti ti-filter me-1"></i> Bộ lọc nâng cao
                  </button>
                  <button type="button" class="btn btn-primary" disabled title="Chức năng đang được phát triển">
                    <i class="ti ti-refresh me-1"></i> Làm mới danh sách
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= FOOTER ================= -->
        <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/footer.php -->
        <footer class="footer footer-transparent d-print-none py-3 border-top mt-auto">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-12 text-center text-secondary">
                Bản quyền &copy; <?= date('Y') ?> &bull; Hệ thống quản lý thực tập và đồ án sinh viên.
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <!-- Tabler Bundle JavaScript -->
    <script src="<?= $baseUrl ?>/assets/tabler/js/tabler.min.js"></script>
  </body>
</html>
