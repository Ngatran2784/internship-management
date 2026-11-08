<?php
$pageTitle = 'Quản lý đề tài';
$pageHeading = 'Danh sách đề tài thực tập & đồ án';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'topics';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$topics = $topics ?? [];
$periods = $periods ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'pages' => 1, 'total' => count($topics)];
$statusLabels = $statusLabels ?? [];
$typeLabels = $typeLabels ?? [];
$statusBadges = $statusBadges ?? [];
$typeBadges = $typeBadges ?? [];
$currentUserRoles = $currentUserRoles ?? [];
$currentUserId = (int)($currentUserId ?? 0);
$canCreate = (bool)array_intersect(['admin', 'training_dept', 'department_head', 'lecturer', 'enterprise', 'student'], $currentUserRoles);
$canSeeStatusFilter = in_array('admin', $currentUserRoles, true)
    || in_array('training_dept', $currentUserRoles, true)
    || (bool)array_intersect(['department_head', 'lecturer', 'enterprise'], $currentUserRoles);
$queryFilters = array_filter([
    'period_id' => $filters['period_id'] ?? null,
    'status' => $filters['status'] ?? null,
    'type' => $filters['type'] ?? null,
    'keyword' => $filters['keyword'] ?? null,
], static fn($value): bool => $value !== null && $value !== '');
?>
<div class="container-xl">
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-circle-check fs-3"></i><div><?= $escape($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-alert-circle fs-3"></i><div><?= $escape($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <form method="GET" action="<?= $escape($baseUrl) ?>/topics"
                  class="topic-filter-toolbar<?= $canSeeStatusFilter ? '' : ' topic-filter-without-status' ?>">
                <div class="topic-filter-field topic-filter-period">
                    <label class="form-label" for="period-filter">Đợt thực tập / đồ án</label>
                    <select class="form-select" id="period-filter" name="period_id">
                        <option value="">Tất cả đợt</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int)$period['id'] ?>"
                                <?= (string)($filters['period_id'] ?? '') === (string)$period['id'] ? 'selected' : '' ?>>
                                <?= $escape($period['code'] . ' — ' . $period['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($canSeeStatusFilter): ?>
                    <div class="topic-filter-field topic-filter-status">
                        <label class="form-label" for="status-filter">Trạng thái</label>
                        <select class="form-select" id="status-filter" name="status">
                            <option value="">Tất cả</option>
                            <?php foreach ($statusLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="topic-filter-field topic-filter-type">
                    <label class="form-label" for="type-filter">Loại đề tài</label>
                    <select class="form-select" id="type-filter" name="type">
                        <option value="">Tất cả loại</option>
                        <?php foreach ($typeLabels as $key => $label): ?>
                            <option value="<?= $escape($key) ?>" <?= ($filters['type'] ?? '') === $key ? 'selected' : '' ?>>
                                <?= $escape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="topic-filter-field topic-filter-keyword">
                    <label class="form-label" for="keyword-filter">Tìm kiếm</label>
                    <input class="form-control" id="keyword-filter" name="keyword" type="search"
                           value="<?= $escape($filters['keyword'] ?? '') ?>" placeholder="Mã, tên hoặc người đề xuất">
                </div>
                <div class="topic-filter-actions">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i>Lọc</button>
                    <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics">Xóa lọc</a>
                </div>
                <?php if ($canCreate): ?>
                    <a class="btn btn-primary topic-propose-button" href="<?= $escape($baseUrl) ?>/topics/create">
                        <i class="ti ti-plus me-1"></i>Đề xuất đề tài
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Mã đề tài</th><th>Tên đề tài</th><th>Loại</th><th>Đợt</th>
                        <th>Người đề xuất</th><th>Trạng thái</th><th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$topics): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-5">
                            <i class="ti ti-file-search fs-1 d-block mb-2"></i>Không tìm thấy đề tài phù hợp.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($topics as $topic): ?>
                            <?php
                            $isManager = (bool)array_intersect(['admin', 'training_dept'], $currentUserRoles);
                            $canEditTopic = $isManager || (
                                (int)$topic['proposer_id'] === $currentUserId
                                && in_array($topic['status'], ['draft', 'rejected'], true)
                            );
                            ?>
                            <tr>
                                <td class="text-nowrap fw-semibold"><?= $escape($topic['code']) ?></td>
                                <td>
                                    <a class="text-reset fw-semibold" href="<?= $escape($baseUrl) ?>/topics/detail?id=<?= (int)$topic['id'] ?>">
                                        <?= $escape($topic['name']) ?>
                                    </a>
                                    <div class="text-secondary small text-truncate" style="max-width: 24rem">
                                        <?= $escape($topic['description'] ?? '') ?>
                                    </div>
                                </td>
                                <td><span class="badge <?= $escape($typeBadges[$topic['type']] ?? 'bg-secondary-lt') ?>">
                                    <?= $escape($typeLabels[$topic['type']] ?? $topic['type']) ?>
                                </span></td>
                                <td>
                                    <div><?= $escape($topic['period_name']) ?></div>
                                    <div class="text-secondary small"><?= $escape($topic['period_code']) ?></div>
                                </td>
                                <td><?= $escape($topic['proposer_name']) ?></td>
                                <td><span class="badge <?= $escape($statusBadges[$topic['status']] ?? 'bg-secondary-lt') ?>">
                                    <?= $escape($statusLabels[$topic['status']] ?? $topic['status']) ?>
                                </span></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" title="Xem chi tiết"
                                       href="<?= $escape($baseUrl) ?>/topics/detail?id=<?= (int)$topic['id'] ?>">
                                        <i class="ti ti-eye"></i><span class="visually-hidden">Xem</span>
                                    </a>
                                    <?php if ($canEditTopic): ?>
                                        <a class="btn btn-sm btn-outline-secondary" title="Chỉnh sửa"
                                           href="<?= $escape($baseUrl) ?>/topics/edit?id=<?= (int)$topic['id'] ?>">
                                            <i class="ti ti-edit"></i><span class="visually-hidden">Sửa</span>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
            <div class="text-secondary">Tổng <?= (int)$pagination['total'] ?> đề tài</div>
            <?php if ((int)$pagination['pages'] > 1): ?>
                <nav aria-label="Phân trang danh sách đề tài">
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($page = 1; $page <= (int)$pagination['pages']; $page++): ?>
                            <li class="page-item<?= $page === (int)$pagination['page'] ? ' active' : '' ?>">
                                <a class="page-link" href="<?= $escape($baseUrl) ?>/topics?<?= $escape(http_build_query($queryFilters + ['page' => $page])) ?>"
                                   <?= $page === (int)$pagination['page'] ? 'aria-current="page"' : '' ?>><?= $page ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
