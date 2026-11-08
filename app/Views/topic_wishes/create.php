<?php
$pageTitle = 'Đăng ký nguyện vọng đề tài';
$pageHeading = 'Đăng ký nguyện vọng đề tài';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'wishes';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
$period = $period ?? [];
$approvedTopics = $approvedTopics ?? [];
$currentWishes = $currentWishes ?? [];
$csrfToken = $csrfToken ?? '';

// Bản đồ nguyện vọng hiện tại theo priority
$wishesByPriority = [];
foreach ($currentWishes as $cw) {
    $wishesByPriority[(int)$cw['priority']] = $cw;
}
?>
<div class="container-xl">
    <!-- Tabs điều hướng Đăng ký / Nguyện vọng của tôi -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active fw-bold" href="<?= $escape($baseUrl) ?>/wishes/create" aria-current="page">
                <i class="ti ti-edit me-1"></i>Đăng ký nguyện vọng
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= $escape($baseUrl) ?>/wishes">
                <i class="ti ti-list-numbers ti-list-ordered me-1"></i>Nguyện vọng của tôi
            </a>
        </li>
    </ul>
    <div class="mb-3">
        <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/wishes">
            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách nguyện vọng
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="ti ti-alert-circle me-2 fs-3 align-middle"></i>
            <?= $escape($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check me-2 fs-3 align-middle"></i>
            <?= $escape($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Thông tin đợt đăng ký -->
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title text-white mb-0">
                <i class="ti ti-calendar me-2"></i>Đợt: <?= $escape($period['name'] ?? '') ?> (<?= $escape($period['code'] ?? '') ?>)
            </h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <span class="text-muted d-block small">Thời hạn tiếp nhận nguyện vọng:</span>
                    <strong class="text-dark">
                        <?= !empty($period['start_date']) ? date('d/m/Y', strtotime($period['start_date'])) : '' ?>
                        đến
                        <?= !empty($period['end_date']) ? date('d/m/Y', strtotime($period['end_date'])) : '' ?>
                    </strong>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="badge bg-success-lt text-success fs-3 px-3 py-2">
                        <i class="ti ti-circle-check me-1"></i>Hệ thống đang mở đăng ký
                    </span>
                </div>
            </div>
            <div class="alert alert-info bg-azure-lt mt-3 mb-0">
                <div class="d-flex">
                    <i class="ti ti-info-circle fs-2 me-2"></i>
                    <div>
                        <strong>Quy định đăng ký nguyện vọng:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Sinh viên được đăng ký tối đa <strong>3 nguyện vọng</strong> (NV1, NV2, NV3) theo thứ tự ưu tiên giảm dần.</li>
                            <li><strong>Nguyện vọng 1</strong> là bắt buộc. Nguyện vọng 2 và 3 là tùy chọn.</li>
                            <li><strong>Không được trùng đề tài</strong> giữa các nguyện vọng trong cùng một đợt.</li>
                            <li>Chỉ những đề tài đã được Hội đồng / Bộ môn phê duyệt (Approved) mới có trong danh mục.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form đăng ký / điều chỉnh nguyện vọng -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title text-primary mb-0">
                <i class="ti ti-pencil me-2"></i>Lựa chọn đề tài cho từng nguyện vọng
            </h3>
        </div>
        <div class="card-body">
            <?php if (empty($approvedTopics)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="ti ti-alert-triangle me-1"></i>
                    Hiện chưa có đề tài nào được duyệt trong đợt này. Vui lòng quay lại sau khi Bộ môn phê duyệt đề tài.
                </div>
            <?php else: ?>
                <form id="wishRegistrationForm" action="<?= $escape($baseUrl) ?>/wishes/save" method="POST">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="period_id" value="<?= (int)($period['id'] ?? 0) ?>">

                    <!-- NV 1 (Bắt buộc) -->
                    <div class="p-3 border rounded mb-3 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary text-white fw-bold me-2 px-2 py-1 fs-3">NGUYỆN VỌNG 1</span>
                            <span class="text-danger fw-semibold small">(*) Bắt buộc</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <label class="form-label required" for="topic_id_1">Chọn đề tài ưu tiên cao nhất</label>
                                <select class="form-select" id="topic_id_1" name="topic_id_1" required>
                                    <option value="">-- Chọn đề tài cho Nguyện vọng 1 --</option>
                                    <?php foreach ($approvedTopics as $t): ?>
                                        <?php 
                                            $selected1 = (isset($wishesByPriority[1]) && (int)$wishesByPriority[1]['topic_id'] === (int)$t['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int)$t['id'] ?>" <?= $selected1 ?>>
                                            [<?= $escape($t['code']) ?>] <?= $escape($t['name']) ?> (GV: <?= $escape($t['proposer_name'] ?? 'N/A') ?> - Tối đa <?= (int)$t['max_students'] ?> SV)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="note_1">Ghi chú cho NV1 (tùy chọn)</label>
                                <input type="text" class="form-control" id="note_1" name="note_1" maxlength="255"
                                       placeholder="Lý do hoặc mong muốn..." 
                                       value="<?= $escape($wishesByPriority[1]['note'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- NV 2 (Tùy chọn) -->
                    <div class="p-3 border rounded mb-3 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-secondary text-white fw-bold me-2 px-2 py-1 fs-3">NGUYỆN VỌNG 2</span>
                            <span class="text-muted small">(Tùy chọn)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <label class="form-label" for="topic_id_2">Chọn đề tài nguyện vọng 2</label>
                                <select class="form-select" id="topic_id_2" name="topic_id_2">
                                    <option value="">-- Không chọn Nguyện vọng 2 --</option>
                                    <?php foreach ($approvedTopics as $t): ?>
                                        <?php 
                                            $selected2 = (isset($wishesByPriority[2]) && (int)$wishesByPriority[2]['topic_id'] === (int)$t['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int)$t['id'] ?>" <?= $selected2 ?>>
                                            [<?= $escape($t['code']) ?>] <?= $escape($t['name']) ?> (GV: <?= $escape($t['proposer_name'] ?? 'N/A') ?> - Tối đa <?= (int)$t['max_students'] ?> SV)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="note_2">Ghi chú cho NV2 (tùy chọn)</label>
                                <input type="text" class="form-control" id="note_2" name="note_2" maxlength="255"
                                       placeholder="Lý do hoặc mong muốn..." 
                                       value="<?= $escape($wishesByPriority[2]['note'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- NV 3 (Tùy chọn) -->
                    <div class="p-3 border rounded mb-4 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-secondary text-white fw-bold me-2 px-2 py-1 fs-3">NGUYỆN VỌNG 3</span>
                            <span class="text-muted small">(Tùy chọn)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-8">
                                <label class="form-label" for="topic_id_3">Chọn đề tài nguyện vọng 3</label>
                                <select class="form-select" id="topic_id_3" name="topic_id_3">
                                    <option value="">-- Không chọn Nguyện vọng 3 --</option>
                                    <?php foreach ($approvedTopics as $t): ?>
                                        <?php 
                                            $selected3 = (isset($wishesByPriority[3]) && (int)$wishesByPriority[3]['topic_id'] === (int)$t['id']) ? 'selected' : '';
                                        ?>
                                        <option value="<?= (int)$t['id'] ?>" <?= $selected3 ?>>
                                            [<?= $escape($t['code']) ?>] <?= $escape($t['name']) ?> (GV: <?= $escape($t['proposer_name'] ?? 'N/A') ?> - Tối đa <?= (int)$t['max_students'] ?> SV)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label" for="note_3">Ghi chú cho NV3 (tùy chọn)</label>
                                <input type="text" class="form-control" id="note_3" name="note_3" maxlength="255"
                                       placeholder="Lý do hoặc mong muốn..." 
                                       value="<?= $escape($wishesByPriority[3]['note'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= $escape($baseUrl) ?>/wishes" class="btn btn-outline-secondary">
                            <i class="ti ti-x me-1"></i>Hủy bỏ
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i>Lưu danh sách nguyện vọng
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nút hủy đăng ký nếu đã có nguyện vọng -->
    <?php if (!empty($currentWishes)): ?>
        <div class="card shadow-sm border-danger">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="text-danger mb-1"><i class="ti ti-trash me-2"></i>Rút / Hủy toàn bộ nguyện vọng</h4>
                    <p class="text-muted small mb-0">Hủy toàn bộ nguyện vọng đã đăng ký trong đợt này. Bạn có thể đăng ký lại nếu đợt vẫn còn hạn.</p>
                </div>
                <form action="<?= $escape($baseUrl) ?>/wishes/delete" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy toàn bộ các nguyện vọng đã đăng ký cho đợt này?');">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="period_id" value="<?= (int)($period['id'] ?? 0) ?>">
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="ti ti-trash me-1"></i>Hủy đăng ký đợt này
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wishRegistrationForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const s1 = document.getElementById('topic_id_1').value;
        const s2 = document.getElementById('topic_id_2').value;
        const s3 = document.getElementById('topic_id_3').value;

        if (!s1) {
            e.preventDefault();
            alert('Vui lòng chọn đề tài cho Nguyện vọng 1.');
            return;
        }

        const selected = [s1];
        if (s2) {
            if (selected.includes(s2)) {
                e.preventDefault();
                alert('Nguyện vọng 2 không được chọn trùng đề tài với Nguyện vọng 1.');
                return;
            }
            selected.push(s2);
        }

        if (s3) {
            if (selected.includes(s3)) {
                e.preventDefault();
                alert('Nguyện vọng 3 không được chọn trùng đề tài với Nguyện vọng 1 hoặc 2.');
                return;
            }
        }
    });
});
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
