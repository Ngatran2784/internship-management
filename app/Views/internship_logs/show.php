<?php
$pageTitle = 'Chi tiết nhật ký tuần';
$pageHeading = $pageTitle;
$activePage = 'internship-logs';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$log = is_array($log ?? null) ? $log : [];
$status = (string)($log['status'] ?? '');
$oldInput = is_array($oldInput ?? null) ? $oldInput : [];
?>

<div class="container-xl my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="<?= $escape($baseUrl) ?>/internship-logs" class="btn btn-outline-secondary btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Danh sách</a>
            <h2 class="h2 mb-1"><i class="ti ti-notebook text-primary me-2"></i>Nhật ký tuần <?= (int)($log['week_number'] ?? 0) ?></h2>
            <div class="text-secondary"><?= $escape($log['student_name'] ?? '') ?> · <?= $escape($log['topic_name'] ?? '') ?></div>
        </div>
        <span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?> fs-3"><?= $escape($statusLabels[$status] ?? $status) ?></span>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" role="status"><?= $escape($success) ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <section class="card">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Ngày ghi</dt><dd class="col-sm-9"><?= !empty($log['log_date']) ? $escape(date('d/m/Y', strtotime($log['log_date']))) : '—' ?></dd>
                        <dt class="col-sm-3">Đợt</dt><dd class="col-sm-9"><?= $escape($log['period_name'] ?? '—') ?></dd>
                        <dt class="col-sm-3">Công việc</dt><dd class="col-sm-9"><div class="text-body" style="white-space:pre-line"><?= $escape($log['work_done'] ?? '') ?></div></dd>
                        <dt class="col-sm-3">Khó khăn</dt><dd class="col-sm-9"><div class="text-body" style="white-space:pre-line"><?= $escape($log['difficulties'] ?? '—') ?></div></dd>
                        <dt class="col-sm-3">Kế hoạch tuần sau</dt><dd class="col-sm-9"><div class="text-body" style="white-space:pre-line"><?= $escape($log['plan_next_week'] ?? '—') ?></div></dd>
                    </dl>
                </div>
                <?php if (!empty($canEdit)): ?>
                    <div class="card-footer d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= $escape($baseUrl) ?>/internship-logs/edit?id=<?= (int)$log['id'] ?>">Cập nhật</a>
                        <?php if ($status === 'draft'): ?>
                            <form method="post" action="<?= $escape($baseUrl) ?>/internship-logs/delete" onsubmit="return confirm('Xóa nhật ký nháp này?')">
                                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
                                <input type="hidden" name="id" value="<?= (int)$log['id'] ?>">
                                <button class="btn btn-outline-danger" type="submit">Xóa bản nháp</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card">
                <div class="card-header"><h3 class="card-title">Phản hồi người hướng dẫn</h3></div>
                <div class="card-body">
                    <?php if (!empty($log['supervisor_comment'])): ?>
                        <div class="text-body" style="white-space:pre-line"><?= $escape($log['supervisor_comment']) ?></div>
                        <?php if (!empty($log['confirmer_name'])): ?><div class="text-secondary small mt-3"><?= $escape($log['confirmer_name']) ?></div><?php endif; ?>
                    <?php else: ?><p class="text-secondary mb-0">Chưa có phản hồi.</p><?php endif; ?>

                    <?php if (!empty($canReview)): ?>
                        <hr>
                        <form method="post" action="<?= $escape($baseUrl) ?>/internship-logs/confirm" class="mb-3">
                            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
                            <input type="hidden" name="id" value="<?= (int)$log['id'] ?>">
                            <label class="form-label" for="confirm-comment">Nhận xét (không bắt buộc)</label>
                            <textarea class="form-control mb-2" name="supervisor_comment" id="confirm-comment" rows="3"><?= $escape($oldInput['supervisor_comment'] ?? '') ?></textarea>
                            <button class="btn btn-success w-100" type="submit">Xác nhận nhật ký</button>
                        </form>
                        <form method="post" action="<?= $escape($baseUrl) ?>/internship-logs/reject">
                            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken ?? '') ?>">
                            <input type="hidden" name="id" value="<?= (int)$log['id'] ?>">
                            <label class="form-label" for="reject-comment">Lý do cần viết lại <span class="text-danger">*</span></label>
                            <textarea class="form-control mb-2" name="supervisor_comment" id="reject-comment" rows="3" required><?= $escape($oldInput['supervisor_comment'] ?? '') ?></textarea>
                            <button class="btn btn-outline-danger w-100" type="submit">Yêu cầu viết lại</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
