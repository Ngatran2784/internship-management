<?php
$pageTitle       = 'Bảng điều khiển tổng quan - Hệ thống quản lý thực tập và đồ án sinh viên';
$pageHeading     = 'Bảng điều khiển tổng quan';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'dashboard';
require dirname(__DIR__) . '/layouts/header.php';

// Tự động đồng bộ từ Model InternshipPeriod nếu view được gọi trực tiếp mà không qua Controller
if (!isset($activePeriods) && class_exists(\App\Models\InternshipPeriod::class)) {
    try {
        $allPeriods = \App\Models\InternshipPeriod::all();
    } catch (\Throwable $e) {
        $allPeriods = [];
    }
    $totalPeriods  = count($allPeriods);
    $activePeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === \App\Models\InternshipPeriod::STATUS_ACTIVE));
    $draftPeriods  = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === \App\Models\InternshipPeriod::STATUS_DRAFT));
    $closedPeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === \App\Models\InternshipPeriod::STATUS_CLOSED));
    $countActive   = count($activePeriods);
    $countDraft    = count($draftPeriods);
    $countClosed   = count($closedPeriods);
    $typeLabels    = \App\Models\InternshipPeriod::TYPE_LABELS;
    $statusLabels  = \App\Models\InternshipPeriod::STATUS_LABELS;
}

$activePeriods = $activePeriods ?? [];
$typeLabels    = $typeLabels    ?? [];
$statusLabels  = $statusLabels  ?? [];
$totalPeriods  = $totalPeriods  ?? count($activePeriods);
$countActive   = $countActive   ?? count($activePeriods);
$countDraft    = $countDraft    ?? 0;
$countClosed   = $countClosed   ?? 0;
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

    <!-- 4 Cards thống kê theo dõi tiến độ các module -->
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

    <!-- Tiến độ thực tập & đồ án sinh viên (Đồng bộ thời gian thực từ bảng internship_periods) -->
    <section class="card shadow-sm" aria-labelledby="internship-progress-title">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h3 class="card-title text-dark m-0" id="internship-progress-title">
                <i class="ti ti-timeline me-2 text-primary"></i>Tiến độ thực tập &amp; đồ án sinh viên
            </h3>
            <?php if (!empty($canManagePeriods)): ?>
                <div class="card-actions">
                    <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-calendar-event me-1"></i>Quản lý đợt thực tập &amp; đồ án
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($activePeriods)): ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th class="w-1 text-secondary">#</th>
                            <th>Tên đợt thực tập / đồ án</th>
                            <th>Loại đợt</th>
                            <th>Thời gian thực hiện</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activePeriods as $p): ?>
                            <?php
                                $pId       = (int)($p['id'] ?? 0);
                                $pName     = (string)($p['name'] ?? '');
                                $pCode     = (string)($p['code'] ?? '');
                                $pType     = (string)($p['type'] ?? 'internship');
                                $typeLabel = $typeLabels[$pType] ?? ($pType === 'graduation_project' ? 'Đồ án tốt nghiệp' : 'Thực tập tốt nghiệp');
                                $startFmt  = !empty($p['start_date']) ? date('d/m/Y', strtotime($p['start_date'])) : '—';
                                $endFmt    = !empty($p['end_date'])   ? date('d/m/Y', strtotime($p['end_date']))   : '—';
                            ?>
                            <tr>
                                <td class="text-secondary small"><?= $pId ?></td>
                                <td>
                                    <div class="fw-semibold text-dark mb-1">
                                        <a href="<?= $escape($baseUrl) ?>/periods/<?= $pId ?>" class="text-reset text-decoration-none">
                                            <?= $escape($pName) ?>
                                        </a>
                                    </div>
                                    <div class="text-secondary small">
                                        <i class="ti ti-hash"></i><?= $escape($pCode) ?>
                                        <?php if (!empty($p['description'])): ?>
                                            &bull; <?= $escape(mb_substr($p['description'], 0, 70)) ?><?= mb_strlen($p['description']) > 70 ? '…' : '' ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $pType === 'graduation_project' ? 'bg-purple-lt text-purple' : 'bg-blue-lt text-blue' ?>">
                                        <i class="ti <?= $pType === 'graduation_project' ? 'ti-school' : 'ti-briefcase' ?> me-1"></i>
                                        <?= $escape($typeLabel) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <i class="ti ti-calendar me-1 text-secondary"></i>Từ: <?= $escape($startFmt) ?>
                                    </div>
                                    <div class="small text-secondary">
                                        <i class="ti ti-calendar-off me-1"></i>Đến: <?= $escape($endFmt) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-lt text-success fw-medium">
                                        <i class="ti ti-circle-check me-1"></i>Đang diễn ra
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $escape($baseUrl) ?>/periods/<?= $pId ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye me-1"></i>Xem chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex flex-wrap align-items-center justify-content-between py-2 small text-secondary">
                <div>
                    Đang hiển thị <strong><?= count($activePeriods) ?></strong> đợt đang mở
                    &bull; Tổng số đợt: <strong><?= (int)$totalPeriods ?></strong>
                    (Bản nháp: <strong><?= (int)$countDraft ?></strong>, Đã đóng: <strong><?= (int)$countClosed ?></strong>)
                </div>
                <?php if (!empty($canManagePeriods)): ?>
                    <div>
                        <a href="<?= $escape($baseUrl) ?>/periods" class="text-primary fw-medium text-decoration-none">
                            Quản lý chi tiết đợt <i class="ti ti-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card-body py-4 text-center">
                <span class="empty-state-icon mb-3"><i class="ti ti-clipboard-off fs-1 text-secondary"></i></span>
                <h3 class="text-dark fw-bold mb-2">Hiện chưa có đợt thực tập hoặc đồ án đang mở</h3>
                <p class="text-secondary mx-auto mb-4" style="max-width: 520px;">
                    <?php if ($isAdmin || !empty($canManagePeriods)): ?>
                        Hiện tại không có đợt thực tập hoặc đồ án nào ở trạng thái "Đang diễn ra" (active). Bạn có thể mở một đợt đang soạn thảo hoặc tạo đợt mới để sinh viên và giảng viên bắt đầu tham gia.
                    <?php else: ?>
                        Hiện tại Nhà trường chưa mở đợt thực tập hoặc đồ án tốt nghiệp nào. Thông tin đợt, đề tài đăng ký và phân công giảng viên sẽ hiển thị tại đây khi đợt được kích hoạt.
                    <?php endif; ?>
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <?php if (!empty($canManagePeriods)): ?>
                        <a href="<?= $escape($baseUrl) ?>/periods/create" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i>Tạo đợt mới
                        </a>
                        <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-calendar-event me-1"></i>Xem tất cả đợt (<?= (int)$totalPeriods ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
