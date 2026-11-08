<?php
$pageTitle = 'Lịch sử xét duyệt';
$pageHeading = 'Lịch sử xét duyệt đề tài';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'topics';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$approvals = $approvals ?? [];
$decisionLabels = $decisionLabels ?? [];
$statusLabels = $statusLabels ?? [];
$statusBadges = $statusBadges ?? [];
?>
<div class="container-xl">
    <?php if (!empty($success)): ?><div class="alert alert-success" role="alert"><?= $escape($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <div class="mb-3"><a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics/detail?id=<?= (int)$topic['id'] ?>">
        <i class="ti ti-arrow-left me-1"></i>Quay lại đề tài
    </a></div>
    <div class="card mb-3">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between gap-2">
            <div><div class="text-secondary small"><?= $escape($topic['code']) ?></div><h2 class="h3 mb-0"><?= $escape($topic['name']) ?></h2></div>
            <span class="badge align-self-start <?= $escape($statusBadges[$topic['status']] ?? 'bg-secondary-lt') ?>">
                <?= $escape($statusLabels[$topic['status']] ?? $topic['status']) ?>
            </span>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Các lần xét duyệt</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Thời gian</th><th>Quyết định</th><th>Người xử lý</th><th>Nhận xét / lý do</th></tr></thead>
                <tbody>
                    <?php if (!$approvals): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-5">Chưa có lịch sử xét duyệt.</td></tr>
                    <?php else: foreach ($approvals as $approval): ?>
                        <?php $decisionClass = $approval['decision'] === 'approved' ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'; ?>
                        <tr>
                            <td class="text-nowrap"><?= $escape($approval['created_at']) ?></td>
                            <td><span class="badge <?= $escape($decisionClass) ?>"><?= $escape($decisionLabels[$approval['decision']] ?? $approval['decision']) ?></span></td>
                            <td><?= $escape($approval['reviewer_name']) ?><div class="text-secondary small"><?= $escape($approval['reviewer_username']) ?></div></td>
                            <td class="text-break"><?= nl2br($escape($approval['comments'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
