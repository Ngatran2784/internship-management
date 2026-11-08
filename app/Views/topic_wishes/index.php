<?php
$pageTitle = 'Nguyện vọng đề tài';
$pageHeading = 'Đăng ký nguyện vọng đề tài';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'wishes';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
$wishes = $wishes ?? [];
$openPeriods = $openPeriods ?? [];
$statusLabels = $statusLabels ?? [];
$statusBadges = $statusBadges ?? [];
?>
<div class="container-xl">
    <!-- Tabs điều hướng Đăng ký / Nguyện vọng của tôi -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link" href="<?= $escape($baseUrl) ?>/wishes/create">
                <i class="ti ti-edit me-1"></i>Đăng ký nguyện vọng
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active fw-bold" href="<?= $escape($baseUrl) ?>/wishes" aria-current="page">
                <i class="ti ti-list-numbers ti-list-ordered me-1"></i>Nguyện vọng của tôi
            </a>
        </li>
    </ul>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2 fs-3 align-middle"></i>
            <?= $escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2 fs-3 align-middle"></i>
            <?= $escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Khối thông tin các đợt đang mở đăng ký -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title text-primary mb-0">
                <i class="ti ti-calendar-event me-2"></i>Các đợt đang mở đăng ký nguyện vọng
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($openPeriods)): ?>
                <div class="text-muted text-center py-3">
                    <i class="ti ti-calendar-off fs-1 d-block mb-2 text-secondary"></i>
                    Hiện tại không có đợt thực tập / đồ án nào đang mở nhận đăng ký nguyện vọng.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($openPeriods as $p): ?>
                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between bg-light">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-blue text-blue-fg"><?= $escape($p['code']) ?></span>
                                        <span class="badge bg-success-lt text-success">Đang mở</span>
                                    </div>
                                    <h4 class="card-title text-dark mb-2"><?= $escape($p['name']) ?></h4>
                                    <div class="text-muted small mb-2">
                                        <i class="ti ti-clock me-1"></i>
                                        Thời hạn: <?= date('d/m/Y', strtotime($p['start_date'])) ?> – <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                                    </div>
                                    <?php if (!empty($p['description'])): ?>
                                        <p class="text-secondary small mb-3"><?= nl2br($escape($p['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <a href="<?= $escape($baseUrl) ?>/wishes/create?period_id=<?= (int)$p['id'] ?>" class="btn btn-primary w-100">
                                        <i class="ti ti-edit me-1"></i>Đăng ký / Chỉnh sửa nguyện vọng
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Danh sách nguyện vọng đã đăng ký của sinh viên -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title text-primary mb-0">
                <i class="ti ti-list-check me-2"></i>Nguyện vọng của tôi
            </h3>
            <?php if (!empty($openPeriods)): ?>
                <a href="<?= $escape($baseUrl) ?>/wishes/create" class="btn btn-sm btn-primary">
                    <i class="ti ti-plus me-1"></i>Đăng ký nguyện vọng mới
                </a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th class="w-1 text-center">Thứ tự</th>
                        <th>Mã đề tài</th>
                        <th>Tên đề tài</th>
                        <th>Đợt thực tập / Đồ án</th>
                        <th>Giảng viên đề xuất</th>
                        <th>Ghi chú sinh viên</th>
                        <th class="text-center">Kết quả xét</th>
                        <th class="w-1 text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($wishes)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="ti ti-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Bạn chưa đăng ký nguyện vọng đề tài nào trong hệ thống.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                            $todayStr = date('Y-m-d');
                            foreach ($wishes as $w): 
                                $isOpen = ($w['period_status'] === 'active' && $todayStr >= $w['period_start_date'] && $todayStr <= $w['period_end_date']);
                        ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-primary-lt fw-bold fs-3">NV<?= (int)$w['priority'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-lt font-monospace"><?= $escape($w['topic_code']) ?></span>
                                </td>
                                <td>
                                    <a href="<?= $escape($baseUrl) ?>/topics/detail?id=<?= (int)$w['topic_id'] ?>" class="text-reset fw-medium text-decoration-none hover-primary">
                                        <?= $escape($w['topic_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="text-body"><?= $escape($w['period_name']) ?></div>
                                    <div class="text-muted small font-monospace"><?= $escape($w['period_code']) ?></div>
                                </td>
                                <td>
                                    <?= $escape($w['proposer_name'] ?? 'Chưa phân công') ?>
                                </td>
                                <td>
                                    <span class="text-muted small"><?= !empty($w['note']) ? $escape($w['note']) : '—' ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadges[$w['status']] ?? 'bg-secondary-lt' ?>">
                                        <?= $escape($statusLabels[$w['status']] ?? $w['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <?php if ($isOpen): ?>
                                        <a href="<?= $escape($baseUrl) ?>/wishes/create?period_id=<?= (int)$w['period_id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Chỉnh sửa nguyện vọng trong đợt này">
                                            <i class="ti ti-edit me-1"></i>Sửa
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt text-secondary" title="Đợt đã đóng hoặc đã hết thời hạn đăng ký">
                                            <i class="ti ti-lock me-1"></i>Khóa
                                        </span>
                                    <?php endif; ?>
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
