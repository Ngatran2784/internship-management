<?php
$pageTitle = !empty($existingReport) ? 'Nộp lại báo cáo' : 'Nộp báo cáo';
$pageHeading = $pageTitle;
$activePage = 'internship-reports';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$milestone = is_array($milestone ?? null) ? $milestone : [];
$existingReport = is_array($existingReport ?? null) ? $existingReport : null;
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
$value = static fn(string $key, mixed $fallback = ''): mixed => $oldInput[$key] ?? $fallback;
?>

<div class="container-xl my-4">
    <a href="<?= $escape($baseUrl) ?>/internship-reports" class="btn btn-outline-secondary btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Danh sách báo cáo</a>
    <h2 class="h2 mb-1"><i class="ti ti-upload text-primary me-2"></i><?= $escape($pageTitle) ?></h2>
    <p class="text-secondary mb-4"><?= $escape($milestone['title'] ?? '') ?> · Hạn nộp: <?= !empty($milestone['due_date']) ? $escape(date('d/m/Y H:i', strtotime($milestone['due_date']))) : '—' ?></p>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if ($existingReport): ?>
        <div class="alert alert-warning">Đây là lần nộp tiếp theo. Phiên bản hiện tại là v<?= (int)$existingReport['current_version'] ?>; các phiên bản trước được giữ trong lịch sử.</div>
    <?php endif; ?>

    <form method="post" action="<?= $escape($baseUrl) ?>/internship-reports/submit" enctype="multipart/form-data" class="card">
        <div class="card-body">
            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
            <input type="hidden" name="milestone_id" value="<?= (int)($milestone['id'] ?? 0) ?>">
            <div class="mb-3">
                <label class="form-label" for="report-title">Tiêu đề báo cáo <span class="text-danger">*</span></label>
                <input class="form-control" type="text" id="report-title" name="title" maxlength="255" value="<?= $escape($value('title')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="report-file">Tệp báo cáo <span class="text-danger">*</span></label>
                <input class="form-control" type="file" id="report-file" name="report_file" accept=".pdf,.doc,.docx,.zip" required>
                <div class="form-hint">Chấp nhận PDF, DOC, DOCX, ZIP; tối đa 20 MB. Khi nộp lại, cần chọn tệp phiên bản mới.</div>
            </div>
            <div>
                <label class="form-label" for="student-note">Ghi chú lần nộp</label>
                <textarea class="form-control" id="student-note" name="student_note" rows="4"><?= $escape($value('student_note')) ?></textarea>
            </div>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
            <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/internship-reports">Hủy</a>
            <button class="btn btn-primary" type="submit"><i class="ti ti-send me-1"></i><?= $existingReport ? 'Nộp lại báo cáo' : 'Nộp báo cáo' ?></button>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
