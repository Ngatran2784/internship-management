<?php
$pageTitle = 'Quản lý mốc tiến độ';
$pageHeading = 'Quản lý mốc tiến độ thực tập & đồ án';
$pageDescription = 'DANH SÁCH CÁC MỐC TIẾN ĐỘ VÀ THỜI HẠN NỘP BÁO CÁO';
$activePage = 'milestones';
$hidePageHeader = true;
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);

$milestones = is_array($milestones ?? null) ? $milestones : [];
$allPeriods = is_array($allPeriods ?? null) ? $allPeriods : [];
$selectedPeriodId = filter_var($selectedPeriodId ?? null, FILTER_VALIDATE_INT) ?: null;
$progressStats = is_array($progressStats ?? null) ? $progressStats : [];
$canManage = (bool)($canManage ?? false);
$isStudent = (bool)($isStudent ?? false);
$userRoles = is_array($userRoles ?? null) ? $userRoles : [];
$now = time();
$upcomingMilestones = [];
$overdueMilestones = [];
foreach ($milestones as $milestoneItem) {
    $itemStatus = (string)($milestoneItem['status'] ?? '');
    $dueTimestamp = !empty($milestoneItem['due_date']) ? strtotime((string)$milestoneItem['due_date']) : false;
    if ($dueTimestamp === false || in_array($itemStatus, ['completed', 'cancelled'], true)) {
        continue;
    }
    if ($itemStatus === 'overdue' || $dueTimestamp < $now) {
        $overdueMilestones[] = $milestoneItem;
    } elseif ($dueTimestamp <= strtotime('+7 days', $now)) {
        $upcomingMilestones[] = $milestoneItem;
    }
}

$pageHeadingText = 'Quản lý mốc tiến độ';
$pageSubheadingText = 'Thiết lập các mốc thời gian, yêu cầu nộp báo cáo và theo dõi tiến độ hoàn thành của sinh viên.';
if ($isStudent) {
    $pageHeadingText = 'Tiến độ thực tập của tôi';
    $pageSubheadingText = 'Theo dõi các mốc nộp đề cương, báo cáo tiến độ và hạn chót hoàn thành của bạn.';
} elseif (in_array('enterprise', $userRoles, true)) {
    $pageHeadingText = 'Tiến độ thực tập';
    $pageSubheadingText = 'Theo dõi kế hoạch thời gian, các mốc báo cáo và tiến độ thực tập của sinh viên tại đơn vị.';
} elseif (in_array('lecturer', $userRoles, true) && !$canManage) {
    $pageHeadingText = 'Tiến độ sinh viên';
    $pageSubheadingText = 'Theo dõi các mốc nộp báo cáo và tiến độ thực hiện đề tài của sinh viên theo đợt.';
}
?>

