<?php
$pageTitle = 'Kết quả phân công đề tài';
$pageHeading = 'Kết quả phân công đề tài & Giảng viên';
$pageDescription = 'KẾT QUẢ PHÂN CÔNG CHÍNH THỨC CỦA SINH VIÊN';
$activePage = 'assignment-result';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);

$allPeriods = is_array($allPeriods ?? null) ? $allPeriods : [];
$selectedPeriodId = filter_var($selectedPeriodId ?? null, FILTER_VALIDATE_INT) ?: null;
$selectedPeriod = is_array($selectedPeriod ?? null) ? $selectedPeriod : null;
$isPublished = (bool)($isPublished ?? false);
$assignment = is_array($assignment ?? null) ? $assignment : null;
$wishes = is_array($wishes ?? null) ? $wishes : [];
$typeLabels = is_array($typeLabels ?? null) ? $typeLabels : [
    'internship' => 'Thực tập tốt nghiệp',
    'thesis'     => 'Đồ án tốt nghiệp',
];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [
    'draft'     => 'Dự thảo',
    'active'    => 'Đang diễn ra',
    'completed' => 'Đã kết thúc',
    'cancelled' => 'Đã hủy',
];

$topicTypeLabel = match ($assignment['topic_type'] ?? '') {
    'thesis'     => 'Đồ án tốt nghiệp',
    'internship' => 'Thực tập tốt nghiệp',
    default      => 'Đề tài',
};
?>

