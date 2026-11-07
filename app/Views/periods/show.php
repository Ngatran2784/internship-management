<?php
$pageTitle       = 'Chi tiết đợt thực tập';
$pageHeading     = 'Chi tiết đợt thực tập';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'periods';
require dirname(__DIR__) . '/layouts/header.php';

$escape       = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$period       = $period       ?? [];
$typeLabels   = $typeLabels   ?? [];
$statusLabels = $statusLabels ?? [];
$success      = $success      ?? null;
$error        = $error        ?? null;

$status = (string)($period['status'] ?? 'draft');
$type   = (string)($period['type']   ?? 'internship');

$typeBadge = [
    'internship'          => 'bg-blue-lt text-blue',
    'graduation_project'  => 'bg-purple-lt text-purple',
];
$typeIcon = [
    'internship'         => 'ti-briefcase',
    'graduation_project' => 'ti-school',
];
$statusBadge = [
    'draft'  => 'bg-yellow-lt text-yellow',
    'active' => 'bg-success-lt text-success',
    'closed' => 'bg-secondary-lt text-secondary',
];

// Màu và icon theo trạng thái
$statusIcon = [
    'draft'  => 'ti-edit-circle',
    'active' => 'ti-player-play',
    'closed' => 'ti-lock',
];

$startFmt = !empty($period['start_date']) ? date('d/m/Y', strtotime($period['start_date'])) : '—';
$endFmt   = !empty($period['end_date'])   ? date('d/m/Y', strtotime($period['end_date']))   : '—';

// Tính số ngày
$daysInfo = '';
if (!empty($period['start_date']) && !empty($period['end_date'])) {
    $now   = new DateTimeImmutable('today');
    $start = new DateTimeImmutable($period['start_date']);
    $end   = new DateTimeImmutable($period['end_date']);
    if ($now < $start) {
        $daysInfo = 'Còn ' . $now->diff($start)->days . ' ngày đến khi bắt đầu';
    } elseif ($now > $end) {
        $daysInfo = 'Đã kết thúc ' . $end->diff($now)->days . ' ngày trước';
    } else {
        $daysLeft  = $now->diff($end)->days;
        $totalDays = $start->diff($end)->days;
        $daysInfo  = "Đang diễn ra · còn {$daysLeft}/{$totalDays} ngày";
    }
}

// Xác định các chuyển đổi trạng thái hợp lệ từ trạng thái hiện tại
$statusTransitions = [
    'draft'  => [
        'active' => ['label' => 'Mở đợt (Kích hoạt)', 'icon' => 'ti-player-play', 'btnClass' => 'btn-success', 'confirmMsg' => 'Bạn có chắc muốn kích hoạt đợt này? Sau khi mở, sinh viên và giảng viên có thể tham gia.'],
    ],
    'active' => [
        'draft'  => ['label' => 'Trả về Nháp',         'icon' => 'ti-rotate-clockwise', 'btnClass' => 'btn-warning',  'confirmMsg' => 'Trả về trạng thái nháp để chỉnh sửa thêm thông tin. Thao tác này sẽ tạm dừng đợt.'],
        'closed' => ['label' => 'Đóng đợt',             'icon' => 'ti-lock',             'btnClass' => 'btn-danger',   'confirmMsg' => 'Xác nhận đóng đợt này? Sau khi đóng, sinh viên không thể nộp thêm hồ sơ.'],
    ],
    'closed' => [
        'active' => ['label' => 'Mở lại đợt',           'icon' => 'ti-lock-open',        'btnClass' => 'btn-primary',  'confirmMsg' => 'Mở lại đợt này để tiếp tục xử lý (phúc khảo hoặc bổ sung hồ sơ)?'],
    ],
];

