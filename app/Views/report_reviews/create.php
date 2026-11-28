<?php
$pageTitle = 'Nhận xét báo cáo';
$pageHeading = $pageTitle;
$activePage = 'report-reviews';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$report = is_array($report ?? null) ? $report : [];
$submission = is_array($submission ?? null) ? $submission : [];
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$decisionLabels = is_array($decisionLabels ?? null) ? $decisionLabels : [];
?>

<div class="container-xl my-4">
    <a href="<?= $escape($baseUrl) ?>/report-reviews" class="btn btn-outline-secondary btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Danh sách cần nhận xét</a>
    <h2 class="h2 mb-1"><i class="ti ti-message-dots text-primary me-2"></i>Nhận xét báo cáo</h2>
    <p class="text-secondary mb-4"><?= $escape($report['student_name'] ?? '') ?> · <?= $escape($report['title'] ?? '') ?> · Phiên bản v<?= (int)($submission['version'] ?? 0) ?></p>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>

    <section class="card mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><div class="fw-medium"><?= $escape($submission['file_name'] ?? '') ?></div><div class="text-secondary small"><?= !empty($submission['submitted_at']) ? $escape(date('d/m/Y H:i', strtotime($submission['submitted_at']))) : '' ?></div></div>
            <a class="btn btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-reports/download?submission_id=<?= (int)($submission['id'] ?? 0) ?>">Tải phiên bản để xem</a>
        </div>
    </section>

    <form method="post" action="<?= $escape($baseUrl) ?>/report-reviews/store" class="card">
        <div class="card-body">
            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
            <input type="hidden" name="report_id" value="<?= (int)($report['id'] ?? 0) ?>">
            <input type="hidden" name="submission_id" value="<?= (int)($submission['id'] ?? 0) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="decision">Quyết định <span class="text-danger">*</span></label>
                    <select class="form-select" id="decision" name="decision" required>
                        <?php foreach ($decisionLabels as $key => $label): ?>
                            <option value="<?= $escape($key) ?>" <?= ($oldInput['decision'] ?? 'comment_only') === $key ? 'selected' : '' ?>><?= $escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="score">Điểm (tùy chọn, thang 10)</label>
                    <input class="form-control" type="number" id="score" name="score" min="0" max="10" step="0.01" value="<?= $escape($oldInput['score'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="comments">Nhận xét <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="comments" name="comments" rows="5" required><?= $escape($oldInput['comments'] ?? '') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="edit-requirements">Yêu cầu chỉnh sửa khi nộp lại</label>
                    <textarea class="form-control" id="edit-requirements" name="edit_requirements" rows="4"><?= $escape($oldInput['edit_requirements'] ?? '') ?></textarea>
                    <div class="form-hint">Bắt buộc khi chọn “Yêu cầu nộp lại”.</div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/internship-reports/detail?id=<?= (int)($report['id'] ?? 0) ?>">Hủy</a>
            <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Lưu nhận xét</button>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
