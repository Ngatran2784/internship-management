<?php
$pageTitle       = 'Tạo đợt thực tập mới';
$pageHeading     = 'Tạo đợt thực tập mới';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'periods';
require dirname(__DIR__) . '/layouts/header.php';

$escape       = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$errors       = $errors       ?? [];
$old          = $old          ?? [];
$typeLabels   = $typeLabels   ?? [];
$statusLabels = $statusLabels ?? [];
?>
<div class="container-xl">

    <!-- Back -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger d-flex gap-2 mb-3" role="alert">
            <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
            <div>
                <div class="fw-semibold mb-1">Vui lòng kiểm tra lại:</div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= $escape($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm bg-teal-lt text-teal">
                    <i class="ti ti-calendar-plus"></i>
                </span>
                <div>
                    <h3 class="card-title mb-0">Thông tin đợt mới</h3>
                    <div class="text-secondary small">Mã đợt phải là duy nhất trong hệ thống</div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="<?= $escape($baseUrl) ?>/periods/create" method="POST" novalidate>
                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required" for="code">Mã đợt</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-hash"></i></span>
                            <input type="text" class="form-control" id="code" name="code"
                                   value="<?= $escape($old['code'] ?? '') ?>"
                                   placeholder="VD: TT2024-01" required>
                        </div>
                        <div class="form-hint">Khuyến nghị: TT/DA + năm + số thứ tự</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required" for="name">Tên đợt</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= $escape($old['name'] ?? '') ?>"
                               placeholder="VD: Đợt thực tập kỳ I năm học 2024-2025" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required" for="type">Loại đợt</label>
                        <select class="form-select" id="type" name="type">
                            <?php foreach ($typeLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>"
                                        <?= (($old['type'] ?? '') === $key) ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statusLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>"
                                        <?= (($old['status'] ?? 'draft') === $key) ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-1"></div>

                    <div class="col-md-4">
                        <label class="form-label required" for="start_date">Ngày bắt đầu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?= $escape($old['start_date'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required" for="end_date">Ngày kết thúc</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-calendar-off"></i></span>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                   value="<?= $escape($old['end_date'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Mô tả</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="4"
                                  placeholder="Mô tả chi tiết về mục tiêu, yêu cầu và lịch trình..."><?= $escape($old['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= $escape($baseUrl) ?>/periods" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Lưu đợt
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
(function () {
    var s = document.getElementById('start_date');
    var e = document.getElementById('end_date');
    if (!s || !e) return;
    function validate() {
        if (s.value && e.value && e.value < s.value) {
            e.setCustomValidity('Ngày kết thúc phải sau ngày bắt đầu.');
        } else {
            e.setCustomValidity('');
        }
    }
    s.addEventListener('change', validate);
    e.addEventListener('change', validate);
})();
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
