<?php
$pageTitle = 'Chỉnh sửa mốc tiến độ';
$pageHeading = 'Chỉnh sửa mốc tiến độ';
$activePage = 'milestones';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);

$milestone = is_array($milestone ?? null) ? $milestone : [];
$allPeriods = is_array($allPeriods ?? null) ? $allPeriods : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$mId = (int)($milestone['id'] ?? 0);
?>

<div class="container-xl my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/milestones">Mốc tiến độ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chỉnh sửa #<?= $mId ?></li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="card-title text-white m-0">
                        <i class="ti ti-edit me-2"></i>Chỉnh sửa mốc tiến độ #<?= $mId ?>: <?= $escape($milestone['title'] ?? '') ?>
                    </h3>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="card-body pb-0">
                        <div class="alert alert-danger mb-0" role="alert">
                            <i class="ti ti-alert-circle me-2"></i><?= $escape($error) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $escape($baseUrl) ?>/milestones/edit?id=<?= $mId ?>" class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= $mId ?>">

                    <div class="mb-3">
                        <label for="period_id" class="form-label required fw-bold text-dark">Đợt thực tập / đồ án áp dụng:</label>
                        <select name="period_id" id="period_id" class="form-select" required>
                            <?php foreach ($allPeriods as $p): ?>
                                <?php
                                $pId = (int)$p['id'];
                                $currPeriodId = (int)($oldInput['period_id'] ?? ($milestone['period_id'] ?? 0));
                                ?>
                                <option value="<?= $pId ?>" <?= ($pId === $currPeriodId) ? 'selected' : '' ?>>
                                    [<?= $escape($p['code']) ?>] <?= $escape($p['name']) ?> (<?= $escape($p['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label required fw-bold text-dark">Tên mốc tiến độ:</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= $escape($oldInput['title'] ?? ($milestone['title'] ?? '')) ?>" required maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold text-dark">Yêu cầu &amp; Hướng dẫn thực hiện:</label>
                        <textarea name="description" id="description" class="form-control" rows="4"><?= $escape($oldInput['description'] ?? ($milestone['description'] ?? '')) ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label required fw-bold text-dark">Ngày bắt đầu:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $escape($oldInput['start_date'] ?? ($milestone['start_date'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label required fw-bold text-dark">Hạn chót hoàn thành:</label>
                            <?php
                            $defaultDueDate = !empty($milestone['due_date']) ? date('Y-m-d\TH:i', strtotime($milestone['due_date'])) : '';
                            ?>
                            <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="<?= $escape($oldInput['due_date'] ?? $defaultDueDate) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="order_index" class="form-label fw-bold text-dark">Thứ tự hiển thị:</label>
                            <input type="number" name="order_index" id="order_index" class="form-control" min="1" max="100" value="<?= (int)($oldInput['order_index'] ?? ($milestone['order_index'] ?? 1)) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-bold text-dark">Trạng thái mốc:</label>
                            <select name="status" id="status" class="form-select">
                                <?php foreach ($statusLabels as $sVal => $sText): ?>
                                    <?php
                                    $currStatus = $oldInput['status'] ?? ($milestone['status'] ?? 'pending');
                                    ?>
                                    <option value="<?= $escape($sVal) ?>" <?= ($currStatus === $sVal) ? 'selected' : '' ?>>
                                        <?= $escape($sText) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-check">
                            <?php
                            $isMandatory = isset($oldInput['is_mandatory']) ? (bool)$oldInput['is_mandatory'] : (bool)($milestone['is_mandatory'] ?? true);
                            ?>
                            <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" <?= $isMandatory ? 'checked' : '' ?>>
                            <span class="form-check-label fw-bold text-dark">Mốc bắt buộc</span>
                        </label>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="<?= $escape($baseUrl) ?>/milestones?period_id=<?= (int)($milestone['period_id'] ?? 0) ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i>Hủy &amp; Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
