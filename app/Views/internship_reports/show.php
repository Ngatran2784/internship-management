<?php
$pageTitle = 'Chi tiết báo cáo';
$pageHeading = $pageTitle;
$activePage = 'internship-reports';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$report = is_array($report ?? null) ? $report : [];
$status = (string)($report['status'] ?? '');
$submissions = is_array($report['submissions'] ?? null) ? $report['submissions'] : [];
$reviews = is_array($report['reviews'] ?? null) ? $report['reviews'] : [];
?>

<div class="container-xl my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a href="<?= $escape($baseUrl) ?>/internship-reports" class="btn btn-outline-secondary btn-sm mb-3"><i class="ti ti-arrow-left me-1"></i>Danh sách báo cáo</a>
            <h2 class="h2 mb-1"><i class="ti ti-file-report text-primary me-2"></i><?= $escape($report['title'] ?? '') ?></h2>
            <div class="text-secondary"><?= $escape($report['milestone_title'] ?? '') ?> · <?= $escape($report['period_name'] ?? '') ?></div>
        </div>
        <span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?> fs-3"><?= $escape($statusLabels[$status] ?? $status) ?></span>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" role="status"><?= $escape($success) ?></div><?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <section class="card mb-3">
                <div class="card-header"><h3 class="card-title">Thông tin nộp</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php if (!empty($report['student_name'])): ?><dt class="col-sm-3">Sinh viên</dt><dd class="col-sm-9"><?= $escape($report['student_name']) ?> (<?= $escape($report['student_code'] ?? '') ?>)</dd><?php endif; ?>
                        <dt class="col-sm-3">Hạn nộp</dt><dd class="col-sm-9"><?= !empty($report['milestone_due_date']) ? $escape(date('d/m/Y H:i', strtotime($report['milestone_due_date']))) : '—' ?></dd>
                        <dt class="col-sm-3">Phiên bản hiện tại</dt><dd class="col-sm-9">v<?= (int)($report['current_version'] ?? 0) ?></dd>
                        <dt class="col-sm-3">Ngày nộp gần nhất</dt><dd class="col-sm-9"><?= !empty($report['submitted_at']) ? $escape(date('d/m/Y H:i', strtotime($report['submitted_at']))) : '—' ?></dd>
                        <?php if ($report['grade'] !== null): ?><dt class="col-sm-3">Điểm</dt><dd class="col-sm-9"><?= $escape(number_format((float)$report['grade'], 2)) ?>/10</dd><?php endif; ?>
                    </dl>
                </div>
                <?php if (!empty($canResubmit)): ?>
                    <div class="card-footer"><a class="btn btn-primary" href="<?= $escape($baseUrl) ?>/internship-reports/create?milestone_id=<?= (int)$report['milestone_id'] ?>">Nộp phiên bản mới</a></div>
                <?php elseif (!empty($canReview)): ?>
                    <div class="card-footer"><a class="btn btn-primary" href="<?= $escape($baseUrl) ?>/report-reviews/create?report_id=<?= (int)$report['id'] ?>">Nhận xét / duyệt</a></div>
                <?php endif; ?>
            </section>

            <section class="card">
                <div class="card-header"><h3 class="card-title">Lịch sử phiên bản</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Phiên bản</th><th>Tệp</th><th>Nộp lúc</th><th>Ghi chú</th><th>Trạng thái hạn</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td>v<?= (int)$submission['version'] ?><?= (int)$submission['version'] === (int)$report['current_version'] ? ' · hiện tại' : '' ?></td>
                                <td><div class="text-truncate" style="max-width:16rem"><?= $escape($submission['file_name'] ?? '') ?></div><div class="text-secondary small"><?= number_format(((int)($submission['file_size'] ?? 0)) / 1048576, 2) ?> MB</div></td>
                                <td><?= !empty($submission['submitted_at']) ? $escape(date('d/m/Y H:i', strtotime($submission['submitted_at']))) : '—' ?></td>
                                <td><?= $escape($submission['student_note'] ?? '—') ?></td>
                                <td><?= !empty($submission['is_late']) ? '<span class="badge bg-danger-lt text-danger">Nộp trễ</span>' : '<span class="badge bg-success-lt text-success">Đúng hạn</span>' ?></td>
                                <td><a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-reports/download?submission_id=<?= (int)$submission['id'] ?>">Tải tệp</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($submissions === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Chưa có phiên bản tệp.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="card">
                <div class="card-header"><h3 class="card-title">Nhận xét và phản hồi</h3></div>
                <div class="card-body">
                    <?php foreach ($reviews as $review): ?>
                        <article class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <strong><?= $escape($review['reviewer_name'] ?? 'Người hướng dẫn') ?></strong>
                                <span class="badge <?= $escape($decisionBadges[$review['decision'] ?? ''] ?? 'bg-secondary-lt text-secondary') ?>"><?= $escape($decisionLabels[$review['decision'] ?? ''] ?? ($review['decision'] ?? '')) ?></span>
                            </div>
                            <div class="text-body" style="white-space:pre-line"><?= $escape($review['comments'] ?? '') ?></div>
                            <?php if (!empty($review['edit_requirements'])): ?><div class="alert alert-warning mt-2 mb-0"><strong>Cần chỉnh sửa:</strong><div style="white-space:pre-line"><?= $escape($review['edit_requirements']) ?></div></div><?php endif; ?>
                            <?php if ($review['score'] !== null): ?><div class="text-secondary small mt-2">Điểm: <?= $escape(number_format((float)$review['score'], 2)) ?>/10</div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($reviews === []): ?><p class="text-secondary mb-0">Chưa có nhận xét.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
