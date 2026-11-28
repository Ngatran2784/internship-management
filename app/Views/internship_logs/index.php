<?php
$pageTitle = 'Nhật ký thực tập';
$pageHeading = 'Nhật ký thực tập';
$activePage = 'internship-logs';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';
$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$logs = is_array($logs ?? null) ? $logs : [];
$periods = is_array($periods ?? null) ? $periods : [];
?>

<div class="container-xl my-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="h2 mb-1"><i class="ti ti-notebook text-primary me-2"></i>Nhật ký thực tập</h2>
            <p class="text-secondary mb-0"><?= $isStudent ? 'Ghi nhận tiến độ công việc theo từng tuần.' : 'Theo dõi nhật ký của sinh viên trong phạm vi phân công.' ?></p>
        </div>
        <?php if ($isStudent && !empty($assignment)): ?>
            <a class="btn btn-primary" href="<?= $escape($baseUrl) ?>/internship-logs/create">
                <i class="ti ti-plus me-1"></i>Thêm nhật ký tuần
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" role="status"><?= $escape($success) ?></div><?php endif; ?>

    <?php if ($isStudent && empty($assignment)): ?>
        <div class="alert alert-info" role="status">Chưa có phân công đã công bố. Bạn có thể xem nhật ký cũ, nhưng chưa thể tạo nhật ký mới.</div>
    <?php endif; ?>

    <?php if (!$isStudent && count($periods) > 1): ?>
        <form method="get" action="<?= $escape($baseUrl) ?>/internship-logs" class="card card-body mb-3">
            <label class="form-label" for="log-period">Lọc theo đợt</label>
            <div class="input-group">
                <select class="form-select" id="log-period" name="period_id">
                    <option value="">Tất cả đợt được phân công</option>
                    <?php foreach ($periods as $period): ?>
                        <option value="<?= (int)$period['id'] ?>" <?= (int)($periodId ?? 0) === (int)$period['id'] ? 'selected' : '' ?>>
                            <?= $escape($period['code'] ?? '') ?> — <?= $escape($period['name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Lọc</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr>
                    <?php if (!$isStudent): ?><th>Sinh viên</th><?php endif; ?>
                    <th>Tuần</th><th>Ngày</th><th>Đề tài / Đợt</th><th>Trạng thái</th><th class="w-1">Thao tác</th>
                </tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php $status = (string)($log['status'] ?? ''); ?>
                    <tr>
                        <?php if (!$isStudent): ?>
                            <td><div class="fw-medium"><?= $escape($log['student_name'] ?? '') ?></div><div class="text-secondary small"><?= $escape($log['student_code'] ?? '') ?></div></td>
                        <?php endif; ?>
                        <td>Tuần <?= (int)($log['week_number'] ?? 0) ?></td>
                        <td><?= !empty($log['log_date']) ? $escape(date('d/m/Y', strtotime($log['log_date']))) : '—' ?></td>
                        <td>
                            <div><?= $escape($log['topic_name'] ?? 'Đề tài được phân công') ?></div>
                            <?php if (!empty($log['period_name'])): ?><div class="text-secondary small"><?= $escape($log['period_name']) ?></div><?php endif; ?>
                        </td>
                        <td><span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>"><?= $escape($statusLabels[$status] ?? $status) ?></span></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl) ?>/internship-logs/detail?id=<?= (int)$log['id'] ?>">Chi tiết</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($logs === []): ?>
                    <tr><td colspan="<?= $isStudent ? 5 : 6 ?>" class="text-center text-secondary py-5">Chưa có nhật ký phù hợp với phạm vi của bạn.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
