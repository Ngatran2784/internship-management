<?php
/**
 * TABLER ADMIN DASHBOARD VIEW
 * Nguồn Template: Tabler Admin Dashboard (Official Build - v1.0.0-beta20)
 * Giấy phép: MIT License - https://tabler.io
 * 
 * Tích hợp cho: Hệ thống quản lý thực tập và đồ án sinh viên
 * Lưu ý phân công:
 * - TV3 sẽ chịu trách nhiệm tách nhỏ file này thành:
 *   + app/Views/layouts/header.php
 *   + app/Views/layouts/sidebar.php
 *   + app/Views/layouts/footer.php
 *   + app/Views/dashboard/index.php (chỉ chứa phần nội dung chính)
 */

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$pageTitle = "Tổng quan Dashboard - Hệ thống Quản lý Thực tập & Đồ án Sinh viên";
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Tabler Core CSS (Bản dựng sẵn chính thức) -->
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
      .badge-demo {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.72rem;
        text-transform: uppercase;
      }
    </style>
  </head>
  <body>
    <div class="page">
      <!-- ================= SIDEBAR (NAVBAR DỌC CHUẨN TABLER) ================= -->
      <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/sidebar.php -->
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          
          <!-- Logo & Tên Website -->
          <h1 class="navbar-brand navbar-brand-autodark px-2 py-3 text-start">
            <a href="<?= $baseUrl ?>/" class="d-flex align-items-center text-decoration-none">
              <span class="avatar avatar-sm bg-primary text-white rounded me-2">
                <i class="ti ti-school fs-2"></i>
              </span>
              <div class="d-inline-block text-truncate" style="max-width: 170px;">
                <span class="fs-4 fw-bold text-white d-block lh-1">QL THỰC TẬP</span>
                <small class="text-white-50 fs-6 lh-1">& ĐỒ ÁN SINH VIÊN</small>
              </div>
            </a>
          </h1>

          <!-- User info trên mobile -->
          <div class="navbar-nav flex-row d-lg-none">
            <div class="nav-item">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0">
                <span class="avatar avatar-sm bg-blue-lt">AD</span>
              </a>
            </div>
          </div>

          <!-- Danh mục Menu chính (Chuẩn hóa theo 8 nhóm chức năng của Giảng viên) -->
          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
              <!-- 1. Tổng quan -->
              <li class="nav-item active">
                <a class="nav-link" href="<?= $baseUrl ?>/">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-dashboard fs-2"></i>
                  </span>
                  <span class="nav-link-title">Tổng quan Dashboard</span>
                </a>
              </li>

              <!-- 2. Quản lý đợt -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-calendar-event fs-2"></i>
                  </span>
                  <span class="nav-link-title">Quản lý Đợt thực tập / Đồ án</span>
                </a>
              </li>

              <!-- 3. Đề tài & Nguyện vọng -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-book fs-2"></i>
                  </span>
                  <span class="nav-link-title">Đề tài & Nguyện vọng</span>
                </a>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="#!">Danh mục đề tài đồ án</a>
                  <a class="dropdown-item" href="#!">Duyệt đề tài giảng viên</a>
                  <a class="dropdown-item" href="#!">Đăng ký nguyện vọng sinh viên</a>
                </div>
              </li>

              <!-- 4. Ghép cặp (Matching) -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-arrows-shuffle fs-2"></i>
                  </span>
                  <span class="nav-link-title">
                    Ghép cặp (Matching)
                    <span class="badge badge-sm bg-red-lt text-uppercase ms-auto">Thuật toán</span>
                  </span>
                </a>
              </li>

              <!-- 5. Tiến độ & Nhật ký -->
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#navbar-progress" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-list-check fs-2"></i>
                  </span>
                  <span class="nav-link-title">Tiến độ & Nhật ký</span>
                </a>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="#!">Kế hoạch & Mốc công việc (Milestones)</a>
                  <a class="dropdown-item" href="#!">Nộp báo cáo định kỳ</a>
                  <a class="dropdown-item" href="#!">Nhật ký thực tập doanh nghiệp</a>
                </div>
              </li>

              <!-- 6. Đánh giá & Rubric -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-checklist fs-2"></i>
                  </span>
                  <span class="nav-link-title">Đánh giá & Thư viện Rubric</span>
                </a>
              </li>

              <!-- 7. Doanh nghiệp & Vị trí thực tập -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-building-community fs-2"></i>
                  </span>
                  <span class="nav-link-title">Doanh nghiệp & Tuyển dụng</span>
                </a>
              </li>

              <!-- 8. Thống kê & Báo cáo -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-chart-pie fs-2"></i>
                  </span>
                  <span class="nav-link-title">Thống kê & Xuất báo cáo</span>
                </a>
              </li>

              <!-- 9. Quản trị hệ thống & Phân quyền (RBAC) -->
              <li class="nav-item">
                <a class="nav-link" href="#!">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <i class="ti ti-users-group fs-2"></i>
                  </span>
                  <span class="nav-link-title">Quản trị & Phân quyền</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </aside>

      <!-- ================= HEADER TOP NAVBAR ================= -->
      <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/header.php -->
      <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
        <div class="container-xl">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          
          <div class="navbar-nav flex-row order-md-last">
            <!-- Thông báo -->
            <div class="nav-item dropdown d-none d-md-flex me-3">
              <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Hiển thị thông báo">
                <i class="ti ti-bell fs-2"></i>
                <span class="badge bg-red"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                <div class="card">
                  <div class="card-header">
                    <h3 class="card-title">Thông báo hệ thống</h3>
                  </div>
                  <div class="list-group list-group-flush list-group-hoverable">
                    <div class="list-group-item">
                      <div class="row align-items-center">
                        <div class="col-auto"><span class="status-dot status-dot-animated bg-green d-block"></span></div>
                        <div class="col text-truncate">
                          <span class="text-body d-block">[DEMO] Hạn chót nộp đề cương</span>
                          <div class="d-block text-secondary text-truncate mt-n1">
                            Các nhóm đồ án khóa 2022 nộp đề cương trước ngày 15/11.
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Profile User Dropdown -->
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Mở menu người dùng">
                <span class="avatar avatar-sm bg-primary-lt">QL</span>
                <div class="d-none d-xl-block ps-2">
                  <div>Quản trị viên (Admin)</div>
                  <div class="mt-1 small text-secondary">Phòng Đào tạo / Khoa CNTT</div>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <a href="#!" class="dropdown-item">Hồ sơ cá nhân</a>
                <a href="#!" class="dropdown-item">Đổi mật khẩu</a>
                <div class="dropdown-divider"></div>
                <!-- TODO [TV2]: Gắn link đăng xuất khi hoàn thành AuthController@logout -->
                <a href="#!" class="dropdown-item text-danger">Đăng xuất (Logout)</a>
              </div>
            </div>
          </div>

          <!-- Tìm kiếm nhanh -->
          <div class="collapse navbar-collapse" id="navbar-menu">
            <div>
              <form action="./" method="get" autocomplete="off" novalidate>
                <div class="input-icon">
                  <span class="input-icon-addon">
                    <i class="ti ti-search fs-3"></i>
                  </span>
                  <input type="text" value="" class="form-control" placeholder="Tìm sinh viên, đề tài, giảng viên..." aria-label="Tìm kiếm trong hệ thống">
                </div>
              </form>
            </div>
          </div>
        </div>
      </header>

      <!-- ================= PAGE CONTENT WRAPPER ================= -->
      <div class="page-wrapper">
        <!-- Tiêu đề trang -->
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <div class="page-pretitle">
                  HỆ THỐNG QUẢN LÝ THỰC TẬP & ĐỒ ÁN SINH VIÊN &bull; SPRINT 1
                </div>
                <h2 class="page-title">
                  Bảng điều khiển Tổng quan (Dashboard)
                </h2>
              </div>
              <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                  <span class="badge-demo me-2">DỮ LIỆU MINH HỌA (DEMO)</span>
                  <a href="#!" class="btn btn-primary d-none d-sm-inline-block">
                    <i class="ti ti-plus fs-2 me-1"></i>
                    Tạo đợt thực tập / đồ án mới
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Thân trang Dashboard -->
        <div class="page-body">
          <div class="container-xl">
            <!-- Thông báo banner Sprint 1 -->
            <div class="alert alert-info alert-dismissible bg-white shadow-sm border-0 mb-3" role="alert">
              <div class="d-flex">
                <div>
                  <i class="ti ti-info-circle fs-1 text-info me-2"></i>
                </div>
                <div>
                  <h4 class="alert-title mb-1">Giao diện Tabler Admin Dashboard chính thức (v1.0.0-beta20)</h4>
                  <div class="text-secondary small">
                    Khung giao diện đã được tích hợp trực tiếp vào dự án PHP thuần MVC. TV3 sẽ chịu trách nhiệm bóc tách các khối Header, Sidebar, Footer và các màn hình con trong các task tiếp theo.
                  </div>
                </div>
              </div>
            </div>

            <!-- Hàng Thẻ Thống Kê (Dashboard Cards) -->
            <div class="row row-deck row-cards mb-3">
              <!-- Card 1: Tổng số sinh viên -->
              <div class="col-sm-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader">Sinh viên tham gia đợt</div>
                      <div class="ms-auto lh-1">
                        <span class="badge-demo">DEMO</span>
                      </div>
                    </div>
                    <div class="h1 mb-3">342</div>
                    <div class="d-flex mb-2">
                      <div>Đã được ghép đề tài</div>
                      <div class="ms-auto">
                        <span class="text-green d-inline-flex align-items-center lh-1">
                          88% <i class="ti ti-trending-up ms-1"></i>
                        </span>
                      </div>
                    </div>
                    <div class="progress progress-sm">
                      <div class="progress-bar bg-primary" style="width: 88%" role="progressbar" aria-valuenow="88" aria-valuemin="0" aria-valuemax="100" aria-label="88% hoàn thành"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 2: Tổng số đề tài -->
              <div class="col-sm-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader">Đề tài đồ án</div>
                      <div class="ms-auto lh-1">
                        <span class="badge-demo">DEMO</span>
                      </div>
                    </div>
                    <div class="h1 mb-3">128</div>
                    <div class="d-flex mb-2">
                      <div>Đã được phê duyệt</div>
                      <div class="ms-auto">
                        <span class="text-green d-inline-flex align-items-center lh-1">
                          115 / 128
                        </span>
                      </div>
                    </div>
                    <div class="progress progress-sm">
                      <div class="progress-bar bg-green" style="width: 90%" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 3: Doanh nghiệp tiếp nhận -->
              <div class="col-sm-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader">Doanh nghiệp liên kết</div>
                      <div class="ms-auto lh-1">
                        <span class="badge-demo">DEMO</span>
                      </div>
                    </div>
                    <div class="h1 mb-3">45</div>
                    <div class="d-flex mb-2">
                      <div>Vị trí thực tập đăng tuyển</div>
                      <div class="ms-auto">
                        <span class="text-blue d-inline-flex align-items-center lh-1">
                          180 vị trí
                        </span>
                      </div>
                    </div>
                    <div class="progress progress-sm">
                      <div class="progress-bar bg-info" style="width: 75%" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Card 4: Tỷ lệ nộp báo cáo đúng hạn -->
              <div class="col-sm-6 col-lg-3">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex align-items-center">
                      <div class="subheader">Tiến độ nộp báo cáo</div>
                      <div class="ms-auto lh-1">
                        <span class="badge-demo">DEMO</span>
                      </div>
                    </div>
                    <div class="h1 mb-3">94.2%</div>
                    <div class="d-flex mb-2">
                      <div>Trễ hạn giai đoạn 1</div>
                      <div class="ms-auto">
                        <span class="text-red d-inline-flex align-items-center lh-1">
                          12 SV trễ
                        </span>
                      </div>
                    </div>
                    <div class="progress progress-sm">
                      <div class="progress-bar bg-warning" style="width: 94%" role="progressbar" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bảng Dữ Liệu Demo Tabler (Data Table: Tiến độ sinh viên & Đề tài) -->
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách đề tài & Tiến độ thực hiện gần nhất</h3>
                <div class="card-actions">
                  <span class="badge-demo me-2">BẢNG DỮ LIỆU DEMO</span>
                  <a href="#!" class="btn btn-outline-secondary btn-sm">Xem tất cả</a>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table card-table table-vcenter text-nowrap datatable">
                  <thead>
                    <tr>
                      <th class="w-1">STT</th>
                      <th>Tên đề tài đồ án / thực tập</th>
                      <th>Sinh viên thực hiện</th>
                      <th>Giảng viên / DN hướng dẫn</th>
                      <th>Hạn nộp báo cáo</th>
                      <th>Trạng thái tiến độ</th>
                      <th class="text-end">Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><span class="text-secondary">01</span></td>
                      <td>
                        <a href="#!" class="text-reset fw-bold">Xây dựng website quản lý thực tập và đồ án sinh viên</a>
                        <div class="small text-secondary">Chuyên ngành: Kỹ thuật phần mềm</div>
                      </td>
                      <td>
                        <div class="d-flex py-1 align-items-center">
                          <span class="avatar avatar-sm me-2 bg-blue-lt">SV</span>
                          <div class="flex-fill">
                            <div class="font-weight-medium">Nguyễn Văn An [DEMO]</div>
                            <div class="text-secondary small">MSSV: 20220001</div>
                          </div>
                        </div>
                      </td>
                      <td>TS. Trần Minh Đức</td>
                      <td>30/11/2026</td>
                      <td>
                        <span class="badge bg-success-lt">Đúng tiến độ (Sprint 1)</span>
                      </td>
                      <td class="text-end">
                        <a href="#!" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                      </td>
                    </tr>
                    <tr>
                      <td><span class="text-secondary">02</span></td>
                      <td>
                        <a href="#!" class="text-reset fw-bold">Nghiên cứu ứng dụng thuật toán Gale-Shapley ghép cặp đề tài</a>
                        <div class="small text-secondary">Chuyên ngành: Khoa học máy tính</div>
                      </td>
                      <td>
                        <div class="d-flex py-1 align-items-center">
                          <span class="avatar avatar-sm me-2 bg-green-lt">SV</span>
                          <div class="flex-fill">
                            <div class="font-weight-medium">Lê Thị Bình [DEMO]</div>
                            <div class="text-secondary small">MSSV: 20220045</div>
                          </div>
                        </div>
                      </td>
                      <td>ThS. Nguyễn Thu Trang</td>
                      <td>05/12/2026</td>
                      <td>
                        <span class="badge bg-warning-lt">Cần nộp đề cương</span>
                      </td>
                      <td class="text-end">
                        <a href="#!" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                      </td>
                    </tr>
                    <tr>
                      <td><span class="text-secondary">03</span></td>
                      <td>
                        <a href="#!" class="text-reset fw-bold">Thực tập phát triển phần mềm Backend với PHP 8.2 & MySQL</a>
                        <div class="small text-secondary">Đơn vị: FPT Software</div>
                      </td>
                      <td>
                        <div class="d-flex py-1 align-items-center">
                          <span class="avatar avatar-sm me-2 bg-purple-lt">SV</span>
                          <div class="flex-fill">
                            <div class="font-weight-medium">Phạm Hoàng Cường [DEMO]</div>
                            <div class="text-secondary small">MSSV: 20220112</div>
                          </div>
                        </div>
                      </td>
                      <td>Anh Hoàng Nam (Mentor DN)</td>
                      <td>10/12/2026</td>
                      <td>
                        <span class="badge bg-info-lt">Đang thực tập</span>
                      </td>
                      <td class="text-end">
                        <a href="#!" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= FOOTER ================= -->
        <!-- TODO [TV3]: Tách khối này vào app/Views/layouts/footer.php -->
        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item"><a href="https://tabler.io" target="_blank" class="link-secondary" rel="noopener">Tabler Core v1.0.0-beta20</a></li>
                  <li class="list-inline-item"><a href="#!" class="link-secondary">Tài liệu Sprint 1</a></li>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    Bản quyền &copy; <?= date('Y') ?>
                    <a href="." class="link-secondary fw-bold">Hệ thống Quản lý Thực tập & Đồ án Sinh viên</a>.
                    Phát triển bằng PHP thuần 8.2 & Bootstrap 5.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <!-- Tabler Bundle JavaScript (Chính thức) -->
    <script src="<?= $baseUrl ?>/assets/tabler/js/tabler.min.js"></script>
  </body>
</html>
