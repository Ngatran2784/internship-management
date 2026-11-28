<?php
$pageTitle = 'Báo cáo cần nhận xét';
$pageHeading = $pageTitle;
$activePage = 'report-reviews';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$reports = is_array($reports ?? null) ? $reports : [];
?>

<div class="container-xl my-4">
    <div class="mb-4">
        <h2 class="h2 mb-1"><i class="ti ti-message-dots text-primary me-2"></i>Báo cáo cần nhận xét</h2>
        <p class="text-secondary mb-0">Danh sách chỉ gồm báo cáo thuộc sinh viên được phân công cho bạn.</p>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" role="status"><?= $escape($success) ?></div><?php endif; ?>

    <section class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Sinh viên</th><th>Báo cáo / mốc</th><th>Phiên bản</th><th>Hạn nộp</th><th>Trạng thái</th><th>Nộp lúc</th><th class="w-1">Thao tác</th></tr></thead>
                <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php $status = (string)($report['status'] ?? ''); ?>
                    <tr>
                        <td><div class="fw-medium"><?= $escape($report['student_name'] ?? '') ?></div><div class="text-secondary small"><?= $escape($report['student_code'] ?? '') ?></div></td>
                        <td><div class="fw-medium"><?= $escape($report['title'] ?? '') ?></div><div class="text-secondary small"><?= $escape($report['milestone_title'] ?? '') ?></div></td>
                        <td>v<?= (int)($report['current_version'] ?? 0) ?></td>
                        <td><?= !empty($report['milestone_due_date']) ? $escape(date('d/m/Y H:i', strtotime($report['milestone_due_date']))) : '—' ?></td>
                        <td><span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>"><?= $escape($statusLabels[$status] ?? $status) ?></span></td>
                        <td><?= !empty($report['latest_submission_at']) ? $escape(date('d/m/Y H:i', strtotime($report['latest_submission_at']))) : '—' ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-reports/detail?id=<?= (int)$report['id'] ?>">Xem</a>
                            <a class="btn btn-sm btn-primary" href="<?= $escape($baseUrl) ?>/report-reviews/create?report_id=<?= (int)$report['id'] ?>">Nhận xét</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($reports === []): ?><tr><td colspan="7" class="text-center text-secondary py-5">Không có báo cáo đang chờ nhận xét.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
