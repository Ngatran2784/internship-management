<?php
$pageTitle = 'Sinh viên thực tập tiếp nhận';
$pageHeading = 'Sinh viên thực tập tiếp nhận';
$pageDescription = 'DANH SÁCH SINH VIÊN ĐƯỢC PHÂN CÔNG THỰC TẬP TẠI DOANH NGHIỆP';
$activePage = 'enterprise-students';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);
$students = is_array($students ?? null) ? $students : [];
$periods = is_array($periods ?? null) ? $periods : [];
$selectedPeriod = filter_var($_GET['period_id'] ?? ($selectedPeriod ?? null), FILTER_VALIDATE_INT);
$keyword = is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : '';
$statusLabels = [
    'draft'     => 'Bản dự thảo',
    'published' => 'Đã công bố',
];
$statusBadges = [
    'draft'     => 'bg-warning-lt text-warning',
    'published' => 'bg-success-lt text-success',
];
?>
<div class="container-xl assignment-page">
    <div class="mb-4">
        <h2 class="h3 mb-1">Sinh viên thực tập được giao</h2>
        <p class="text-secondary mb-0">Danh sách sinh viên được phân công vào các đề tài/vị trí do đơn vị tiếp nhận.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <i class="ti ti-check me-1"></i><?= $escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <i class="ti ti-alert-circle me-1"></i><?= $escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="assignment-filter-toolbar">
                <div class="assignment-filter-field assignment-filter-keyword">
                    <label class="form-label" for="ent-student-search">Tìm theo mã sinh viên hoặc họ tên</label>
                    <input class="form-control" type="search" name="keyword" id="ent-student-search"
                           value="<?= $escape($keyword) ?>" placeholder="Nhập mã sinh viên hoặc họ tên">
                </div>
                <div class="assignment-filter-field assignment-filter-period">
                    <label class="form-label" for="ent-student-period">Đợt thực tập</label>
                    <select class="form-select" name="period_id" id="ent-student-period">
                        <option value="">Tất cả đợt</option>
                        <?php foreach ($periods as $period): ?>
                            <?php $periodId = (int)($period['id'] ?? 0); ?>
                            <option value="<?= $periodId ?>" <?= $selectedPeriod === $periodId ? 'selected' : '' ?>>
                                [<?= $escape($period['code'] ?? '') ?>] <?= $escape($period['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="assignment-filter-actions">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-filter me-1"></i>Lọc danh sách
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table assignment-table">
                <thead>
                    <tr>
                        <th>Mã sinh viên</th>
                        <th>Họ và tên</th>
                        <th>Liên hệ</th>
                        <th>Đề tài tiếp nhận</th>
                        <th>Đợt thực tập</th>
                        <th>GV phụ trách</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $visibleCount = 0; ?>
                    <?php foreach ($students as $student): ?>
                        <?php
                        $rowPeriodId = (int)($student['period_id'] ?? 0);
                        $rowSearch = mb_strtolower(
                            (string)($student['student_code'] ?? '') . ' ' . (string)($student['student_name'] ?? ''),
                            'UTF-8'
                        );
                        if (($selectedPeriod && $selectedPeriod !== $rowPeriodId)
                            || ($keyword !== '' && !str_contains($rowSearch, mb_strtolower($keyword, 'UTF-8')))) {
                            continue;
                        }
                        $visibleCount++;
                        $status = (string)($student['status'] ?? '');
                        ?>
                        <tr>
                            <td class="text-nowrap fw-semibold"><?= $escape($student['student_code'] ?? '') ?></td>
                            <td><?= $escape($student['student_name'] ?? '') ?></td>
                            <td>
                                <div><i class="ti ti-mail me-1 text-muted"></i><?= $escape($student['student_email'] ?? 'N/A') ?></div>
                                <?php if (!empty($student['student_phone'])): ?>
                                    <div class="small text-muted"><i class="ti ti-phone me-1"></i><?= $escape($student['student_phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= $escape($student['topic_name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($student['topic_code'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= $escape($student['period_name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($student['period_code'] ?? '') ?></div>
                            </td>
                            <td><?= $escape($student['lecturer_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>">
                                    <?= $escape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($visibleCount === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                <?= $students
                                    ? 'Không có sinh viên phù hợp bộ lọc.'
                                    : 'Hiện chưa có sinh viên nào được phân công vào đề tài thực tập của doanh nghiệp trong đợt này.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