<div class="container-xl my-4">
    <!-- Breadcrumb & Tiêu đề trang -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
            <li class="breadcrumb-item text-secondary">Phân công đề tài</li>
            <li class="breadcrumb-item active" aria-current="page">Kết quả phân công</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h2 text-dark mb-1">
                <i class="ti ti-award text-primary me-2"></i>Kết quả phân công đề tài &amp; Giảng viên
            </h2>
            <p class="text-secondary mb-0">
                Tra cứu kết quả phân công đề tài và giảng viên hướng dẫn chính thức theo từng đợt thực tập / đồ án.
            </p>
        </div>
        <div class="btn-list">
            <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-outline-primary">
                <i class="ti ti-list-numbers me-1"></i>Nguyện vọng của tôi
            </a>
            <a href="<?= $escape($baseUrl) ?>/topics" class="btn btn-outline-secondary">
                <i class="ti ti-files me-1"></i>Danh sách đề tài
            </a>
        </div>
    </div>

    <!-- Thông báo lỗi / thành công flash -->
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

    <!-- Bộ chọn đợt thực tập / đồ án -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="get" action="<?= $escape($baseUrl) ?>/assignments/my-result" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="period_id_select" class="col-form-label fw-bold text-secondary">
                        <i class="ti ti-calendar-event me-1"></i>Chọn đợt:
                    </label>
                </div>
                <div class="col-md-6 col-lg-5">
                    <select name="period_id" id="period_id_select" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($allPeriods)): ?>
                            <option value="">(Không có đợt thực tập khả dụng)</option>
                        <?php else: ?>
                            <?php foreach ($allPeriods as $p): ?>
                                <?php
                                $pId = (int)($p['id'] ?? 0);
                                $isSel = ($pId === (int)$selectedPeriodId);
                                $pStatus = $p['status'] ?? 'active';
                                $statusText = $statusLabels[$pStatus] ?? $pStatus;
                                ?>
                                <option value="<?= $pId ?>" <?= $isSel ? 'selected' : '' ?>>
                                    [<?= $escape($p['code'] ?? '') ?>] <?= $escape($p['name'] ?? '') ?> (<?= $escape($statusText) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">
                        <i class="ti ti-filter me-1"></i>Xem kết quả
                    </button>
                </div>
                <?php if ($selectedPeriod): ?>
                    <div class="col-12 col-md-auto ms-md-auto">
                        <span class="badge bg-blue-lt text-blue px-2 py-1">
                            <?= $escape($typeLabels[$selectedPeriod['type'] ?? ''] ?? 'Thực tập / Đồ án') ?>
                        </span>
                        <?php if (!empty($selectedPeriod['start_date']) && !empty($selectedPeriod['end_date'])): ?>
                            <span class="text-secondary small ms-2">
                                <i class="ti ti-calendar me-1"></i>
                                <?= date('d/m/Y', strtotime($selectedPeriod['start_date'])) ?> - <?= date('d/m/Y', strtotime($selectedPeriod['end_date'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if (!$selectedPeriodId): ?>
        <!-- Không có đợt nào được chọn -->
        <div class="card card-body text-center py-5 shadow-sm border-0">
            <div class="avatar avatar-xl bg-secondary-lt text-secondary mx-auto mb-3">
                <i class="ti ti-calendar-off fs-1"></i>
            </div>
            <h3 class="card-title text-dark">Chưa có đợt thực tập nào khả dụng</h3>
            <p class="text-secondary mb-0">Hiện tại không có đợt thực tập hoặc đồ án tốt nghiệp nào đang mở cho tài khoản của bạn.</p>
        </div>

    <?php elseif (!$isPublished): ?>
        <!-- TRƯỜNG HỢP 1: ĐỢT CHƯA CÔNG BỐ KẾT QUẢ PHÂN CÔNG -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-status-top bg-warning"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar avatar-lg bg-warning-lt text-warning flex-shrink-0">
                        <i class="ti ti-clock-pause fs-1"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark px-2 py-1">Chờ công bố kết quả</span>
                            <span class="text-secondary small">Đợt: <strong><?= $escape($selectedPeriod['name'] ?? '') ?></strong></span>
                        </div>
                        <h3 class="card-title text-dark mb-2">Đợt chưa công bố kết quả phân công chính thức</h3>
                        <p class="text-secondary mb-3">
                            Hội đồng đối sánh và Ban chủ nhiệm Khoa đang trong quá trình xét duyệt, rà soát đối sánh nguyện vọng và phê duyệt danh sách phân công. Kết quả chính thức sẽ được niêm yết tại đây ngay sau khi Phòng Đào tạo ban hành quyết định công bố.
                        </p>
                        <div class="alert alert-info d-flex align-items-center mb-0" role="status">
                            <i class="ti ti-info-circle fs-2 me-2 flex-shrink-0"></i>
                            <div>
                                Quá trình phân công sẽ tự động đối sánh theo thứ tự ưu tiên của các nguyện vọng (NV1 &gt; NV2 &gt; NV3) kết hợp điểm rèn luyện, điểm tích lũy và chỉ tiêu tiếp nhận của từng đề tài.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bảng nguyện vọng sinh viên đã đăng ký trong đợt này (để sinh viên tiện theo dõi) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                <h4 class="card-title text-dark m-0">
                    <i class="ti ti-list-check text-primary me-2"></i>Nguyện vọng đã gửi trong đợt này
                </h4>
                <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-edit me-1"></i>Xem / Chỉnh sửa nguyện vọng
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($wishes)): ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-hover">
                            <thead>
                                <tr>
                                    <th class="w-1">Ưu tiên</th>
                                    <th>Mã đề tài</th>
                                    <th>Tên đề tài</th>
                                    <th>Người đề xuất / Hướng dẫn</th>
                                    <th>Chỉ tiêu</th>
                                    <th>Trạng thái NV</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wishes as $w): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-azure text-white fw-bold">NV<?= (int)($w['priority'] ?? 1) ?></span>
                                        </td>
                                        <td><code><?= $escape($w['topic_code'] ?? 'N/A') ?></code></td>
                                        <td class="fw-medium text-dark"><?= $escape($w['topic_name'] ?? 'N/A') ?></td>
                                        <td class="text-secondary"><?= $escape($w['proposer_name'] ?? 'N/A') ?></td>
                                        <td><?= (int)($w['topic_max_students'] ?? 1) ?> SV</td>
                                        <td>
                                            <span class="badge bg-secondary-lt text-secondary">
                                                <i class="ti ti-clock me-1"></i>Đang chờ xét duyệt
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="avatar avatar-md bg-secondary-lt text-secondary mb-2">
                            <i class="ti ti-file-alert fs-2"></i>
                        </div>
                        <p class="text-secondary mb-2">Bạn chưa đăng ký nguyện vọng đề tài nào trong đợt này.</p>
                        <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i>Đăng ký nguyện vọng ngay
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($isPublished && empty($assignment)): ?>
        <!-- TRƯỜNG HỢP 2: ĐỢT ĐÃ CÔNG BỐ NHƯNG SINH VIÊN CHƯA ĐƯỢC PHÂN CÔNG -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-status-top bg-danger"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar avatar-lg bg-danger-lt text-danger flex-shrink-0">
                        <i class="ti ti-alert-triangle fs-1"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-danger text-white px-2 py-1">Đã công bố</span>
                            <span class="badge bg-danger-lt text-danger px-2 py-1">Chưa được phân công đề tài</span>
                            <span class="text-secondary small">Đợt: <strong><?= $escape($selectedPeriod['name'] ?? '') ?></strong></span>
                        </div>
                        <h3 class="card-title text-dark mb-2">Bạn chưa có tên trong danh sách phân công chính thức đợt này</h3>
                        <p class="text-secondary mb-3">
                            Đợt thực tập / đồ án đã chính thức công bố kết quả phân công. Tuy nhiên, hồ sơ của bạn chưa được ghép vào đề tài nào trong đợt công bố này (có thể do các đề tài bạn chọn đã đủ chỉ tiêu tiếp nhận, hoặc bạn chưa nộp nguyện vọng hợp lệ trước thời hạn).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hướng dẫn xử lý bổ sung -->
        <div class="row row-cards mb-4">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light py-3">
                        <h4 class="card-title text-dark m-0"><i class="ti ti-help text-primary me-2"></i>Hướng dẫn giải quyết &amp; Các bước tiếp theo</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-0">
                            <li class="list-group-item px-0 pt-0">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge bg-primary text-white rounded-circle p-2 mt-1">1</span>
                                    <div>
                                        <strong>Kiểm tra đề tài còn chỉ tiêu:</strong>
                                        <p class="text-secondary small mb-1">Tra cứu danh sách đề tài của bộ môn để xem các đề tài chưa đạt đủ số lượng sinh viên tiếp nhận.</p>
                                        <a href="<?= $escape($baseUrl) ?>/topics" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-search me-1"></i>Tra cứu đề tài
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge bg-primary text-white rounded-circle p-2 mt-1">2</span>
                                    <div>
                                        <strong>Liên hệ Ban chủ nhiệm khoa / Trưởng bộ môn:</strong>
                                        <p class="text-secondary small mb-0">Liên hệ trực tiếp với Thầy/Cô Trưởng bộ môn phụ trách ngành hoặc Văn phòng Khoa để được đăng ký phân công bổ sung vào đợt vét.</p>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item px-0 pb-0">
                                <div class="d-flex align-items-start gap-2">
                                    <span class="badge bg-primary text-white rounded-circle p-2 mt-1">3</span>
                                    <div>
                                        <strong>Khai báo đơn vị thực tập ngoài:</strong>
                                        <p class="text-secondary small mb-0">Nếu bạn đã tự liên hệ được cơ sở tiếp nhận thực tập (Doanh nghiệp), bạn có thể nộp đơn xác nhận tiếp nhận để được Nhà trường phê duyệt phân công giảng viên phụ trách.</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light py-3">
                        <h4 class="card-title text-dark m-0"><i class="ti ti-list-numbers text-primary me-2"></i>Nguyện vọng đã đăng ký</h4>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($wishes)): ?>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ưu tiên</th>
                                            <th>Đề tài</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wishes as $w): ?>
                                            <tr>
                                                <td><span class="badge bg-azure text-white">NV<?= (int)($w['priority'] ?? 1) ?></span></td>
                                                <td>
                                                    <div class="fw-medium text-dark small text-truncate" style="max-width: 180px;" title="<?= $escape($w['topic_name'] ?? '') ?>">
                                                        <?= $escape($w['topic_name'] ?? '') ?>
                                                    </div>
                                                    <div class="text-secondary extra-small"><code><?= $escape($w['topic_code'] ?? '') ?></code></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-danger-lt text-danger">Chưa đạt</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 px-3">
                                <p class="text-secondary small mb-0">Bạn chưa đăng ký nguyện vọng nào trong đợt này.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- TRƯỜNG HỢP 3: SINH VIÊN ĐÃ ĐƯỢC PHÂN CÔNG CHÍNH THỨC -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-status-top bg-success"></div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-lg bg-success text-white flex-shrink-0">
                        <i class="ti ti-circle-check fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge bg-success text-white px-2 py-1">Đã công bố chính thức</span>
                            <span class="badge bg-primary-lt text-primary px-2 py-1"><?= $escape($topicTypeLabel) ?></span>
                            <?php if (!empty($assignment['matched_priority'])): ?>
                                <span class="badge bg-azure text-white px-2 py-1">Trúng tuyển NV<?= (int)$assignment['matched_priority'] ?></span>
                            <?php else: ?>
                                <span class="badge bg-purple-lt text-purple px-2 py-1">Phân công chỉ định</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="card-title text-success mb-1">Chúc mừng! Bạn đã được phân công đề tài chính thức</h2>
                        <p class="text-secondary mb-0">
                            Kết quả phân công đề tài và giảng viên hướng dẫn đã được Phòng Đào tạo ban hành chính thức cho đợt <strong><?= $escape($selectedPeriod['name'] ?? '') ?></strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards mb-4">
            <!-- Thông tin chi tiết Đề tài & Giảng viên -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                        <h4 class="card-title text-dark m-0"><i class="ti ti-file-text text-primary me-2"></i>Thông tin đề tài được phân công</h4>
                        <span class="badge bg-azure text-white">Mã: <?= $escape($assignment['topic_code'] ?? 'N/A') ?></span>
                    </div>
                    <div class="card-body">
                        <h3 class="text-primary fw-bold mb-3"><?= $escape($assignment['topic_name'] ?? 'Chưa cập nhật tên đề tài') ?></h3>

                        <dl class="row mb-3">
                            <dt class="col-sm-3 text-secondary">Loại đề tài:</dt>
                            <dd class="col-sm-9 fw-medium text-dark"><?= $escape($topicTypeLabel) ?></dd>

                            <dt class="col-sm-3 text-secondary">Đợt thực hiện:</dt>
                            <dd class="col-sm-9 text-dark">
                                [<?= $escape($assignment['period_code'] ?? '') ?>] <?= $escape($assignment['period_name'] ?? '') ?>
                                <?php if (!empty($assignment['period_start_date']) && !empty($assignment['period_end_date'])): ?>
                                    <span class="text-secondary small ms-1">(<?= date('d/m/Y', strtotime($assignment['period_start_date'])) ?> &ndash; <?= date('d/m/Y', strtotime($assignment['period_end_date'])) ?>)</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-3 text-secondary">Phương thức ghép:</dt>
                            <dd class="col-sm-9">
                                <?php if (($assignment['method'] ?? '') === 'auto'): ?>
                                    <span class="badge bg-info-lt text-info">Đối sánh nguyện vọng tự động</span>
                                <?php else: ?>
                                    <span class="badge bg-teal-lt text-teal">Phân công trực tiếp / Thủ công</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-3 text-secondary">Nguyện vọng trúng:</dt>
                            <dd class="col-sm-9">
                                <?php if (!empty($assignment['matched_priority'])): ?>
                                    <span class="badge bg-azure text-white">Nguyện vọng <?= (int)$assignment['matched_priority'] ?> (NV<?= (int)$assignment['matched_priority'] ?>)</span>
                                <?php else: ?>
                                    <span class="text-secondary">Phân công chỉ định bổ sung</span>
                                <?php endif; ?>
                            </dd>

                            <?php if (!empty($assignment['assigned_at'])): ?>
                                <dt class="col-sm-3 text-secondary">Ngày phân công:</dt>
                                <dd class="col-sm-9 text-secondary">
                                    <i class="ti ti-clock me-1"></i><?= date('d/m/Y H:i', strtotime($assignment['assigned_at'])) ?>
                                </dd>
                            <?php endif; ?>
                        </dl>

                        <div class="p-3 bg-light rounded">
                            <h5 class="text-dark fw-bold mb-2"><i class="ti ti-notes me-1"></i>Mô tả &amp; Mục tiêu đề tài:</h5>
                            <div class="text-secondary" style="white-space: pre-line;">
                                <?= !empty($assignment['topic_description']) ? $escape($assignment['topic_description']) : '<em>(Chưa có bản mô tả chi tiết cho đề tài này)</em>' ?>
                            </div>
                        </div>

                        <?php if (!empty($assignment['notes'])): ?>
                            <div class="alert alert-warning mt-3 mb-0" role="note">
                                <strong><i class="ti ti-note me-1"></i>Ghi chú phân công:</strong>
                                <?= $escape($assignment['notes']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Card Thông tin Giảng viên hướng dẫn & Hướng dẫn tiếp theo -->
            <div class="col-lg-4">
                <!-- Thông tin Giảng viên hướng dẫn -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary-lt py-3">
                        <h4 class="card-title text-primary m-0"><i class="ti ti-user-check me-2"></i>Giảng viên hướng dẫn</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar avatar-lg bg-primary text-white rounded-circle">
                                <i class="ti ti-user fs-1"></i>
                            </div>
                            <div>
                                <h3 class="card-title text-dark mb-0"><?= $escape($assignment['lecturer_name'] ?? 'N/A') ?></h3>
                                <span class="badge bg-azure-lt text-azure">Cán bộ hướng dẫn</span>
                            </div>
                        </div>

                        <ul class="list-group list-group-flush mb-0">
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span class="text-secondary"><i class="ti ti-mail me-1"></i>Email:</span>
                                <?php if (!empty($assignment['lecturer_email'])): ?>
                                    <a href="mailto:<?= $escape($assignment['lecturer_email']) ?>" class="fw-medium text-primary text-break">
                                        <?= $escape($assignment['lecturer_email']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Chưa cập nhật</span>
                                <?php endif; ?>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span class="text-secondary"><i class="ti ti-phone me-1"></i>Điện thoại:</span>
                                <?php if (!empty($assignment['lecturer_phone'])): ?>
                                    <a href="tel:<?= $escape($assignment['lecturer_phone']) ?>" class="fw-medium text-dark">
                                        <?= $escape($assignment['lecturer_phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Chưa cập nhật</span>
                                <?php endif; ?>
                            </li>
                        </ul>

                        <?php if (!empty($assignment['lecturer_email'])): ?>
                            <div class="mt-3">
                                <a href="mailto:<?= $escape($assignment['lecturer_email']) ?>?subject=<?= rawurlencode('[Thực tập/Đồ án] Liên hệ GVHD - ' . ($assignment['topic_name'] ?? '')) ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="ti ti-send me-1"></i>Gửi email cho Giảng viên
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hướng dẫn tiếp theo -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-3">
                        <h4 class="card-title text-dark m-0"><i class="ti ti-checklist text-primary me-2"></i>Các bước tiếp theo</h4>
                    </div>
                    <div class="card-body">
                        <ol class="ps-3 mb-0 text-secondary small">
                            <li class="mb-2">
                                <strong class="text-dark">Liên hệ GVHD:</strong> Chủ động gửi email hoặc gọi điện thoại cho Thầy/Cô để báo cáo và nhận lịch trao đổi ban đầu.
                            </li>
                            <li class="mb-2">
                                <strong class="text-dark">Thống nhất kế hoạch:</strong> Xây dựng đề cương chi tiết và kế hoạch thực hiện theo mốc thời gian của đợt.
                            </li>
                            <li class="mb-0">
                                <strong class="text-dark">Cập nhật tiến độ:</strong> Thường xuyên ghi nhận nhật ký công việc và báo cáo định kỳ theo yêu cầu.
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
