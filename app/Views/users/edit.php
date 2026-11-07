<?php
$pageTitle       = 'Chỉnh sửa tài khoản';
$pageHeading     = 'Chỉnh sửa tài khoản';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'users';
require dirname(__DIR__) . '/layouts/header.php';

$escape    = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$errors    = $errors    ?? [];
$old       = $old       ?? [];
$roles     = $roles     ?? [];
$user      = $user      ?? [];
$userRoles = $userRoles ?? [];

$selectedRoleIds = [];
if (!empty($old['role_ids']) && is_array($old['role_ids'])) {
    $selectedRoleIds = array_map('intval', $old['role_ids']);
} else {
    foreach ($userRoles as $roleName) {
        foreach ($roles as $role) {
            if (($role['name'] ?? '') === $roleName) {
                $selectedRoleIds[] = (int)$role['id'];
            }
        }
    }
}

$roleBadgeColor = [
    'admin'           => 'bg-red-lt text-red',
    'training_dept'   => 'bg-purple-lt text-purple',
    'department_head' => 'bg-orange-lt text-orange',
    'lecturer'        => 'bg-blue-lt text-blue',
    'student'         => 'bg-green-lt text-green',
    'enterprise'      => 'bg-teal-lt text-teal',
];

// Avatar
$avatarBgs  = ['bg-blue','bg-azure','bg-indigo','bg-purple','bg-pink','bg-red','bg-orange','bg-yellow','bg-lime','bg-green','bg-teal','bg-cyan'];
$avatarBg   = $avatarBgs[(int)($user['id'] ?? 0) % count($avatarBgs)];
$nameParts  = array_values(array_filter(explode(' ', $user['full_name'] ?? '')));
$initials   = '';
if (count($nameParts) >= 2) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1));
} elseif (count($nameParts) === 1) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 2));
} else {
    $initials = '??';
}
?>
<div class="container-xl">

    <!-- Back -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= $escape($baseUrl) ?>/users" class="btn btn-sm btn-outline-secondary">
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
            <div class="d-flex align-items-center gap-3">
                <span class="avatar avatar-md <?= $escape($avatarBg) ?> text-white fs-4 fw-bold">
                    <?= $escape($initials) ?>
                </span>
                <div>
                    <h3 class="card-title mb-0">
                        <?= $escape($user['full_name'] ?? 'Tài khoản') ?>
                    </h3>
                    <div class="text-secondary small">
                        @<?= $escape($user['username'] ?? '') ?>
                        &bull; ID #<?= (int)($user['id'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="<?= $escape($baseUrl) ?>/users/update" method="POST" novalidate>
                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="id"   value="<?= (int)($user['id'] ?? 0) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username_ro">Tên đăng nhập</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-at"></i></span>
                            <input type="text" class="form-control" id="username_ro"
                                   value="<?= $escape($user['username'] ?? '') ?>"
                                   readonly tabindex="-1">
                        </div>
                        <div class="form-hint">Không thể thay đổi.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="email">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-mail"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= $escape($old['email'] ?? $user['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="full_name">Họ và tên</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?= $escape($old['full_name'] ?? $user['full_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   value="<?= $escape($old['phone'] ?? $user['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Mật khẩu mới</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-lock"></i></span>
                            <input type="password" class="form-control" id="password"
                                   name="password" placeholder="Để trống nếu giữ nguyên">
                            <button class="btn btn-outline-secondary toggle-pw" type="button"
                                    data-target="password" aria-label="Hiện/ẩn">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Xác nhận mật khẩu mới</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-lock"></i></span>
                            <input type="password" class="form-control" id="password_confirm"
                                   name="password_confirm" placeholder="Nhập lại mật khẩu mới">
                            <button class="btn btn-outline-secondary toggle-pw" type="button"
                                    data-target="password_confirm" aria-label="Hiện/ẩn">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Trạng thái</label>
                        <select class="form-select" id="status" name="status">
                            <?php $curStatus = (string)($old['status'] ?? $user['status'] ?? '1'); ?>
                            <option value="1" <?= $curStatus === '1' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="0" <?= $curStatus === '0' ? 'selected' : '' ?>>Bị khóa</option>
                        </select>
                    </div>
                </div>

                <!-- Roles -->
                <?php if (!empty($roles)): ?>
                    <!-- Marker báo cho controller biết phần roles đã được submit -->
                    <input type="hidden" name="_roles_submitted" value="1">
                    <div class="mt-4 pt-3 border-top">
                        <div class="form-label">
                            <i class="ti ti-shield me-1 text-primary"></i>
                            <strong>Vai trò &amp; Phân quyền</strong>
                        </div>
                        <div class="row g-2">
                            <?php foreach ($roles as $role): ?>
                                <div class="col-md-4 col-sm-6">
                                    <label class="form-check border rounded p-2 d-flex align-items-center gap-2 m-0">
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
                        <i class="ti ti-device-floppy me-1"></i>Lưu thay đổi
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
