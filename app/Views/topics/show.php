<?php
$pageTitle = 'Chi tiết đề tài';
$pageHeading = 'Chi tiết đề tài';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'topics';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$statusLabels = $statusLabels ?? [];
$typeLabels = $typeLabels ?? [];
$statusBadges = $statusBadges ?? [];
$typeBadges = $typeBadges ?? [];
$roles = $_SESSION['roles'] ?? [];
$isReviewer = (bool)array_intersect(['admin', 'training_dept', 'department_head'], $roles);
?>
<div class="container-xl">
    <?php if (!empty($success)): ?><div class="alert alert-success" role="alert"><?= $escape($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
        <a class="btn btn-outline-secondary align-self-start" href="<?= $escape($baseUrl) ?>/topics"><i class="ti ti-arrow-left me-1"></i>Danh sách</a>
        <div class="d-flex flex-wrap gap-2">
            <?php if (!empty($canEdit)): ?>
                <a class="btn btn-outline-primary" href="<?= $escape($baseUrl) ?>/topics/edit?id=<?= (int)$topic['id'] ?>">
                    <i class="ti ti-edit me-1"></i>Chỉnh sửa
                </a>
            <?php endif; ?>
            <?php if (!empty($canSubmit)): ?>
                <form action="<?= $escape($baseUrl) ?>/topics/submit" method="POST"
                      onsubmit="return confirm('Gửi đề tài này để xét duyệt?');">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-send me-1"></i>Gửi xét duyệt</button>
                </form>
            <?php endif; ?>
            <?php if ($isReviewer && $topic['status'] === 'pending'): ?>
                <a class="btn btn-warning" href="<?= $escape($baseUrl) ?>/topics/pending">Danh sách chờ duyệt</a>
            <?php endif; ?>
            <?php if ((int)$topic['proposer_id'] === (int)($_SESSION['user_id'] ?? 0) || $isReviewer): ?>
                <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics/history?id=<?= (int)$topic['id'] ?>">
                    <i class="ti ti-history me-1"></i>Lịch sử xét duyệt
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="row row-cards">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div><div class="text-secondary small">Mã đề tài</div><h2 class="card-title"><?= $escape($topic['code']) ?></h2></div>
                    <span class="badge <?= $escape($statusBadges[$topic['status']] ?? 'bg-secondary-lt') ?>">
                        <?= $escape($statusLabels[$topic['status']] ?? $topic['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <h3><?= $escape($topic['name']) ?></h3>
                    <div class="mb-4"><span class="badge <?= $escape($typeBadges[$topic['type']] ?? 'bg-secondary-lt') ?>">
                        <?= $escape($typeLabels[$topic['type']] ?? $topic['type']) ?>
                    </span></div>
                    <h4 class="subheader">Mô tả</h4>
                    <div class="text-secondary text-break"><?= nl2br($escape($topic['description'] ?: 'Chưa có mô tả.')) ?></div>
                </div>
                <div class="card-footer text-secondary small">
                    Tạo lúc <?= $escape($topic['created_at'] ?? '') ?> · Cập nhật <?= $escape($topic['updated_at'] ?? '') ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Đợt liên kết</h3></div>
                <div class="card-body">
                    <div class="fw-semibold"><?= $escape($topic['period_name']) ?></div>
                    <div class="text-secondary"><?= $escape($topic['period_code']) ?></div>
                    <div class="mt-2">Loại đợt: <?= $escape($typeLabels[$topic['period_type']] ?? $topic['period_type']) ?></div>
                    <div>Trạng thái đợt: <?= $escape(['draft' => 'Bản nháp', 'active' => 'Đang diễn ra', 'closed' => 'Đã kết thúc'][$topic['period_status']] ?? $topic['period_status']) ?></div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Người đề xuất</h3></div>
                <div class="card-body">
                    <div class="fw-semibold"><?= $escape($topic['proposer_name']) ?></div>
                    <div class="text-secondary"><?= $escape($topic['proposer_username']) ?></div>
                    <div class="text-secondary"><?= $escape($topic['proposer_email']) ?></div>
                    <div class="mt-3">Số sinh viên tối đa: <strong><?= (int)$topic['max_students'] ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
