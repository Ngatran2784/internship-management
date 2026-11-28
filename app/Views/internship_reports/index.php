<?php
$pageTitle = 'Báo cáo thực tập';
$pageHeading = $pageTitle;
$activePage = 'internship-reports';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$reports = is_array($reports ?? null) ? $reports : [];
$submissionMilestones = is_array($submissionMilestones ?? null) ? $submissionMilestones : [];
?>

<div class="container-xl my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="h2 mb-1"><i class="ti ti-file-report text-primary me-2"></i>Báo cáo thực tập</h2>
            <p class="text-secondary mb-0"><?= $isStudent ? 'Nộp báo cáo theo mốc tiến độ và theo dõi các lần nhận xét.' : 'Báo cáo chỉ hiển thị trong phạm vi sinh viên được phân công.' ?></p>
        </div>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" role="status"><?= $escape($success) ?></div><?php endif; ?>

    <?php if ($isStudent): ?>
        <section class="card mb-4">
            <div class="card-header"><h3 class="card-title">Các mốc nộp báo cáo</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Mốc</th><th>Hạn nộp</th><th>Trạng thái hiện tại</th><th class="w-1">Thao tác</th></tr></thead>
                    <tbody>
                    <?php foreach ($submissionMilestones as $milestone): ?>
                        <?php
                        $reportStatus = (string)($milestone['report_status'] ?? '');
                        $canSubmit = (bool)($milestone['can_submit'] ?? false);
                        ?>
                        <tr>
                            <td><div class="fw-medium"><?= $escape($milestone['title'] ?? '') ?></div><div class="text-secondary small"><?= $escape($milestone['period_name'] ?? '') ?></div></td>
                            <td><?= !empty($milestone['due_date']) ? $escape(date('d/m/Y H:i', strtotime($milestone['due_date']))) : '—' ?></td>
                            <td>
                                <?php if ($reportStatus !== ''): ?>
                                    <span class="badge <?= $escape($statusBadges[$reportStatus] ?? 'bg-secondary-lt text-secondary') ?>"><?= $escape($statusLabels[$reportStatus] ?? $reportStatus) ?></span>
                                <?php else: ?><span class="text-secondary">Chưa nộp</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canSubmit): ?>
                                    <a class="btn btn-sm btn-primary" href="<?= $escape($baseUrl) ?>/internship-reports/create?milestone_id=<?= (int)$milestone['id'] ?>">
                                        <?= $reportStatus === '' ? 'Nộp báo cáo' : 'Nộp lại' ?>
                                    </a>
                                <?php elseif (!empty($milestone['report_id'])): ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-reports/detail?id=<?= (int)$milestone['report_id'] ?>">Chi tiết</a>
                                <?php else: ?><span class="text-secondary">—</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($submissionMilestones === []): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">Chưa có mốc báo cáo trong phân công đã công bố.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <section class="card">
        <div class="card-header"><h3 class="card-title"><?= $isStudent ? 'Báo cáo đã nộp' : 'Báo cáo trong phạm vi hướng dẫn' ?></h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <?php if ($canReview): ?><th>Sinh viên</th><?php endif; ?>
                    <th>Báo cáo / mốc</th><th>Phiên bản</th><th>Hạn nộp</th><th>Trạng thái</th><th>Đã nộp lúc</th><th class="w-1">Thao tác</th>
                </tr></thead>
                <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php $status = (string)($report['status'] ?? ''); ?>
                    <tr>
                        <?php if ($canReview): ?><td><div class="fw-medium"><?= $escape($report['student_name'] ?? '') ?></div><div class="text-secondary small"><?= $escape($report['student_code'] ?? '') ?></div></td><?php endif; ?>
                        <td><div class="fw-medium"><?= $escape($report['title'] ?? '') ?></div><div class="text-secondary small"><?= $escape($report['milestone_title'] ?? '') ?></div></td>
                        <td>v<?= (int)($report['current_version'] ?? 0) ?></td>
                        <td><?= !empty($report['milestone_due_date']) ? $escape(date('d/m/Y H:i', strtotime($report['milestone_due_date']))) : '—' ?></td>
                        <td><span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>"><?= $escape($statusLabels[$status] ?? $status) ?></span></td>
                        <td><?= !empty($report['submitted_at']) ? $escape(date('d/m/Y H:i', strtotime($report['submitted_at']))) : '—' ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-reports/detail?id=<?= (int)$report['id'] ?>">Chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($reports === []): ?>
                    <tr><td colspan="<?= $canReview ? 7 : 6 ?>" class="text-center text-secondary py-5"><?= $isStudent ? 'Bạn chưa nộp báo cáo nào.' : 'Chưa có báo cáo thuộc phạm vi của bạn.' ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