<div class="container-xl my-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $escape($pageHeadingText) ?></li>
        </ol>
    </nav>

    <!-- Header & Action -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h2 text-dark mb-1">
                <i class="ti ti-timeline text-primary me-2"></i><?= $escape($pageHeadingText) ?>
            </h2>
            <p class="text-secondary mb-0">
                <?= $escape($pageSubheadingText) ?>
            </p>
        </div>
        <?php if ($canManage): ?>
            <div>
                <a href="<?= $escape($baseUrl) ?>/milestones/create<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Thêm mốc mới
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Flash alert -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-alert-circle me-2"></i><?= $escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i><?= $escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <!-- Bộ lọc đợt và trạng thái -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="get" action="<?= $escape($baseUrl) ?>/milestones" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="period_select" class="form-label small fw-bold text-secondary mb-1">
                        <i class="ti ti-calendar me-1"></i>Đợt thực tập / đồ án:
                    </label>
                    <select name="period_id" id="period_select" class="form-select fw-medium" onchange="this.form.submit()">
                        <?php if (empty($allPeriods)): ?>
                            <option value="">(Chưa có đợt)</option>
                        <?php else: ?>
                            <?php foreach ($allPeriods as $p): ?>
                                <?php
                                $statusBadge = ($p['status'] ?? '') === 'active' ? ' [Đang diễn ra]' : (($p['status'] ?? '') === 'closed' ? ' [Đã đóng]' : ' [Chưa mở]');
                                $shortName = preg_replace('/^Đợt\s+/u', '', (string)$p['name']);
                                ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$selectedPeriodId) ? 'selected' : '' ?>>
                                    <?= $escape($p['code']) ?> — <?= $escape($shortName) ?><?= $statusBadge ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (!$isStudent): ?>
                    <div class="col-md-3">
                        <label for="status_select" class="form-label small fw-bold text-secondary mb-1">Trạng thái mốc:</label>
                        <select name="status" id="status_select" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Tất cả trạng thái --</option>
                            <?php foreach ($statusLabels as $sVal => $sText): ?>
                                <option value="<?= $escape($sVal) ?>" <?= (($statusFilter ?? '') === $sVal) ? 'selected' : '' ?>>
                                    <?= $escape($sText) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="kw_input" class="form-label small fw-bold text-secondary mb-1">Tìm kiếm mốc:</label>
                        <input type="search" name="keyword" id="kw_input" class="form-control" placeholder="Tên hoặc nội dung mốc..." value="<?= $escape($keywordFilter ?? '') ?>">
                    </div>
                <?php endif; ?>
                <div class="col-auto align-self-end">
                    <button type="submit" class="btn btn-secondary">
                        <i class="ti ti-filter me-1"></i>Lọc
                    </button>
                    <a href="<?= $escape($baseUrl) ?>/milestones<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-outline-secondary">
                        Đặt lại
                    </a>
                </div>
            <?php
            $activePeriodInfo = null;
            foreach ($allPeriods as $p) {
                if ((int)$p['id'] === (int)$selectedPeriodId) {
                    $activePeriodInfo = $p;
                    break;
                }
            }
            ?>
            <?php if ($activePeriodInfo): ?>
                <div class="mt-2 pt-2 border-top d-flex flex-wrap align-items-center gap-2 small text-secondary">
                    <span class="fw-bold text-dark">Đang xem:</span>
                    <span class="badge bg-primary-lt text-primary fw-bold"><?= $escape($activePeriodInfo['code']) ?></span>
                    <span class="text-dark fw-medium"><?= $escape($activePeriodInfo['name']) ?></span>
                    <span class="badge <?= ($activePeriodInfo['status'] ?? '') === 'active' ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary' ?>">
                        <?= ($activePeriodInfo['status'] ?? '') === 'active' ? 'Đang diễn ra' : (($activePeriodInfo['status'] ?? '') === 'closed' ? 'Đã đóng' : 'Chưa mở') ?>
                    </span>
                    <?php if (!empty($activePeriodInfo['start_date']) && !empty($activePeriodInfo['end_date'])): ?>
                        <span><i class="ti ti-calendar me-1"></i><?= date('d/m/Y', strtotime($activePeriodInfo['start_date'])) ?> – <?= date('d/m/Y', strtotime($activePeriodInfo['end_date'])) ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Khối thẻ thống kê tiến độ (Progress KPI) -->
    <?php if (!empty($progressStats)): ?>
        <div class="row row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-primary text-white"><i class="ti ti-list-check fs-2"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-dark fw-bold fs-2"><?= (int)($progressStats['total_milestones'] ?? 0) ?></div>
                                <div class="text-secondary small">Tổng số mốc tiến độ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-success text-white"><i class="ti ti-circle-check fs-2"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-dark fw-bold fs-2"><?= $isStudent ? (int)($progressStats['student_completed'] ?? 0) : (int)($progressStats['completed_milestones'] ?? 0) ?></div>
                                <div class="text-secondary small"><?= $isStudent ? 'Mốc đã được duyệt' : 'Mốc đã hoàn thành' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-danger text-white"><i class="ti ti-clock-exclamation fs-2"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-dark fw-bold fs-2"><?= $isStudent ? (int)($progressStats['student_overdue'] ?? 0) : (int)($progressStats['overdue_milestones'] ?? 0) ?></div>
                                <div class="text-secondary small">Mốc quá hạn<?= $isStudent ? ' chưa nộp' : '' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-azure text-white"><i class="ti ti-chart-pie fs-2"></i></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-dark fw-bold fs-2"><?= $isStudent ? (float)($progressStats['student_rate'] ?? 0) : (float)($progressStats['completion_rate'] ?? 0) ?>%</div>
                                <div class="text-secondary small"><?= $isStudent ? 'Tiến độ hoàn thành của tôi' : 'Tỷ lệ tiến độ chung' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($selectedPeriodId): ?>
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <section class="card h-100 border-0 shadow-sm" aria-labelledby="overdue-milestones-title">
                    <div class="card-header">
                        <h3 id="overdue-milestones-title" class="card-title text-danger mb-0">
                            <i class="ti ti-clock-exclamation me-2"></i>Mốc đã quá hạn
                            <span class="badge bg-danger-lt text-danger ms-1"><?= count($overdueMilestones) ?></span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($overdueMilestones)): ?>
                            <p class="text-secondary mb-0">Không có mốc quá hạn trong danh sách hiện tại.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach (array_slice($overdueMilestones, 0, 4) as $overdueMilestone): ?>
                                    <li class="d-flex flex-wrap justify-content-between gap-2 py-2 border-bottom">
                                        <a class="fw-semibold" href="<?= $escape($baseUrl) ?>/milestones/detail?id=<?= (int)$overdueMilestone['id'] ?>">
                                            <?= $escape($overdueMilestone['title'] ?? '') ?>
                                        </a>
                                        <span class="text-danger small"><?= !empty($overdueMilestone['due_date']) && strtotime((string)$overdueMilestone['due_date']) !== false ? date('d/m/Y H:i', strtotime((string)$overdueMilestone['due_date'])) : 'Chưa có hạn' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (count($overdueMilestones) > 4): ?>
                                <p class="text-secondary small mt-2 mb-0">Còn <?= count($overdueMilestones) - 4 ?> mốc quá hạn khác trong danh sách.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="card h-100 border-0 shadow-sm" aria-labelledby="upcoming-milestones-title">
                    <div class="card-header">
                        <h3 id="upcoming-milestones-title" class="card-title text-primary mb-0">
                            <i class="ti ti-calendar-time me-2"></i>Sắp đến hạn (7 ngày tới)
                            <span class="badge bg-primary-lt text-primary ms-1"><?= count($upcomingMilestones) ?></span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingMilestones)): ?>
                            <p class="text-secondary mb-0">Không có mốc sắp đến hạn trong danh sách hiện tại.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach (array_slice($upcomingMilestones, 0, 4) as $upcomingMilestone): ?>
                                    <li class="d-flex flex-wrap justify-content-between gap-2 py-2 border-bottom">
                                        <a class="fw-semibold" href="<?= $escape($baseUrl) ?>/milestones/detail?id=<?= (int)$upcomingMilestone['id'] ?>">
                                            <?= $escape($upcomingMilestone['title'] ?? '') ?>
                                        </a>
                                        <span class="text-secondary small"><?= date('d/m/Y H:i', strtotime((string)$upcomingMilestone['due_date'])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (count($upcomingMilestones) > 4): ?>
                                <p class="text-secondary small mt-2 mb-0">Còn <?= count($upcomingMilestones) - 4 ?> mốc sắp đến hạn khác trong danh sách.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bảng danh sách mốc tiến độ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
            <h4 class="card-title text-dark m-0">
                <i class="ti ti-list text-primary me-2"></i>Danh sách các mốc tiến độ (<?= count($milestones) ?> mốc)
            </h4>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover milestone-table">
                <thead>
                    <tr>
                        <th class="w-1 text-center">STT</th>
                        <th>Tên mốc &amp; Nội dung yêu cầu</th>
                        <th>Ngày bắt đầu</th>
                        <th>Hạn chót hoàn thành</th>
                        <th>Trạng thái</th>
                        <?php if ($isStudent): ?>
                            <th>Trạng thái bài nộp</th>
                        <?php else: ?>
                            <th>Số báo cáo</th>
                        <?php endif; ?>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($milestones)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <div class="avatar avatar-md bg-secondary-lt text-secondary mb-2"><i class="ti ti-calendar-off fs-2"></i></div>
                                <div>Chưa có mốc tiến độ nào cho đợt được chọn.</div>
                                <?php if ($canManage): ?>
                                    <a href="<?= $escape($baseUrl) ?>/milestones/create<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-primary mt-2">
                                        <i class="ti ti-plus me-1"></i>Thêm mốc đầu tiên
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($milestones as $m): ?>
                            <?php
                            $mStatus = $m['status'] ?? 'pending';
                            $statusBadge = $statusBadges[$mStatus] ?? 'bg-secondary-lt text-secondary';
                            $statusText  = $statusLabels[$mStatus] ?? $mStatus;
                            $isOverdue   = strtotime($m['due_date']) < time() && !in_array($mStatus, ['completed', 'cancelled'], true);
                            ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary">
                                    #<?= (int)($m['order_index'] ?? 1) ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">
                                        <a href="<?= $escape($baseUrl) ?>/milestones/detail?id=<?= (int)$m['id'] ?>" class="text-reset">
                                            <?= $escape($m['title']) ?>
                                        </a>
                                        <?php if (!empty($m['is_mandatory'])): ?>
                                            <span class="badge bg-red-lt text-red extra-small ms-1">Bắt buộc</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($m['description'])): ?>
                                        <div class="text-secondary small text-truncate" style="max-width: 380px;">
                                            <?= $escape($m['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary small">
                                    <i class="ti ti-calendar me-1"></i><?= date('d/m/Y', strtotime($m['start_date'])) ?>
                                </td>
                                <td>
                                    <div class="small fw-medium <?= $isOverdue ? 'text-danger fw-bold' : 'text-dark' ?>">
                                        <i class="ti ti-clock me-1"></i><?= date('d/m/Y H:i', strtotime($m['due_date'])) ?>
                                    </div>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge bg-danger-lt text-danger extra-small">Đã hết hạn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $escape($statusBadge) ?> px-2 py-1">
                                        <?= $escape($statusText) ?>
                                    </span>
                                </td>

                                <?php if ($isStudent): ?>
                                    <td>
                                        <?php if (!empty($m['report_id'])): ?>
                                            <?php if (($m['report_status'] ?? '') === 'approved'): ?>
                                                <span class="badge bg-success-lt text-success">Đã duyệt (<?= (float)$m['report_grade'] ?>đ)</span>
                                            <?php else: ?>
                                                <?php
                                                $reportStatusLabels = [
                                                    'draft' => 'Bản nháp',
                                                    'submitted' => 'Đã nộp',
                                                    'reviewing' => 'Đang chấm',
                                                    'rejected' => 'Không đạt',
                                                    'resubmit_required' => 'Cần nộp lại',
                                                ];
                                                $reportStatus = (string)($m['report_status'] ?? '');
                                                ?>
                                                <span class="badge <?= $reportStatus === 'resubmit_required' ? 'bg-warning-lt text-warning' : ($reportStatus === 'rejected' ? 'bg-danger-lt text-danger' : 'bg-info-lt text-info') ?>">
                                                    <?= $escape($reportStatusLabels[$reportStatus] ?? 'Đã nộp') ?> (v<?= (int)($m['report_version'] ?? 0) ?>)
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-secondary">Chưa nộp</span>
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <span class="badge bg-azure-lt text-azure">
                                            <?= (int)($m['total_reports'] ?? 0) ?> nộp / <?= (int)($m['approved_reports'] ?? 0) ?> duyệt
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <td class="text-end milestone-actions">
                                    <div class="btn-list justify-content-end">
                                        <a href="<?= $escape($baseUrl) ?>/milestones/detail?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <?php if ($canManage): ?>
                                            <a href="<?= $escape($baseUrl) ?>/milestones/edit?id=<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Chỉnh sửa">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <form method="post" action="<?= $escape($baseUrl) ?>/milestones/delete" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mốc tiến độ này không?');">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa mốc">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
