<?php
$pageTitle       = 'Quản lý đợt thực tập & đồ án';
$pageHeading     = 'Quản lý đợt thực tập & đồ án';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'periods';
require dirname(__DIR__) . '/layouts/header.php';

$escape       = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$periods      = $periods      ?? [];
$filters      = $filters      ?? ['type' => '', 'status' => '', 'keyword' => ''];
$typeLabels   = $typeLabels   ?? [];
$statusLabels = $statusLabels ?? [];
$success      = $success      ?? null;
$error        = $error        ?? null;

$countActive = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'active'));
$countDraft  = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'draft'));
$countClosed = count(array_filter($periods, fn($p) => ($p['status'] ?? '') === 'closed'));
?>
<div class="container-xl">

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ti ti-circle-check fs-3"></i>
            <div><?= $escape($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ti ti-alert-circle fs-3"></i>
            <div><?= $escape($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-blue-lt text-blue">
                                <i class="ti ti-calendar fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Tổng số đợt</div>
                            <div class="h3 mb-0"><?= count($periods) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-green-lt text-green">
                                <i class="ti ti-circle-check fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Đang mở</div>
                            <div class="h3 mb-0"><?= $countActive ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-yellow-lt text-yellow">
                                <i class="ti ti-edit-circle fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Đang soạn</div>
                            <div class="h3 mb-0"><?= $countDraft ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-secondary-lt text-secondary">
                                <i class="ti ti-lock fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Đã đóng</div>
                            <div class="h3 mb-0"><?= $countClosed ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter + Table card -->
    <div class="card shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center gap-3 py-3">
            <div class="flex-fill">
                <form method="GET" action="<?= $escape($baseUrl) ?>/periods"
                      class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label visually-hidden" for="type-filter">Loại đợt</label>
                        <select class="form-select form-select-sm" id="type-filter" name="type"
                                style="min-width:140px">
                            <option value="">Tất cả loại</option>
                            <?php foreach ($typeLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>"
                                    <?= ($filters['type'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label visually-hidden" for="status-filter">Trạng thái</label>
                        <select class="form-select form-select-sm" id="status-filter" name="status"
                                style="min-width:140px">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach ($statusLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>"
                                    <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label class="form-label visually-hidden" for="keyword-filter">Tìm kiếm</label>
                        <input type="text" class="form-control form-control-sm"
                               id="keyword-filter" name="keyword"
                               value="<?= $escape($filters['keyword'] ?? '') ?>"
                               placeholder="Mã hoặc tên đợt...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-search me-1"></i>Tìm
                        </button>
                        <?php if (!empty($filters['type']) || !empty($filters['status']) || !empty($filters['keyword'])): ?>
                            <a href="<?= $escape($baseUrl) ?>/periods"
                               class="btn btn-sm btn-outline-secondary ms-1">
                                <i class="ti ti-x me-1"></i>Xóa lọc
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0">
                <a href="<?= $escape($baseUrl) ?>/periods/create" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>Tạo đợt mới
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th class="w-1 text-secondary">#</th>
                        <th>Tên đợt</th>
                        <th>Loại</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($periods)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class="ti ti-calendar-off d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                            <div class="fw-medium">Chưa có đợt nào</div>
                            <div class="small mt-1">
                                <a href="<?= $escape($baseUrl) ?>/periods/create">Tạo đợt thực tập đầu tiên</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periods as $period): ?>
                        <?php
                            $pStatus = (string)($period['status'] ?? 'draft');
                            $pType   = (string)($period['type']   ?? 'internship');

                            // Format dates
                            $startFmt = !empty($period['start_date']) ? date('d/m/Y', strtotime($period['start_date'])) : '—';
                            $endFmt   = !empty($period['end_date'])   ? date('d/m/Y', strtotime($period['end_date']))   : '—';

                            $typeBadgeMap = [
                                'internship'         => 'bg-blue-lt text-blue',
                                'graduation_project' => 'bg-purple-lt text-purple',
                            ];
                            $typeIconMap = [
                                'internship'         => 'ti-briefcase',
                                'graduation_project' => 'ti-school',
                            ];
                            $statusBadgeMap = [
                                'draft'  => 'bg-yellow-lt text-yellow',
                                'active' => 'bg-success-lt text-success',
                                'closed' => 'bg-secondary-lt text-secondary',
                            ];

                            // Xác định nút đổi trạng thái nhanh
                            $quickToggle = null;
                            if ($pStatus === 'draft') {
                                $quickToggle = ['newStatus' => 'active', 'label' => 'Mở đợt',  'icon' => 'ti-player-play', 'cls' => 'btn-outline-success',  'msg' => 'Kích hoạt đợt này cho sinh viên tham gia?'];
                            } elseif ($pStatus === 'active') {
                                $quickToggle = ['newStatus' => 'closed', 'label' => 'Đóng đợt', 'icon' => 'ti-lock',        'cls' => 'btn-outline-danger',   'msg' => 'Đóng đợt này? Sinh viên sẽ không nộp thêm được.'];
                            } elseif ($pStatus === 'closed') {
                                $quickToggle = ['newStatus' => 'active', 'label' => 'Mở lại',   'icon' => 'ti-lock-open',   'cls' => 'btn-outline-primary',  'msg' => 'Mở lại đợt này?'];
                            }
                        ?>
                        <tr>
                            <td class="text-secondary small"><?= (int)$period['id'] ?></td>
                            <td>
                                <div class="fw-semibold text-dark mb-1">
                                    <a href="<?= $escape($baseUrl) ?>/periods/detail?id=<?= (int)$period['id'] ?>"
                                       class="text-reset text-decoration-none link-hover">
                                        <?= $escape($period['name'] ?? '') ?>
                                    </a>
                                </div>
                                <div class="text-secondary small">
                                    <i class="ti ti-hash"></i><?= $escape($period['code'] ?? '') ?>
                                    <?php if (!empty($period['description'])): ?>
                                        &bull; <?= $escape(mb_substr($period['description'], 0, 50)) ?><?= mb_strlen($period['description']) > 50 ? '…' : '' ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $escape($typeBadgeMap[$pType] ?? 'bg-secondary-lt text-secondary') ?>">
                                    <i class="ti <?= $escape($typeIconMap[$pType] ?? 'ti-calendar') ?> me-1"></i>
                                    <?= $escape($typeLabels[$pType] ?? $pType) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small">
                                    <i class="ti ti-calendar me-1 text-secondary"></i><?= $escape($startFmt) ?>
                                </div>
                                <div class="small text-secondary">
                                    <i class="ti ti-calendar-off me-1"></i><?= $escape($endFmt) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $escape($statusBadgeMap[$pStatus] ?? 'bg-secondary-lt') ?>">
                                    <?= $escape($statusLabels[$pStatus] ?? $pStatus) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <a href="<?= $escape($baseUrl) ?>/periods/detail?id=<?= (int)$period['id'] ?>"
                                       class="btn btn-sm btn-outline-info">
                                        <i class="ti ti-eye me-1"></i>Chi tiết
                                    </a>
                                    <a href="<?= $escape($baseUrl) ?>/periods/edit?id=<?= (int)$period['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-pencil me-1"></i>Sửa
                                    </a>
                                    <?php if ($quickToggle !== null): ?>
                                        <button type="button"
                                                class="btn btn-sm <?= $escape($quickToggle['cls']) ?> btn-quick-toggle"
                                                data-period-id="<?= (int)$period['id'] ?>"
                                                data-new-status="<?= $escape($quickToggle['newStatus']) ?>"
                                                data-confirm-msg="<?= $escape($quickToggle['msg']) ?>">
                                            <i class="ti <?= $escape($quickToggle['icon']) ?> me-1"></i><?= $escape($quickToggle['label']) ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($periods)): ?>
            <div class="card-footer d-flex align-items-center justify-content-between py-2">
                <p class="m-0 text-secondary small">
                    Hiển thị <strong><?= count($periods) ?></strong> đợt
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal xác nhận đổi trạng thái nhanh -->
<div class="modal modal-blur fade" id="modalQuickToggle" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-transfer me-2 text-primary"></i>Xác nhận thao tác
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p id="qt-confirm-text" class="mb-0">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Hủy
                </button>
                <form id="form-quick-toggle" action="<?= $escape($baseUrl) ?>/periods/status" method="POST" class="m-0">
                    <input type="hidden" name="_csrf"   value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="id"      id="qt-period-id"   value="">
                    <input type="hidden" name="status"  id="qt-new-status"  value="">
                    <button type="submit" id="qt-confirm-btn" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>Xác nhận
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    document.querySelectorAll('.btn-quick-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('qt-period-id').value  = btn.dataset.periodId;
            document.getElementById('qt-new-status').value = btn.dataset.newStatus;
            document.getElementById('qt-confirm-text').textContent = btn.dataset.confirmMsg;

            var modal = new bootstrap.Modal(document.getElementById('modalQuickToggle'));
            modal.show();
        });
    });
})();
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