$currentTransitions = $statusTransitions[$status] ?? [];
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

    <!-- Back + Actions -->
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách
        </a>
        <a href="<?= $escape($baseUrl) ?>/periods/edit?id=<?= (int)($period['id'] ?? 0) ?>"
           class="btn btn-sm btn-primary">
            <i class="ti ti-pencil me-1"></i>Chỉnh sửa
        </a>
    </div>

    <div class="row g-4">

        <!-- Cột trái: chi tiết -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <!-- Header of detail card -->
                <div class="card-header py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-md <?= $type === 'graduation_project' ? 'bg-purple-lt text-purple' : 'bg-blue-lt text-blue' ?>">
                            <i class="ti <?= $escape($typeIcon[$type] ?? 'ti-calendar') ?> fs-2"></i>
                        </span>
                        <div>
                            <h3 class="card-title mb-0">
                                <?= $escape($period['name'] ?? '') ?>
                            </h3>
                            <div class="text-secondary small d-flex align-items-center gap-2 flex-wrap mt-1">
                                <span><i class="ti ti-hash"></i><?= $escape($period['code'] ?? '') ?></span>
                                <span class="badge <?= $escape($typeBadge[$type] ?? 'bg-secondary-lt') ?>">
                                    <i class="ti <?= $escape($typeIcon[$type] ?? 'ti-calendar') ?> me-1"></i>
                                    <?= $escape($typeLabels[$type] ?? $type) ?>
                                </span>
                                <span class="badge <?= $escape($statusBadge[$status] ?? 'bg-secondary-lt') ?>">
                                    <i class="ti <?= $escape($statusIcon[$status] ?? 'ti-circle') ?> me-1"></i>
                                    <?= $escape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Time info -->
                    <div class="card card-sm bg-primary-lt border-0 mb-4">
                        <div class="card-body py-3">
                            <div class="row align-items-center g-3">
                                <div class="col-auto text-center">
                                    <div class="text-secondary small">Bắt đầu</div>
                                    <div class="fw-bold fs-5 text-dark"><?= $escape($startFmt) ?></div>
                                </div>
                                <div class="col text-center text-secondary">
                                    <i class="ti ti-arrow-right fs-2 d-block"></i>
                                </div>
                                <div class="col-auto text-center">
                                    <div class="text-secondary small">Kết thúc</div>
                                    <div class="fw-bold fs-5 text-dark"><?= $escape($endFmt) ?></div>
                                </div>
                                <?php if ($daysInfo): ?>
                                    <div class="col-12 col-md-auto ms-md-auto">
                                        <span class="badge bg-primary text-white px-3 py-2">
                                            <i class="ti ti-clock me-1"></i><?= $escape($daysInfo) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-1 text-secondary small fw-semibold text-uppercase">
                        <i class="ti ti-file-text me-1"></i>Mô tả
                    </div>
                    <?php if (!empty($period['description'])): ?>
                        <p class="text-dark" style="line-height:1.7">
                            <?= nl2br($escape($period['description'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="text-secondary fst-italic">Chưa có mô tả cho đợt này.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cột phải: thao tác + metadata -->
        <div class="col-lg-4">

            <!-- Trạng thái hiện tại -->
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-info-circle me-1 text-primary"></i>Trạng thái đợt
                    </h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar avatar-sm <?= $escape($statusBadge[$status] ?? 'bg-secondary-lt') ?>">
                            <i class="ti <?= $escape($statusIcon[$status] ?? 'ti-circle') ?>"></i>
                        </span>
                        <div>
                            <div class="fw-semibold"><?= $escape($statusLabels[$status] ?? $status) ?></div>
                            <div class="text-secondary small">
                                <?php if ($status === 'draft'): ?>
                                    Chưa mở cho sinh viên tham gia
                                <?php elseif ($status === 'active'): ?>
                                    Đang mở — sinh viên có thể đăng ký
                                <?php else: ?>
                                    Đã đóng — không nhận thêm hồ sơ
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($currentTransitions)): ?>
                        <hr class="my-3">
                        <div class="small text-secondary fw-semibold mb-2 text-uppercase">
                            <i class="ti ti-transfer me-1"></i>Chuyển đổi trạng thái
                        </div>
                        <?php foreach ($currentTransitions as $newStatus => $transition): ?>
                            <button type="button"
                                    class="btn <?= $escape($transition['btnClass']) ?> w-100 mb-2 btn-status-change"
                                    data-period-id="<?= (int)($period['id'] ?? 0) ?>"
                                    data-new-status="<?= $escape($newStatus) ?>"
                                    data-confirm-msg="<?= $escape($transition['confirmMsg']) ?>">
                                <i class="ti <?= $escape($transition['icon']) ?> me-1"></i>
                                <?= $escape($transition['label']) ?>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-secondary small fst-italic">
                            Không có chuyển đổi trạng thái nào khả dụng.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-bolt me-1 text-orange"></i>Thao tác nhanh
                    </h4>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?= $escape($baseUrl) ?>/periods/edit?id=<?= (int)($period['id'] ?? 0) ?>"
                       class="btn btn-primary">
                        <i class="ti ti-pencil me-1"></i>Chỉnh sửa đợt
                    </a>
                    <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-outline-secondary">
                        <i class="ti ti-list me-1"></i>Về danh sách
                    </a>
                    <a href="<?= $escape($baseUrl) ?>/periods/create" class="btn btn-outline-primary">
                        <i class="ti ti-plus me-1"></i>Tạo đợt mới
                    </a>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-database me-1 text-secondary"></i>Thông tin hệ thống
                    </h4>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-secondary ps-3">ID</td>
                                <td class="fw-semibold pe-3">#<?= (int)($period['id'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary ps-3">Loại</td>
                                <td class="pe-3">
                                    <span class="badge <?= $escape($typeBadge[$type] ?? 'bg-secondary-lt') ?>">
                                        <?= $escape($typeLabels[$type] ?? $type) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-secondary ps-3">Trạng thái</td>
                                <td class="pe-3">
                                    <span class="badge <?= $escape($statusBadge[$status] ?? 'bg-secondary-lt') ?>">
                                        <?= $escape($statusLabels[$status] ?? $status) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php if (!empty($period['created_at'])): ?>
                                <tr>
                                    <td class="text-secondary ps-3">Ngày tạo</td>
                                    <td class="pe-3"><?= $escape(date('d/m/Y', strtotime($period['created_at']))) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($period['updated_at'])): ?>
                                <tr>
                                    <td class="text-secondary ps-3">Cập nhật</td>
                                    <td class="pe-3"><?= $escape(date('d/m/Y H:i', strtotime($period['updated_at']))) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ─────────────────────────────────────────────────────
     Modal xác nhận chuyển trạng thái (POST form)
     Form thực sự ẩn trong modal, submit khi user xác nhận
───────────────────────────────────────────────────────── -->
<div class="modal modal-blur fade" id="modalConfirmStatus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-alert-triangle me-2 text-warning"></i>Xác nhận thao tác
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <p id="modal-confirm-text" class="mb-0">Bạn có chắc chắn muốn thực hiện thao tác này?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Hủy
                </button>
                <!-- Form POST thực sự — được điền ID và status từ JS khi mở modal -->
                <form id="form-status-change" action="<?= $escape($baseUrl) ?>/periods/status" method="POST" class="m-0">
                    <input type="hidden" name="_csrf"     value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="id"        id="modal-period-id"     value="">
                    <input type="hidden" name="status"    id="modal-new-status"    value="">
                    <input type="hidden" name="redirect"  value="detail">
                    <button type="submit" id="modal-confirm-btn" class="btn btn-danger">
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

    document.querySelectorAll('.btn-status-change').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var periodId   = btn.dataset.periodId;
            var newStatus  = btn.dataset.newStatus;
            var confirmMsg = btn.dataset.confirmMsg;
            var btnClass   = btn.className.match(/btn-(success|warning|danger|primary)/);
            var cls        = btnClass ? btnClass[0] : 'btn-primary';

            // Điền dữ liệu vào form trong modal
            document.getElementById('modal-period-id').value  = periodId;
            document.getElementById('modal-new-status').value = newStatus;
            document.getElementById('modal-confirm-text').textContent = confirmMsg;

            // Đổi màu nút xác nhận theo loại hành động
            var confirmBtn = document.getElementById('modal-confirm-btn');
            confirmBtn.className = 'btn ' + cls;

            // Mở modal Bootstrap 5
            var modal = new bootstrap.Modal(document.getElementById('modalConfirmStatus'));
            modal.show();
        });
    });

    // Tự động đóng modal khi form submit thành công
    document.getElementById('form-status-change').addEventListener('submit', function () {
        var modal = bootstrap.Modal.getInstance(document.getElementById('modalConfirmStatus'));
        if (modal) modal.hide();
    });
})();
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
