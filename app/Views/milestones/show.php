<?php
$pageTitle = 'Chi tiết mốc tiến độ';
$pageHeading = 'Chi tiết mốc tiến độ';
$activePage = 'milestones';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);

$milestone = is_array($milestone ?? null) ? $milestone : [];
$canManage = (bool)($canManage ?? false);
$isStudent = (bool)($isStudent ?? false);
$mId = (int)($milestone['id'] ?? 0);
$mStatus = $milestone['status'] ?? 'pending';
$statusBadge = $statusBadges[$mStatus] ?? 'bg-secondary-lt text-secondary';
$statusText  = $statusLabels[$mStatus] ?? $mStatus;
$isOverdue   = strtotime($milestone['due_date'] ?? 'now') < time() && $mStatus !== 'completed';
?>

<div class="container-xl my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/milestones?period_id=<?= (int)($milestone['period_id'] ?? 0) ?>">Mốc tiến độ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi tiết #<?= $mId ?></li>
        </ol>
    </nav>

    <!-- Flash message -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-alert-circle me-2"></i><?= $escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i><?= $escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                    <h3 class="card-title text-dark m-0">
                        <i class="ti ti-bookmark text-primary me-2"></i>Thông tin mốc tiến độ #<?= $mId ?>
                    </h3>
                    <div>
                        <span class="badge <?= $escape($statusBadge) ?> px-2 py-1 fs-3">
                            <?= $escape($statusText) ?>
                        </span>
                        <?php if (!empty($milestone['is_mandatory'])): ?>
                            <span class="badge bg-red-lt text-red px-2 py-1 ms-1">Bắt buộc</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body">
                    <h2 class="text-primary fw-bold mb-3"><?= $escape($milestone['title'] ?? '') ?></h2>

                    <dl class="row mb-3">
                        <dt class="col-sm-3 text-secondary">Đợt áp dụng:</dt>
                        <dd class="col-sm-9 fw-bold text-dark">
                            [<?= $escape($milestone['period_code'] ?? '') ?>] <?= $escape($milestone['period_name'] ?? '') ?>
                        </dd>

                        <dt class="col-sm-3 text-secondary">Ngày bắt đầu:</dt>
                        <dd class="col-sm-9 text-dark">
                            <i class="ti ti-calendar me-1"></i><?= date('d/m/Y', strtotime($milestone['start_date'] ?? 'now')) ?>
                        </dd>

                        <dt class="col-sm-3 text-secondary">Hạn chót hoàn thành:</dt>
                        <dd class="col-sm-9 <?= $isOverdue ? 'text-danger fw-bold' : 'text-dark' ?>">
                            <i class="ti ti-clock me-1"></i><?= date('d/m/Y H:i:s', strtotime($milestone['due_date'] ?? 'now')) ?>
                            <?php if ($isOverdue): ?>
                                <span class="badge bg-danger-lt text-danger ms-2">Đã quá thời hạn</span>
                            <?php endif; ?>
                        </dd>

                        <dt class="col-sm-3 text-secondary">Thứ tự hiển thị:</dt>
                        <dd class="col-sm-9 text-secondary">Mốc số #<?= (int)($milestone['order_index'] ?? 1) ?></dd>

                        <dt class="col-sm-3 text-secondary">Người tạo mốc:</dt>
                        <dd class="col-sm-9 text-secondary">
                            <?= $escape($milestone['creator_name'] ?? 'Hệ thống') ?> (<code><?= $escape($milestone['creator_username'] ?? '') ?></code>)
                        </dd>

                        <dt class="col-sm-3 text-secondary">Thời điểm tạo:</dt>
                        <dd class="col-sm-9 text-secondary">
                            <?= date('d/m/Y H:i', strtotime($milestone['created_at'] ?? 'now')) ?>
                        </dd>
                    </dl>

                    <div class="p-3 bg-light rounded mt-3">
                        <h4 class="text-dark fw-bold mb-2"><i class="ti ti-file-description me-1"></i>Yêu cầu &amp; Hướng dẫn thực hiện:</h4>
                        <div class="text-secondary" style="white-space: pre-line;">
                            <?= !empty($milestone['description']) ? $escape($milestone['description']) : '<em>(Không có nội dung mô tả chi tiết)</em>' ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex align-items-center justify-content-between py-3">
                    <a href="<?= $escape($baseUrl) ?>/milestones?period_id=<?= (int)($milestone['period_id'] ?? 0) ?>" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách
                    </a>
                    <?php if ($canManage): ?>
                        <div class="btn-list">
                            <a href="<?= $escape($baseUrl) ?>/milestones/edit?id=<?= $mId ?>" class="btn btn-primary">
                                <i class="ti ti-edit me-1"></i>Chỉnh sửa mốc
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Thẻ trợ giúp / Thông tin nhanh -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary-lt py-3">
                    <h4 class="card-title text-primary m-0"><i class="ti ti-info-circle me-2"></i>Quy định mốc tiến độ</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled text-secondary small mb-0">
                        <li class="mb-2">
                            <i class="ti ti-check text-success me-1"></i> Sinh viên cần hoàn thành nộp đề cương / báo cáo trước thời hạn chót quy định.
                        </li>
                        <li class="mb-2">
                            <i class="ti ti-alert-triangle text-warning me-1"></i> Các bài nộp sau thời điểm hạn chót sẽ bị hệ thống tự động gắn nhãn <strong>Nộp muộn (is_late)</strong>.
                        </li>
                        <li class="mb-0">
                            <i class="ti ti-lock text-danger me-1"></i> Mốc tiến độ đã có sinh viên nộp báo cáo sẽ được bảo vệ, không thể xóa tùy tiện để đảm bảo toàn vẹn dữ liệu.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
