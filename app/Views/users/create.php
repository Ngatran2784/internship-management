<?php
$pageTitle       = 'Tạo tài khoản mới';
$pageHeading     = 'Tạo tài khoản mới';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'users';
require dirname(__DIR__) . '/layouts/header.php';

$escape          = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$errors          = $errors ?? [];
$old             = $old    ?? [];
$roles           = $roles  ?? [];
$selectedRoleIds = is_array($old['role_ids'] ?? null) ? array_map('intval', $old['role_ids']) : [];

$roleBadgeColor = [
    'admin'           => 'bg-red-lt text-red',
    'training_dept'   => 'bg-purple-lt text-purple',
    'department_head' => 'bg-orange-lt text-orange',
    'lecturer'        => 'bg-blue-lt text-blue',
    'student'         => 'bg-green-lt text-green',
    'enterprise'      => 'bg-teal-lt text-teal',
];
?>
<div class="container-xl">

    <!-- Breadcrumb / back -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= $escape($baseUrl) ?>/users"
           class="btn btn-sm btn-outline-secondary">
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
                <span class="avatar avatar-sm bg-primary-lt text-primary">
                    <i class="ti ti-user-plus"></i>
                </span>
                <div>
                    <h3 class="card-title mb-0">Thông tin tài khoản</h3>
                    <div class="text-secondary small">Điền đầy đủ các trường bắt buộc</div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="<?= $escape($baseUrl) ?>/users/create" method="POST" novalidate>
                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="full_name">Họ và tên</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?= $escape($old['full_name'] ?? '') ?>"
                                   placeholder="Nguyễn Văn A" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="email">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-mail"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= $escape($old['email'] ?? '') ?>"
                                   placeholder="example@edu.vn" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="username">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-at"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                   value="<?= $escape($old['username'] ?? '') ?>"
                                   placeholder="username" required>
                        </div>
                        <div class="form-hint">Chỉ chữ thường, số và dấu gạch dưới.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= $escape($old['phone'] ?? '') ?>"
                                   placeholder="0901 234 567">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="password">Mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-lock"></i></span>
                            <input type="password" class="form-control" id="password"
                                   name="password" placeholder="Tối thiểu 6 ký tự" required>
                            <button class="btn btn-outline-secondary toggle-pw" type="button"
                                    data-target="password" aria-label="Hiện/ẩn">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="password_confirm">Xác nhận mật khẩu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-lock"></i></span>
                            <input type="password" class="form-control" id="password_confirm"
                                   name="password_confirm" placeholder="Nhập lại mật khẩu">
                            <button class="btn btn-outline-secondary toggle-pw" type="button"
                                    data-target="password_confirm" aria-label="Hiện/ẩn">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <option value="1" <?= (($old['status'] ?? '1') == '1') ? 'selected' : '' ?>>
                                Hoạt động
                            </option>
                            <option value="0" <?= (($old['status'] ?? '1') == '0') ? 'selected' : '' ?>>
                                Bị khóa
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Roles -->
                <?php if (!empty($roles)): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="form-label">
                            <i class="ti ti-shield me-1 text-primary"></i>
                            <strong>Phân quyền vai trò</strong>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($roles as $role): ?>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-check border rounded p-2 d-flex align-items-center gap-2 m-0 cursor-pointer">
                                        <input class="form-check-input mt-0" type="checkbox"
                                               name="role_ids[]" value="<?= (int)$role['id'] ?>"
                                               <?= in_array((int)$role['id'], $selectedRoleIds, true) ? 'checked' : '' ?>>
                                        <span class="badge <?= $escape($roleBadgeColor[$role['name']] ?? 'bg-secondary-lt text-secondary') ?>">
                                            <?= $escape($role['display_name']) ?>
                                        </span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= $escape($baseUrl) ?>/users" class="btn btn-outline-secondary">
                        <i class="ti ti-x me-1"></i>Hủy
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Lưu tài khoản
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = document.getElementById(btn.dataset.target);
        var icon  = btn.querySelector('i');
        if (!input) return;
        var hide = input.type === 'password';
        input.type = hide ? 'text' : 'password';
        icon.classList.toggle('ti-eye',     !hide);
        icon.classList.toggle('ti-eye-off',  hide);
    });
});
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
