<?php
$pageTitle = 'Phân công đề tài';
$pageHeading = 'Phân công đề tài và kết quả đối sánh';
$pageDescription = 'QUẢN LÝ PHÂN CÔNG ĐỀ TÀI THEO ĐỢT';
$activePage = 'assignments';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);
$periods = is_array($periods ?? null) ? $periods : [];
$assignments = is_array($assignments ?? null) ? $assignments : [];
$unassigned = is_array($unassigned ?? null) ? $unassigned : [];
$statistics = is_array($statistics ?? null) ? $statistics : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$statusBadges = is_array($statusBadges ?? null) ? $statusBadges : [];
$methodLabels = is_array($methodLabels ?? null) ? $methodLabels : [];
$methodBadges = is_array($methodBadges ?? null) ? $methodBadges : [];
$filters = is_array($filters ?? null) ? $filters : [];
$selectedStatus = is_string($_GET['status'] ?? null)
    ? trim($_GET['status'])
    : (string)($filters['status'] ?? '');
if ($selectedStatus !== 'unassigned' && !array_key_exists($selectedStatus, $statusLabels)) {
    $selectedStatus = '';
}
$isManager = !empty($isManager);
$currentPeriod = (int)($currentPeriod ?? 0);
$keyword = is_string($filters['keyword'] ?? null) ? trim($filters['keyword']) : '';
$searchKeyword = mb_strtolower($keyword, 'UTF-8');
$assignments = array_values(array_filter(
    $assignments,
    static function (array $assignment) use ($selectedStatus, $statusLabels, $searchKeyword): bool {
        $status = (string)($assignment['status'] ?? '');
        if ($selectedStatus === 'unassigned'
            || ($selectedStatus !== '' && isset($statusLabels[$selectedStatus]) && $status !== $selectedStatus)) {
            return false;
        }

        if ($searchKeyword === '') {
            return true;
        }

        $studentText = mb_strtolower(
            (string)($assignment['student_code'] ?? '') . ' ' . (string)($assignment['student_name'] ?? ''),
            'UTF-8'
        );
        return str_contains($studentText, $searchKeyword);
    }
));
$unassigned = array_values(array_filter(
    $unassigned,
    static function (array $student) use ($selectedStatus, $searchKeyword): bool {
        if ($selectedStatus !== '' && $selectedStatus !== 'unassigned') {
            return false;
        }

        if ($searchKeyword === '') {
            return true;
        }

        $studentText = mb_strtolower(
            (string)($student['student_code'] ?? '') . ' ' . (string)($student['student_name'] ?? ''),
            'UTF-8'
        );
        return str_contains($studentText, $searchKeyword);
    }
));
$selectedPeriod = null;
foreach ($periods as $period) {
    if ((int)($period['id'] ?? 0) === $currentPeriod) {
        $selectedPeriod = $period;
        break;
    }
}
?>
<div class="container-xl assignment-page">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-circle-check fs-3"></i>
            <div><?= $escape($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-alert-circle fs-3"></i>
            <div><?= $escape($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <?php if (!$isManager): ?>
        <div class="alert alert-warning" role="alert">
            Bạn không có quyền xem danh sách phân công. Chức năng này chỉ dành cho cán bộ có quyền quản lý.
        </div>
    <?php else: ?>
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <h2 class="h3 mb-1">Phân công đề tài</h2>
                <p class="text-secondary mb-0">Tra cứu kết quả phân công và đối sánh nguyện vọng theo đợt.</p>
            </div>
            <?php if ($currentPeriod > 0): ?>
                <div class="assignment-action-form d-flex flex-column flex-sm-row gap-2">
                    <a class="btn btn-outline-primary" href="#assignment-results">
                        <i class="ti ti-list-check me-1"></i>Xem kết quả
                    </a>
                    <button type="button" class="btn btn-outline-secondary" disabled
                            title="Backend chưa có route phân công thủ công">
                        <i class="ti ti-user-plus me-1"></i>Phân công thủ công
                    </button>
                    <form method="post" action="<?= $escape($baseUrl) ?>/assignments/auto-match"
                          class="assignment-action-form"
                          onsubmit="return confirm('Chạy đối sánh cho đợt này? Kết quả được lưu ở trạng thái dự thảo.');">
                        <input type="hidden" name="_csrf" value="<?= $escape($csrf_token ?? '') ?>">
                        <input type="hidden" name="period_id" value="<?= $currentPeriod ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-arrows-shuffle me-1"></i>Chạy đối sánh tự động
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="get" action="<?= $escape($baseUrl) ?>/assignments"
                      class="assignment-filter-toolbar">
                    <div class="assignment-filter-field assignment-filter-period">
                        <label class="form-label" for="assignment-period">Đợt thực tập / đồ án</label>
                        <select class="form-select" id="assignment-period" name="period_id" required>
                            <option value="">Chọn đợt</option>
                            <?php foreach ($periods as $period): ?>
                                <?php $periodId = (int)($period['id'] ?? 0); ?>
                                <option value="<?= $periodId ?>" <?= $periodId === $currentPeriod ? 'selected' : '' ?>>
                                    <?= $escape($period['code'] ?? '') ?> — <?= $escape($period['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="assignment-filter-field assignment-filter-keyword">
                        <label class="form-label" for="assignment-search">Tìm sinh viên</label>
                        <input type="search" class="form-control" id="assignment-search" name="keyword"
                               value="<?= $escape($keyword) ?>"
                               placeholder="Mã sinh viên hoặc họ tên">
                    </div>
                    <div class="assignment-filter-field assignment-filter-status">
                        <label class="form-label" for="assignment-status">Trạng thái</label>
                        <select class="form-select" id="assignment-status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="unassigned" <?= $selectedStatus === 'unassigned' ? 'selected' : '' ?>>
                                Chưa phân công
                            </option>
                            <?php foreach ($statusLabels as $status => $label): ?>
                                <option value="<?= $escape($status) ?>"
                                    <?= $selectedStatus === $status ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="assignment-filter-actions">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="ti ti-filter me-1"></i>Lọc kết quả
                        </button>
                        <a class="btn btn-outline-secondary"
                           href="<?= $escape($baseUrl) ?>/assignments?period_id=<?= $currentPeriod ?>">
                            Xóa lọc
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!$periods): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <span class="empty-state-icon mb-3"><i class="ti ti-calendar-off fs-2"></i></span>
                    <h3 class="h4">Chưa có đợt thực tập hoặc đồ án</h3>
                    <p class="text-secondary mb-0">Tạo đợt trước khi xem hoặc thực hiện đối sánh phân công.</p>
                </div>
            </div>
        <?php elseif (!$selectedPeriod): ?>
            <div class="alert alert-info" role="status">Vui lòng chọn một đợt hợp lệ để xem kết quả.</div>
        <?php else: ?>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="badge bg-blue-lt text-blue"><?= $escape($selectedPeriod['code'] ?? '') ?></span>
                <h3 class="h4 mb-0"><?= $escape($selectedPeriod['name'] ?? '') ?></h3>
                <span class="badge bg-secondary-lt text-secondary">
                    <?= $escape(\App\Models\InternshipPeriod::STATUS_LABELS[$selectedPeriod['status'] ?? ''] ?? ($selectedPeriod['status'] ?? '')) ?>
                </span>
            </div>

            <div class="row row-deck row-cards mb-4">
                <?php
                $statCards = [
                    ['label' => 'Sinh viên đăng ký', 'value' => $statistics['total_registered'] ?? 0, 'icon' => 'ti-users', 'color' => 'blue'],
                    ['label' => 'Đã phân công', 'value' => $statistics['active_assignments'] ?? 0, 'icon' => 'ti-user-check', 'color' => 'green'],
                    ['label' => 'Chưa phân công', 'value' => $statistics['unassigned_count'] ?? 0, 'icon' => 'ti-user-question', 'color' => 'yellow'],
                    ['label' => 'Đã công bố', 'value' => $statistics['published_count'] ?? 0, 'icon' => 'ti-speakerphone', 'color' => 'purple'],
                ];
                ?>
                <?php foreach ($statCards as $stat): ?>
                    <div class="col-6 col-xl-3">
                        <div class="card card-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar bg-<?= $escape($stat['color']) ?>-lt text-<?= $escape($stat['color']) ?>">
                                        <i class="ti <?= $escape($stat['icon']) ?> fs-2"></i>
                                    </span>
                                    <div>
                                        <div class="subheader"><?= $escape($stat['label']) ?></div>
                                        <div class="h2 mb-0"><?= (int)$stat['value'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <span class="text-secondary small" id="assignment-result-count" aria-live="polite">
                    <?= count($assignments) + count($unassigned) ?> sinh viên
                </span>
            </div>

            <?php require __DIR__ . '/results.php'; ?>

            <div class="alert alert-info mt-4 mb-0" role="note">
                Backend hiện chỉ hỗ trợ đối sánh tự động và lưu bản dự thảo. Chưa có route phân công thủ công,
                xác nhận/công bố hoặc dữ liệu từng nguyện vọng NV1–NV3; các thao tác tương ứng chưa được bật.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
(() => {
    const searchInput = document.getElementById('assignment-search');
    const statusSelect = document.getElementById('assignment-status');
    if (!searchInput || !statusSelect) return;

    const count = document.getElementById('assignment-result-count');
    const pageSize = 10;
    const sections = [...document.querySelectorAll('[data-assignment-table]')].map((table) => {
        const type = table.dataset.assignmentTable;
        const pagination = document.querySelector(`[data-assignment-pagination="${type}"]`);
        const section = {
            type,
            rows: [...table.querySelectorAll('.assignment-filter-row')],
            empty: table.querySelector(type === 'assigned'
                ? '#assignment-empty-filter'
                : '#unassigned-empty-filter'),
            pagination,
            previous: pagination?.querySelector('[data-page-previous]'),
            next: pagination?.querySelector('[data-page-next]'),
            summary: pagination?.querySelector('[data-page-summary]'),
            page: 1,
            filteredRows: [],
        };
        section.filteredRows = section.rows;
        return section;
    });

    const renderSection = (section) => {
        const pageCount = Math.max(1, Math.ceil(section.filteredRows.length / pageSize));
        section.page = Math.min(section.page, pageCount);
        const start = (section.page - 1) * pageSize;
        const visibleRows = new Set(section.filteredRows.slice(start, start + pageSize));

        section.rows.forEach((row) => {
            row.hidden = !visibleRows.has(row);
        });
        if (section.empty) section.empty.hidden = section.filteredRows.length > 0;
        if (!section.pagination) return;

        section.pagination.hidden = section.filteredRows.length <= pageSize;
        if (section.summary) {
            const first = section.filteredRows.length ? start + 1 : 0;
            const last = Math.min(start + pageSize, section.filteredRows.length);
            section.summary.textContent = `${first}–${last} / ${section.filteredRows.length} kết quả`;
        }
        if (section.previous) section.previous.disabled = section.page <= 1;
        if (section.next) section.next.disabled = section.page >= pageCount;
    };

    const filterRows = () => {
        const keyword = searchInput.value.trim().toLocaleLowerCase();
        const status = statusSelect.value;
        let visible = 0;

        sections.forEach((section) => {
            section.filteredRows = section.rows.filter((row) => {
                const matchesSearch = (row.dataset.search || '').includes(keyword);
                const matchesStatus = !status || (row.dataset.status || '') === status;
                return matchesSearch && matchesStatus;
            });
            section.page = 1;
            visible += section.filteredRows.length;
            renderSection(section);
        });

        if (count) count.textContent = `${visible} sinh viên`;
    };

    sections.forEach((section) => {
        section.previous?.addEventListener('click', () => {
            section.page--;
            renderSection(section);
        });
        section.next?.addEventListener('click', () => {
            section.page++;
            renderSection(section);
        });
    });

    searchInput.addEventListener('input', filterRows);
    statusSelect.addEventListener('change', filterRows);
    filterRows();
})();
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
