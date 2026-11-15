<?php
$pageTitle = 'Sinh viên hướng dẫn';
$pageHeading = 'Sinh viên hướng dẫn';
$pageDescription = 'DANH SÁCH SINH VIÊN ĐƯỢC PHÂN CÔNG HƯỚNG DẪN';
$activePage = 'supervisor-students';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);
$hasStudentData = is_array($students ?? null);
$students = is_array($students ?? null) ? $students : [];
$periods = is_array($periods ?? null) ? $periods : [];
$lecturerId = (int)($_SESSION['user_id'] ?? 0);
$students = array_values(array_filter(
    $students,
    static fn(array $student): bool => $lecturerId > 0
        && (int)($student['lecturer_id'] ?? 0) === $lecturerId
));
$selectedPeriod = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);
$keyword = is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : '';
$periodById = [];
foreach ($periods as $period) {
    $periodById[(int)($period['id'] ?? 0)] = $period;
}
?>
<div class="container-xl assignment-page">
    <div class="mb-4">
        <h2 class="h3 mb-1">Sinh viên hướng dẫn</h2>
        <p class="text-secondary mb-0">Chỉ hiển thị sinh viên được phân công cho tài khoản giảng viên hiện tại.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= $escape($error) ?></div>
    <?php endif; ?>
    <?php if (!$hasStudentData): ?>
        <div class="alert alert-info" role="note">Backend chưa cung cấp dữ liệu sinh viên hướng dẫn cho trang này.</div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="assignment-filter-toolbar">
                <div class="assignment-filter-field assignment-filter-keyword">
                    <label class="form-label" for="supervisor-student-search">Tìm theo mã sinh viên hoặc họ tên</label>
                    <input class="form-control" type="search" name="keyword" id="supervisor-student-search"
                           value="<?= $escape($keyword) ?>" placeholder="Nhập mã sinh viên hoặc họ tên">
                </div>
                <div class="assignment-filter-field assignment-filter-period">
                    <label class="form-label" for="supervisor-student-period">Đợt thực tập / đồ án</label>
                    <select class="form-select" name="period_id" id="supervisor-student-period">
                        <option value="">Tất cả đợt</option>
                        <?php foreach ($periods as $period): ?>
                            <?php $periodId = (int)($period['id'] ?? 0); ?>
                            <option value="<?= $periodId ?>" <?= $selectedPeriod === $periodId ? 'selected' : '' ?>>
                                <?= $escape($period['code'] ?? '') ?> — <?= $escape($period['name'] ?? '') ?>
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
                        <th>Đề tài</th>
                        <th>Đợt</th>
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
                        $period = $periodById[$rowPeriodId] ?? [];
                        $statusLabels = [
                            'draft' => 'Bản dự thảo',
                            'published' => 'Đã công bố',
                        ];
                        $statusBadges = [
                            'draft' => 'bg-warning-lt text-warning',
                            'published' => 'bg-success-lt text-success',
                        ];
                        ?>
                        <tr>
                            <td class="text-nowrap fw-semibold"><?= $escape($student['student_code'] ?? '') ?></td>
                            <td><?= $escape($student['student_name'] ?? '') ?></td>
                            <td>
                                <div class="fw-semibold"><?= $escape($student['topic_name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($student['topic_code'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= $escape($student['period_name'] ?? $period['name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($student['period_code'] ?? $period['code'] ?? '') ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>">
                                    <?= $escape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($visibleCount === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                <?= $students
                                    ? 'Không có sinh viên phù hợp bộ lọc trong danh sách được phân công cho bạn.'
                                    : 'Chưa có dữ liệu sinh viên hướng dẫn hoặc Backend chưa kết nối trang này.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
