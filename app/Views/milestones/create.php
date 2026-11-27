<?php
$pageTitle = 'Thêm mốc tiến độ mới';
$pageHeading = 'Thêm mốc tiến độ mới';
$activePage = 'milestones';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);

$allPeriods = is_array($allPeriods ?? null) ? $allPeriods : [];
$selectedPeriodId = filter_var($selectedPeriodId ?? null, FILTER_VALIDATE_INT) ?: null;
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
?>

<div class="container-xl my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/milestones">Mốc tiến độ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm mới</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h3 class="card-title text-white m-0">
                        <i class="ti ti-calendar-plus me-2"></i>Thêm mốc tiến độ thực tập / đồ án mới
                    </h3>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="card-body pb-0">
                        <div class="alert alert-danger mb-0" role="alert">
                            <i class="ti ti-alert-circle me-2"></i><?= $escape($error) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $escape($baseUrl) ?>/milestones/create" class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">

                    <div class="mb-3">
                        <label for="period_id" class="form-label required fw-bold text-dark">Đợt thực tập / đồ án áp dụng:</label>
                        <select name="period_id" id="period_id" class="form-select" required>
                            <option value="">-- Chọn đợt thực tập / đồ án --</option>
                            <?php foreach ($allPeriods as $p): ?>
                                <?php
                                $pId = (int)$p['id'];
                                $isSel = ($pId === (int)($oldInput['period_id'] ?? $selectedPeriodId));
                                ?>
                                <option value="<?= $pId ?>" <?= $isSel ? 'selected' : '' ?>>
                                    [<?= $escape($p['code']) ?>] <?= $escape($p['name']) ?> (<?= $escape($p['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label required fw-bold text-dark">Tên mốc tiến độ:</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Ví dụ: Nộp đề cương chi tiết thực tập..." value="<?= $escape($oldInput['title'] ?? '') ?>" required maxlength="255">
                        <small class="form-hint">Tên gọi ngắn gọn, rõ ràng về công việc sinh viên cần hoàn thành tại mốc này.</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold text-dark">Yêu cầu &amp; Hướng dẫn thực hiện:</label>
                        <textarea name="description" id="description" class="form-control" rows="4" placeholder="Mô tả chi tiết nội dung, cấu trúc báo cáo cần nộp, tiêu chí đánh giá..."><?= $escape($oldInput['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label required fw-bold text-dark">Ngày bắt đầu:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $escape($oldInput['start_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label required fw-bold text-dark">Hạn chót hoàn thành (Ngày &amp; Giờ):</label>
                            <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="<?= $escape($oldInput['due_date'] ?? date('Y-m-d\TH:i', strtotime('+14 days 23:59'))) ?>" required>
                            <small class="form-hint">Hạn nộp phải sau hoặc bằng ngày bắt đầu.</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="order_index" class="form-label fw-bold text-dark">Thứ tự hiển thị:</label>
                            <input type="number" name="order_index" id="order_index" class="form-control" min="1" max="100" value="<?= $escape($oldInput['order_index'] ?? '1') ?>">
                            <small class="form-hint">Mốc có số thứ tự nhỏ hơn sẽ được ưu tiên hiển thị trước.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-bold text-dark">Trạng thái mốc:</label>
                            <select name="status" id="status" class="form-select">
                                <?php foreach ($statusLabels as $sVal => $sText): ?>
                                    <option value="<?= $escape($sVal) ?>" <?= (($oldInput['status'] ?? 'pending') === $sVal) ? 'selected' : '' ?>>
                                        <?= $escape($sText) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-check">
                            <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" <?= (!isset($oldInput['is_mandatory']) || $oldInput['is_mandatory']) ? 'checked' : '' ?>>
                            <span class="form-check-label fw-bold text-dark">Mốc bắt buộc (Bắt buộc sinh viên phải nộp báo cáo để hoàn thành đợt)</span>
                        </label>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="<?= $escape($baseUrl) ?>/milestones<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>Lưu mốc tiến độ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
