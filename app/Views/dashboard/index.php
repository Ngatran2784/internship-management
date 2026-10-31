<?php
$pageTitle = 'Bảng điều khiển tổng quan - Hệ thống quản lý thực tập và đồ án sinh viên';
$pageHeading = 'Bảng điều khiển tổng quan';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'dashboard';
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="container-xl">
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <section class="card card-sm h-100">
                <div class="card-body py-2 dashboard-stat-body">
                    <div class="subheader text-secondary">Sinh viên tham gia</div>
                    <div class="h3 my-1 text-secondary">Chưa có dữ liệu</div>
                    <div class="text-secondary small"><i class="ti ti-info-circle me-1"></i>Chưa mở đợt đăng ký mới</div>
                </div>
            </section>
        </div>
        <div class="col-sm-6 col-lg-3">
            <section class="card card-sm h-100">
                <div class="card-body py-2 dashboard-stat-body">
                    <div class="subheader text-secondary">Đề tài đồ án</div>
                    <div class="h3 my-1 text-secondary">Chưa có dữ liệu</div>
                    <div class="text-secondary small"><i class="ti ti-info-circle me-1"></i>Chưa có đề tài nào được duyệt</div>
                </div>
            </section>
        </div>
        <div class="col-sm-6 col-lg-3">
            <section class="card card-sm h-100">
                <div class="card-body py-2 dashboard-stat-body">
                    <div class="subheader text-secondary">Doanh nghiệp thực tập</div>
                    <div class="h3 my-1 text-secondary">Chưa có dữ liệu</div>
                    <div class="text-secondary small"><i class="ti ti-info-circle me-1"></i>Đang cập nhật danh sách đơn vị</div>
                </div>
            </section>
        </div>
        <div class="col-sm-6 col-lg-3">
            <section class="card card-sm h-100">
                <div class="card-body py-2 dashboard-stat-body">
                    <div class="subheader text-secondary">Tiến độ báo cáo</div>
                    <div class="h3 my-1 text-secondary">Chưa có dữ liệu</div>
                    <div class="text-secondary small"><i class="ti ti-info-circle me-1"></i>Chưa đến hạn nộp báo cáo</div>
                </div>
            </section>
        </div>
    </div>

    <section class="card shadow-sm" aria-labelledby="internship-progress-title">
        <div class="card-header py-3">
            <h3 class="card-title text-dark" id="internship-progress-title">Tiến độ thực tập &amp; đồ án sinh viên</h3>
        </div>
        <div class="card-body py-3 text-center">
            <span class="empty-state-icon mb-3"><i class="ti ti-clipboard-list fs-1"></i></span>
            <h3 class="text-dark fw-bold mb-2">Chưa có đề tài hoặc báo cáo nào</h3>
            <p class="text-secondary mx-auto mb-4" style="max-width: 450px;">
                Hệ thống hiện tại chưa ghi nhận danh sách đề tài hay tiến độ báo cáo của sinh viên. Dữ liệu sẽ tự động hiển thị tại đây khi quản trị viên khởi tạo đợt thực tập/đồ án và sinh viên đăng ký đề tài.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Chức năng đang được phát triển">
                    <i class="ti ti-filter me-1"></i>Bộ lọc nâng cao
                </button>
                <button type="button" class="btn btn-sm btn-primary" disabled title="Chức năng đang được phát triển">
                    <i class="ti ti-refresh me-1"></i>Làm mới danh sách
                </button>
            </div>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
