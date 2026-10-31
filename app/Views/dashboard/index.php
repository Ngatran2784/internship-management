<?php
$pageTitle = 'Bảng điều khiển tổng quan - Hệ thống quản lý thực tập và đồ án sinh viên';
$pageHeading = 'Bảng điều khiển tổng quan';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'dashboard';
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="container-xl">
    <!-- Thẻ chào mừng cá nhân hóa theo vai trò thực tế từ Session -->
    <div class="card mb-4 border-0 shadow-sm bg-primary-lt">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar avatar-md <?= $isAdmin ? 'bg-primary text-white' : 'bg-success text-white' ?>">
                        <i class="ti <?= $isAdmin ? 'ti-shield-check' : 'ti-school' ?> fs-1"></i>
                    </span>
                </div>
                <div class="col">
                    <h2 class="mb-1 fw-bold text-dark">
                        Xin chào, <?= $escape($currentFullName) ?>!
                    </h2>
                    <div class="text-secondary small">
                        Vai trò hệ thống: 
                        <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-success' ?> text-white fw-medium ms-1">
                            <i class="ti <?= $isAdmin ? 'ti-shield' : 'ti-user-check' ?> me-1"></i><?= $escape($displayRole) ?>
                        </span>
                        &bull; Trạng thái tài khoản: <span class="badge bg-success-lt text-success fw-medium">Đang hoạt động</span>
                    </div>
                </div>
                <div class="col-12 col-md-auto mt-3 mt-md-0">
                    <?php if ($isAdmin): ?>
                        <span class="badge bg-azure-lt py-2 px-3">
                            <i class="ti ti-shield-lock me-1"></i>Toàn quyền Quản trị viên
                        </span>
                    <?php else: ?>
                        <span class="badge bg-teal-lt py-2 px-3">
                            <i class="ti ti-school me-1"></i>Cổng thông tin Sinh viên
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
            <h3 class="text-dark fw-bold mb-2">Chưa có đợt thực tập hoặc đề tài đang mở</h3>
            <p class="text-secondary mx-auto mb-4" style="max-width: 520px;">
                <?php if ($isAdmin): ?>
                    Chào mừng bạn đến với Bảng điều khiển Quản trị. Với vai trò Quản trị viên, bạn có toàn quyền quản lý các đợt thực tập, duyệt đề tài, phân công giảng viên và quản lý tài khoản khi các phân hệ chức năng tương ứng được đưa vào vận hành.
                <?php else: ?>
                    Bạn đang đăng nhập với vai trò Sinh viên. Hiện tại chưa có đợt thực tập hoặc đề tài đồ án nào đang mở đăng ký. Dữ liệu đề tài, phân công giảng viên và tiến độ báo cáo tuần sẽ hiển thị tại đây khi bạn đăng ký thành công.
                <?php endif; ?>
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
