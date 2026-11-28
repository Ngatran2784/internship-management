<?php
$isEdit = is_array($log ?? null);
$pageTitle = $isEdit ? 'Cập nhật nhật ký tuần' : 'Tạo nhật ký tuần';
$pageHeading = $pageTitle;
$activePage = 'internship-logs';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$log = is_array($log ?? null) ? $log : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$value = static fn(string $key, mixed $fallback = ''): mixed => $oldInput[$key] ?? ($log[$key] ?? $fallback);
$action = $isEdit ? '/internship-logs/update' : '/internship-logs/store';
?>

<div class="container-xl my-4">
    <div class="mb-4">
        <a href="<?= $escape($baseUrl) ?>/internship-logs" class="btn btn-outline-secondary btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Danh sách nhật ký</a>
        <h2 class="h2 mb-1"><i class="ti ti-notebook text-primary me-2"></i><?= $escape($pageTitle) ?></h2>
        <?php if (!empty($assignment)): ?><p class="text-secondary mb-0"><?= $escape($assignment['topic_name'] ?? '') ?> · <?= $escape($assignment['period_name'] ?? '') ?></p><?php endif; ?>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>

    <form method="post" action="<?= $escape($baseUrl . $action) ?>" class="card">
        <div class="card-body">
            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$log['id'] ?>"><?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="week_number">Tuần thực tập <span class="text-danger">*</span></label>
                    <input class="form-control" type="number" min="1" id="week_number" name="week_number" value="<?= $escape($value('week_number')) ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
                    <?php if ($isEdit): ?><div class="form-hint">Số tuần được giữ nguyên khi cập nhật.</div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="log_date">Ngày ghi nhật ký <span class="text-danger">*</span></label>
                    <input class="form-control" type="date" id="log_date" name="log_date" value="<?= $escape($value('log_date', date('Y-m-d'))) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="work_done">Công việc đã thực hiện <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="work_done" name="work_done" rows="5" required><?= $escape($value('work_done')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="difficulties">Khó khăn / vướng mắc</label>
                    <textarea class="form-control" id="difficulties" name="difficulties" rows="3"><?= $escape($value('difficulties')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="plan_next_week">Kế hoạch tuần tiếp theo</label>
                    <textarea class="form-control" id="plan_next_week" name="plan_next_week" rows="3"><?= $escape($value('plan_next_week')) ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/internship-logs">Hủy</a>
            <button class="btn btn-outline-primary" type="submit" name="save_mode" value="draft">Lưu nháp</button>
            <button class="btn btn-primary" type="submit" name="save_mode" value="submit"><i class="ti ti-send me-1"></i>Gửi người hướng dẫn</button>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
