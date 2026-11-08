<?php
$pageTitle = 'Đề xuất đề tài';
$pageHeading = 'Tạo mới đề tài';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'topics';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES, 'UTF-8');
$old = $old ?? [];
$errors = $errors ?? [];
$periods = $periods ?? [];
$typeLabels = $typeLabels ?? [];
$oldPeriodId = is_scalar($old['period_id'] ?? null) ? (string)$old['period_id'] : '';
?>
<div class="container-xl">
    <div class="mb-3"><a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics">
        <i class="ti ti-arrow-left me-1"></i>Danh sách đề tài
    </a></div>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <strong>Vui lòng kiểm tra lại thông tin.</strong>
            <ul class="mb-0 mt-2"><?php foreach ($errors as $message): ?><li><?= $escape($message) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-header"><h2 class="card-title">Thông tin đề tài</h2></div>
        <div class="card-body">
            <form action="<?= $escape($baseUrl) ?>/topics/create" method="POST">
                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label required" for="code">Mã đề tài</label>
                        <input class="form-control<?= isset($errors['code']) ? ' is-invalid' : '' ?>" id="code" name="code"
                               maxlength="50" required value="<?= $escape($old['code'] ?? '') ?>" autocomplete="off">
                        <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= $escape($errors['code']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="form-label required" for="name">Tên đề tài</label>
                        <input class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" id="name" name="name"
                               maxlength="255" minlength="5" required value="<?= $escape($old['name'] ?? '') ?>">
                        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= $escape($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="type">Loại đề tài</label>
                        <select class="form-select<?= isset($errors['type']) ? ' is-invalid' : '' ?>" id="type" name="type" required>
                            <option value="">Chọn loại đề tài</option>
                            <?php foreach ($typeLabels as $key => $label): ?>
                                <option value="<?= $escape($key) ?>" <?= ($old['type'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['type'])): ?><div class="invalid-feedback"><?= $escape($errors['type']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="period_id">Đợt thực tập / đồ án</label>
                        <select class="form-select<?= isset($errors['period_id']) ? ' is-invalid' : '' ?>" id="period_id" name="period_id" required>
                            <option value="">Chọn đợt</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?= (int)$period['id'] ?>" <?= $oldPeriodId === (string)$period['id'] ? 'selected' : '' ?>>
                                    <?= $escape($period['code'] . ' — ' . $period['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['period_id'])): ?><div class="invalid-feedback"><?= $escape($errors['period_id']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label required" for="max_students">Số sinh viên tối đa</label>
                        <input class="form-control<?= isset($errors['max_students']) ? ' is-invalid' : '' ?>" id="max_students"
                               name="max_students" type="number" min="1" max="10" required
                               value="<?= $escape($old['max_students'] ?? 1) ?>">
                        <?php if (isset($errors['max_students'])): ?><div class="invalid-feedback"><?= $escape($errors['max_students']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">Mô tả</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?= $escape($old['description'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics">Hủy</a>
                    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Lưu bản nháp</button>
                </div>
                <p class="form-hint mt-3 mb-0">Đề tài mới được lưu ở trạng thái Bản nháp. Gửi xét duyệt từ trang chi tiết.</p>
            </form>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
