<?php
$pageTitle       = 'Bảng điều khiển tổng quan - Hệ thống quản lý thực tập và đồ án sinh viên';
$pageHeading     = 'Bảng điều khiển tổng quan';
$pageDescription = 'Tổng quan thông tin và tiến độ thực tập - đồ án';
$activePage      = 'dashboard';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$allPeriods          = $allPeriods          ?? [];
$activePeriods       = $activePeriods       ?? [];
$draftPeriods        = $draftPeriods        ?? [];
$closedPeriods       = $closedPeriods       ?? [];
$selectedPeriod      = $selectedPeriod      ?? null;
$selectedPeriodId    = $selectedPeriodId    ?? null;
$typeLabels          = $typeLabels          ?? [];
$statusLabels        = $statusLabels        ?? [];
$primaryRole         = $primaryRole         ?? null;
$userRoles           = $userRoles           ?? [];
$roleData            = $roleData            ?? [];
$approvedTopicCounts = $approvedTopicCounts ?? [];

// Nhãn vai trò đại diện trên banner
$portalLabels = [
    'admin'           => 'Bảng điều khiển Quản trị viên',
    'training_dept'   => 'Cổng thông tin Phòng Đào tạo',
    'department_head' => 'Cổng thông tin Trưởng bộ môn',
    'lecturer'        => 'Cổng thông tin Giảng viên',
    'student'         => 'Cổng thông tin Sinh viên',
    'enterprise'      => 'Cổng thông tin Doanh nghiệp',
];
$portalTitle = $primaryRole !== null ? ($portalLabels[$primaryRole] ?? 'Cổng thông tin người dùng') : 'Tài khoản chưa phân quyền';
?>
<div class="container-xl">
    <!-- 1. BANNER CHÀO MỪNG CÁ NHÂN HÓA THEO VAI TRÒ -->
    <div class="card mb-4 border-0 shadow-sm bg-primary-lt">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="avatar avatar-md bg-primary text-white shadow-sm">
                        <i class="ti <?= $primaryRole === 'admin' ? 'ti-shield-check' : ($primaryRole === 'student' ? 'ti-school' : ($primaryRole === 'enterprise' ? 'ti-building' : 'ti-user-check')) ?> fs-1"></i>
                    </span>
                </div>
                <div class="col">
                    <h2 class="mb-1 fw-bold text-dark">
                        Xin chào, <?= $escape($currentFullName) ?>!
                    </h2>
                    <div class="text-secondary small">
                        <?php if (mb_strtolower(trim((string)$currentFullName)) !== mb_strtolower(trim((string)$displayRole))): ?>
                            Vai trò chính: 
                            <span class="badge bg-primary text-white fw-medium ms-1 me-1">
                                <?= $escape($displayRole) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (count($userRoles) > 1): ?>
                            <span class="text-muted ms-1">(Kiêm nhiệm: <?= $escape(implode(', ', array_map(fn($r) => $roleLabels[$r] ?? $r, array_diff($userRoles, [$primaryRole])))) ?>)</span>
                        <?php endif; ?>
                        &bull; Trạng thái: <span class="badge bg-success-lt text-success fw-medium">Đang hoạt động</span>
                    </div>
                </div>
                <div class="col-12 col-md-auto mt-3 mt-md-0">
                    <span class="badge bg-azure-lt py-2 px-3 fs-6">
                        <i class="ti ti-id me-1"></i><?= $escape($portalTitle) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BỘ CHỌN ĐỢT THỰC TẬP & ĐỒ ÁN (NẾU HỆ THỐNG CÓ ĐỢT) -->
    <?php if (!empty($allPeriods)): ?>
        <div class="card mb-4 shadow-sm border-0 bg-white" id="current-periods">
            <div class="card-body py-3">
                <form method="get" action="<?= $escape($baseUrl) ?>/dashboard" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label for="period_select" class="col-form-label fw-bold text-secondary">
                            <i class="ti ti-calendar-event me-1 text-primary"></i>Chọn đợt thực tập / đồ án:
                        </label>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <select name="period_id" id="period_select" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allPeriods as $p): ?>
                                <?php
                                    $pStatus = $p['status'] ?? 'draft';
                                    $statusBadge = $pStatus === 'active' ? '● Đang mở' : ($pStatus === 'draft' ? '○ Bản nháp' : '■ Đã đóng');
                                ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ((int)$p['id'] === (int)$selectedPeriodId) ? 'selected' : '' ?>>
                                    [<?= $statusBadge ?>] <?= $escape($p['code']) ?> - <?= $escape($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($selectedPeriod): ?>
                        <div class="col-auto ms-md-auto text-secondary small">
                            <span class="badge bg-<?= ($selectedPeriod['status'] ?? '') === 'active' ? 'success' : (($selectedPeriod['status'] ?? '') === 'draft' ? 'warning' : 'secondary') ?>-lt">
                                <?= $escape($statusLabels[$selectedPeriod['status'] ?? ''] ?? $selectedPeriod['status']) ?>
                            </span>
                            &bull; Thời gian: <?= $escape($selectedPeriod['start_date'] ?? 'N/A') ?> &rarr; <?= $escape($selectedPeriod['end_date'] ?? 'N/A') ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. HIỂN THỊ CẢNH BÁO CHO TÀI KHOẢN CHƯA CÓ VAI TRÒ -->
    <?php if ($primaryRole === null): ?>
        <div class="alert alert-warning shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle fs-1 me-3 text-warning"></i>
                <div>
                    <h4 class="alert-title mb-1">Tài khoản chưa được phân quyền sử dụng</h4>
                    <div class="text-secondary">
                        Tài khoản của bạn đã đăng nhập thành công nhưng chưa được gán vai trò hợp lệ trong hệ thống. Vui lòng liên hệ Phòng Đào tạo hoặc Quản trị viên để được cấp quyền truy cập các phân hệ.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 4. KHỐI WIDGET CHO ADMIN (QUẢN TRỊ VIÊN)                           -->
    <!-- ================================================================= -->
    <?php if (in_array('admin', $userRoles, true) && isset($roleData['admin'])): ?>
        <?php $adm = $roleData['admin']; ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-shield-check text-primary me-2"></i>Tổng quan hệ thống Quản trị</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/users/create" class="btn btn-sm btn-primary">
                    <i class="ti ti-user-plus me-1"></i>Thêm người dùng
                </a>
                <a href="<?= $escape($baseUrl) ?>/periods/create" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-calendar-plus me-1"></i>Tạo đợt mới
                </a>
            </div>
        </div>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="bg-primary text-white avatar me-3"><i class="ti ti-users fs-2"></i></span>
                            <div>
                                <div class="text-secondary small">Tổng tài khoản</div>
                                <div class="h2 m-0"><?= number_format($adm['total_users']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="bg-success text-white avatar me-3"><i class="ti ti-school fs-2"></i></span>
                            <div>
                                <div class="text-secondary small">Sinh viên</div>
                                <div class="h2 m-0"><?= number_format($adm['count_students']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="bg-info text-white avatar me-3"><i class="ti ti-user-check fs-2"></i></span>
                            <div>
                                <div class="text-secondary small">Giảng viên</div>
                                <div class="h2 m-0"><?= number_format($adm['count_lecturers']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <span class="bg-orange text-white avatar me-3"><i class="ti ti-building fs-2"></i></span>
                            <div>
                                <div class="text-secondary small">Doanh nghiệp</div>
                                <div class="h2 m-0"><?= number_format($adm['count_enterprises']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 5. KHỐI WIDGET CHO PHÒNG ĐÀO TẠO (TRAINING_DEPT)                   -->
    <!-- ================================================================= -->
    <?php if (in_array('training_dept', $userRoles, true) && isset($roleData['training_dept'])): ?>
        <?php 
            $pdt = $roleData['training_dept']; 
            $pdtStat = $pdt['statistics'] ?? [];
        ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-building-bank text-primary me-2"></i>Nghiệp vụ Phòng Đào tạo</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/topics/pending" class="btn btn-sm btn-outline-warning">
                    <i class="ti ti-checklist me-1"></i>Duyệt đề tài (<?= (int)$pdt['pending_pdt_count'] ?>)
                </a>
                <a href="<?= $escape($baseUrl) ?>/assignments<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-primary">
                    <i class="ti ti-arrows-shuffle me-1"></i>Quản lý phân công
                </a>
            </div>
        </div>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Sinh viên nộp NV</div>
                        <div class="h2 my-1 text-primary"><?= number_format((int)($pdtStat['total_registered'] ?? 0)) ?></div>
                        <div class="text-secondary small">Hồ sơ đã ghi nhận trong đợt</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đã phân công</div>
                        <div class="h2 my-1 text-success"><?= number_format((int)($pdtStat['active_assignments'] ?? 0)) ?></div>
                        <div class="text-secondary small">Đang hiệu lực (Draft + Published)</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đã công bố</div>
                        <div class="h2 my-1 text-azure"><?= number_format((int)($pdtStat['published_count'] ?? 0)) ?></div>
                        <div class="text-secondary small"><?= (int)($pdtStat['draft_count'] ?? 0) ?> bản ghi còn ở trạng thái nháp</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Chưa được phân công</div>
                        <div class="h2 my-1 text-danger"><?= number_format((int)($pdtStat['unassigned_count'] ?? 0)) ?></div>
                        <div class="text-secondary small">SV nộp NV nhưng chưa trúng tuyển</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 6. KHỐI WIDGET CHO TRƯỞNG BỘ MÔN (DEPARTMENT_HEAD)                -->
    <!-- ================================================================= -->
    <?php if (in_array('department_head', $userRoles, true) && isset($roleData['department_head'])): ?>
        <?php $dept = $roleData['department_head']; ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-certificate text-primary me-2"></i>Quản lý Chuyên môn Bộ môn</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/topics/pending" class="btn btn-sm btn-warning">
                    <i class="ti ti-checkup-list me-1"></i>Xét duyệt đề tài (<?= (int)$dept['pending_cbn_count'] ?>)
                </a>
                <a href="<?= $escape($baseUrl) ?>/assignments<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-user-share me-1"></i>Xem kết quả phân công
                </a>
            </div>
        </div>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đề tài chờ Bộ môn duyệt</div>
                        <div class="h2 my-1 text-warning"><?= number_format((int)$dept['pending_cbn_count']) ?></div>
                        <div class="text-secondary small">Cần phê duyệt để PĐT thẩm định</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đề tài đã duyệt trong đợt</div>
                        <div class="h2 my-1 text-success"><?= number_format((int)$dept['total_approved']) ?></div>
                        <div class="text-secondary small">Sẵn sàng tiếp nhận sinh viên</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Tác vụ nhanh</div>
                        <div class="mt-2">
                            <a href="<?= $escape($baseUrl) ?>/topics/create" class="btn btn-sm btn-outline-primary w-100">
                                <i class="ti ti-plus me-1"></i>Đề xuất đề tài của Bộ môn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 7. KHỐI WIDGET CHO GIẢNG VIÊN (LECTURER)                          -->
    <!-- ================================================================= -->
    <?php if (in_array('lecturer', $userRoles, true) && isset($roleData['lecturer'])): ?>
        <?php $lec = $roleData['lecturer']; ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-chalkboard text-primary me-2"></i>Hướng dẫn &amp; Đề tài của tôi</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/topics?proposer=me" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-folder me-1"></i>Đề tài của tôi (<?= (int)$lec['total_topics'] ?>)
                </a>
                <a href="<?= $escape($baseUrl) ?>/topics/create<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus me-1"></i>Đề xuất đề tài mới
                </a>
            </div>
        </div>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đề tài đã đề xuất</div>
                        <div class="h2 my-1 text-primary"><?= number_format((int)$lec['total_topics']) ?></div>
                        <div class="text-secondary small">Trong đợt đang chọn</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đã phê duyệt</div>
                        <div class="h2 my-1 text-success"><?= number_format((int)$lec['approved_count']) ?></div>
                        <div class="text-secondary small">Sinh viên có thể đăng ký</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đang chờ duyệt</div>
                        <div class="h2 my-1 text-warning"><?= number_format((int)$lec['pending_count']) ?></div>
                        <div class="text-secondary small"><?= (int)$lec['draft_count'] ?> bản ghi nháp</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Sinh viên hướng dẫn</div>
                        <div class="h2 my-1 text-azure"><?= number_format((int)$lec['total_students']) ?></div>
                        <div class="text-secondary small">Sinh viên đã phân công cho tôi</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($lec['students'])): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header py-2">
                    <h4 class="card-title m-0"><i class="ti ti-users me-2 text-primary"></i>Danh sách sinh viên phân công hướng dẫn</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead>
                            <tr>
                                <th>Mã SV</th>
                                <th>Họ tên sinh viên</th>
                                <th>Đề tài phân công</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lec['students'] as $st): ?>
                                <tr>
                                    <td class="fw-bold"><?= $escape($st['student_code'] ?? 'N/A') ?></td>
                                    <td><?= $escape($st['student_name'] ?? 'N/A') ?></td>
                                    <td><?= $escape($st['topic_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($st['status'] ?? '') === 'published' ? 'success' : 'warning' ?>-lt">
                                            <?= $escape($st['status'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 8. KHỐI WIDGET CHO SINH VIÊN (STUDENT)                            -->
    <!-- ================================================================= -->
    <?php if (in_array('student', $userRoles, true) && isset($roleData['student'])): ?>
        <?php $stu = $roleData['student']; ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-school text-primary me-2"></i>Tiến độ Đăng ký &amp; Kết quả Phân công</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/topics" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-files me-1"></i>Xem danh sách đề tài
                </a>
                <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-sm btn-primary">
                    <i class="ti ti-list-numbers me-1"></i>Đăng ký nguyện vọng
                </a>
            </div>
        </div>

        <div class="row row-cards mb-4">
            <!-- Card Kết quả phân công tóm tắt -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0" id="official-assignment">
                    <div class="card-header bg-primary-lt py-3 d-flex align-items-center justify-content-between">
                        <h4 class="card-title text-primary m-0"><i class="ti ti-award me-2"></i>Kết quả phân công</h4>
                        <a href="<?= $escape($baseUrl) ?>/assignments/my-result<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-eye me-1"></i>Xem kết quả
                        </a>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <?php if ($stu['has_assignment'] && !empty($stu['assignment'])): ?>
                            <?php $asg = $stu['assignment']; ?>
                            <div>
                                <div class="badge bg-success text-white mb-2 py-1 px-2">
                                    <i class="ti ti-check me-1"></i>Đã phân công chính thức
                                </div>
                                <h3 class="card-title text-dark mb-1"><?= $escape($asg['topic_name'] ?? 'N/A') ?></h3>
                                <p class="text-secondary small mb-3">
                                    Mã đề tài: <code><?= $escape($asg['topic_code'] ?? 'N/A') ?></code> &bull;
                                    GVHD: <strong class="text-dark"><?= $escape($asg['lecturer_name'] ?? 'N/A') ?></strong>
                                    <?php if (!empty($asg['matched_priority'])): ?>
                                        &bull; <span class="badge bg-azure text-white">NV<?= (int)$asg['matched_priority'] ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="pt-2 border-top">
                                <a href="<?= $escape($baseUrl) ?>/assignments/my-result<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="ti ti-arrow-right me-1"></i>Xem kết quả phân công chi tiết
                                </a>
                            </div>
                        <?php elseif (!empty($stu['is_published'])): ?>
                            <div>
                                <div class="badge bg-warning-lt text-warning mb-2 py-1 px-2">
                                    <i class="ti ti-alert-triangle me-1"></i>Đợt đã công bố
                                </div>
                                <h3 class="card-title text-dark mb-1">Chưa được phân công đề tài</h3>
                                <p class="text-secondary small mb-3">
                                    Đợt thực tập/đồ án này đã công bố kết quả nhưng bạn chưa có tên trong danh sách phân công chính thức. Vui lòng xem hướng dẫn xử lý hoặc liên hệ Ban chủ nhiệm khoa.
                                </p>
                            </div>
                            <div class="pt-2 border-top">
                                <a href="<?= $escape($baseUrl) ?>/assignments/my-result<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-outline-warning btn-sm w-100">
                                    <i class="ti ti-arrow-right me-1"></i>Xem thông tin & hướng dẫn
                                </a>
                            </div>
                        <?php else: ?>
                            <div>
                                <div class="badge bg-secondary-lt text-secondary mb-2 py-1 px-2">
                                    <i class="ti ti-clock me-1"></i>Chờ công bố
                                </div>
                                <h3 class="card-title text-dark mb-1">Chưa công bố kết quả phân công</h3>
                                <p class="text-secondary small mb-3">
                                    Hội đồng đang xử lý phân công và đối sánh nguyện vọng cho đợt này. Kết quả sẽ được cập nhật ngay khi công bố chính thức.
                                </p>
                            </div>
                            <div class="pt-2 border-top d-flex gap-2">
                                <a href="<?= $escape($baseUrl) ?>/assignments/my-result<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="ti ti-eye me-1"></i>Xem kết quả
                                </a>
                                <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-outline-secondary btn-sm flex-fill">
                                    <i class="ti ti-list-check me-1"></i>Xem nguyện vọng
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card Trạng thái nguyện vọng -->
            <div class="col-md-6">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header bg-azure-lt py-3">
                        <h4 class="card-title text-azure m-0"><i class="ti ti-list-check me-2"></i>Nguyện vọng đã đăng ký trong đợt</h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($stu['wishes'])): ?>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th class="w-1">Ưu tiên</th>
                                            <th>Mã đề tài</th>
                                            <th>Tên đề tài</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stu['wishes'] as $w): ?>
                                            <tr>
                                                <td><span class="badge bg-primary text-white">NV<?= (int)$w['priority'] ?></span></td>
                                                <td><code><?= $escape($w['topic_code'] ?? 'N/A') ?></code></td>
                                                <td><?= $escape($w['topic_name'] ?? 'N/A') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 px-3">
                                <div class="avatar avatar-lg bg-secondary-lt text-secondary mb-3">
                                    <i class="ti ti-file-plus fs-1"></i>
                                </div>
                                <h4 class="text-dark mb-1">Bạn chưa đăng ký nguyện vọng</h4>
                                <p class="text-secondary small mb-3">
                                    Đợt thực tập này bạn chưa gửi đăng ký nguyện vọng nào.
                                </p>
                                <a href="<?= $escape($baseUrl) ?>/wishes/create" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Đăng ký nguyện vọng ngay
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 9. KHỐI WIDGET CHO DOANH NGHIỆP (ENTERPRISE)                      -->
    <!-- ================================================================= -->
    <?php if (in_array('enterprise', $userRoles, true) && isset($roleData['enterprise'])): ?>
        <?php $ent = $roleData['enterprise']; ?>
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="m-0 text-dark fw-bold"><i class="ti ti-building text-primary me-2"></i>Đề tài Tiếp nhận Thực tập Doanh nghiệp</h3>
            <div class="btn-list">
                <a href="<?= $escape($baseUrl) ?>/topics?proposer=me" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-folder me-1"></i>Đề tài đã đề xuất (<?= (int)$ent['total_topics'] ?>)
                </a>
                <a href="<?= $escape($baseUrl) ?>/topics/create<?= $selectedPeriodId ? '?period_id=' . (int)$selectedPeriodId : '' ?>" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus me-1"></i>Đề xuất đề tài thực tập
                </a>
            </div>
        </div>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đề tài đề xuất</div>
                        <div class="h2 my-1 text-primary"><?= number_format((int)$ent['total_topics']) ?></div>
                        <div class="text-secondary small">Của doanh nghiệp trong đợt</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đã phê duyệt</div>
                        <div class="h2 my-1 text-success"><?= number_format((int)$ent['approved_count']) ?></div>
                        <div class="text-secondary small">Sẵn sàng tiếp nhận sinh viên</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Đang thẩm định</div>
                        <div class="h2 my-1 text-warning"><?= number_format((int)$ent['pending_count']) ?></div>
                        <div class="text-secondary small">Chờ Khoa / PĐT duyệt</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="subheader text-secondary">Chỉ tiêu tiếp nhận</div>
                        <div class="h2 my-1 text-azure"><?= number_format((int)$ent['total_capacity']) ?></div>
                        <div class="text-secondary small">Tổng chỗ sinh viên thực tập</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================================================================= -->
    <!-- 10. TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN (BẢO LƯU CHO MỌI VAI TRÒ VÀ TEST)   -->
    <!-- ================================================================= -->
    <section class="card shadow-sm" aria-labelledby="internship-progress-title">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h3 class="card-title text-dark m-0" id="internship-progress-title">
                <i class="ti ti-timeline me-2 text-primary"></i>Tiến độ thực tập &amp; đồ án sinh viên
            </h3>
            <?php if (!empty($canManagePeriods)): ?>
                <div class="card-actions">
                    <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-calendar-event me-1"></i>Quản lý đợt thực tập &amp; đồ án
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($activePeriods)): ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-hover card-table">
                    <thead>
                        <tr>
                            <th class="w-1 text-secondary">#</th>
                            <th>Tên đợt thực tập / đồ án</th>
                            <th>Loại đợt</th>
                            <th>Thời gian thực hiện</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activePeriods as $p): ?>
                            <tr>
                                <td class="text-secondary"><?= (int)($p['id'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $escape($p['name'] ?? '') ?></div>
                                    <small class="text-secondary font-monospace"><?= $escape($p['code'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-blue-lt">
                                        <?= $escape($typeLabels[$p['type'] ?? ''] ?? ($p['type'] ?? 'Chưa xác định')) ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-secondary d-block">Bắt đầu: <?= $escape($p['start_date'] ?? 'N/A') ?></small>
                                    <small class="text-secondary d-block">Kết thúc: <?= $escape($p['end_date'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-success text-white">
                                        <i class="ti ti-check me-1"></i><?= $escape($statusLabels[$p['status'] ?? ''] ?? 'Đang mở') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= $escape($baseUrl) ?>/dashboard?period_id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-eye me-1"></i>Xem dữ liệu đợt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body text-center py-4 text-secondary">
                <i class="ti ti-calendar-off fs-1 text-muted mb-2 d-block"></i>
                Hiện tại chưa có đợt thực tập hoặc đồ án nào đang ở trạng thái hoạt động.
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
