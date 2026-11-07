<?php
$pageTitle       = 'Hồ sơ cá nhân';
$pageHeading     = 'Hồ sơ cá nhân';
$pageDescription = 'Thông tin tài khoản và cập nhật hồ sơ cá nhân';
$activePage      = 'profile';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$user    = $user    ?? [];
$roles   = $roles   ?? [];
$errors  = $errors  ?? [];
$success = $success ?? null;
$error   = $error   ?? null;

$roleLabels = [
    'admin'           => 'Quản trị viên',
    'training_dept'   => 'Phòng đào tạo',
    'department_head' => 'Trưởng bộ môn',
    'lecturer'        => 'Giảng viên',
    'student'         => 'Sinh viên',
    'enterprise'      => 'Doanh nghiệp',
];

$roleBadgeClass = [
    'admin'           => 'bg-red',
    'training_dept'   => 'bg-purple',
    'department_head' => 'bg-orange',
    'lecturer'        => 'bg-blue',
    'student'         => 'bg-green',
    'enterprise'      => 'bg-teal',
];

// ── Tính chữ cái viết tắt avatar ─────────────────────────────────────────
$fullName  = $user['full_name'] ?? '';
$nameParts = array_values(array_filter(explode(' ', $fullName)));
if (count($nameParts) >= 2) {
    $initials = mb_strtoupper(
        mb_substr($nameParts[0], 0, 1) .
        mb_substr($nameParts[count($nameParts) - 1], 0, 1)
    );
} elseif (count($nameParts) === 1) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 2));
} else {
    $initials = 'ND';
}

// ── Màu avatar nhất quán theo username ───────────────────────────────────
$avatarPalette = ['bg-blue','bg-azure','bg-indigo','bg-purple','bg-pink',
                  'bg-red','bg-orange','bg-yellow','bg-lime','bg-green','bg-teal','bg-cyan'];
$avatarBg      = $avatarPalette[abs(crc32($user['username'] ?? '')) % count($avatarPalette)];

// ── Phân loại lỗi cho đúng tab ───────────────────────────────────────────
$pwErrors   = [];
$infoErrors = [];
foreach ($errors as $e) {
    if (str_contains($e, 'ật khẩu')) {
        $pwErrors[] = $e;
    } else {
        $infoErrors[] = $e;
    }
}
$activeTab = (!empty($pwErrors) && empty($infoErrors)) ? 'password' : 'info';

$isActive = (int)($user['status'] ?? 1) === 1;
?>

<style>
/* ───────────────────────────────────────────────
   Profile page – scoped styles
   Tất cả selector bắt đầu bằng .pr- để không
   xung đột với Tabler globals.
─────────────────────────────────────────────── */

/* Hero banner */
.pr-hero {
    position: relative;
    overflow: hidden;
    padding: 2.25rem 2rem 5rem;
    background: linear-gradient(135deg, #1a56db 0%, #5850ec 60%, #7c3aed 100%);
    border-radius: var(--tblr-border-radius-lg) var(--tblr-border-radius-lg) 0 0;
}
.pr-hero::before {
    content: '';
    position: absolute;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    right: -90px; top: -140px;
    pointer-events: none;
}
.pr-hero::after {
    content: '';
    position: absolute;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    right: 160px; bottom: -100px;
    pointer-events: none;
}

/* Avatar viết tắt lớn */
.pr-avatar {
    width: 5.5rem;
    height: 5.5rem;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,.35);
    box-shadow: 0 8px 24px rgba(0,0,0,.2);
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: .02em;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
}

/* Card body offset (kéo nội dung lên chồng hero) */
.pr-body-offset {
    margin-top: -3.5rem;
    padding: 0 1.5rem 1.5rem;
}

/* Stat rows bên trái */
.pr-stat {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .75rem 0;
    border-bottom: 1px solid var(--tblr-border-color);
}
.pr-stat:last-child { border-bottom: none; }
.pr-stat-icon {
    width: 2rem; height: 2rem;
    border-radius: .5rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
    margin-top: .1rem;
}
.pr-stat-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--tblr-secondary);
    margin-bottom: .2rem;
}
.pr-stat-value {
    font-weight: 500;
    word-break: break-word;
}

/* Tab navigation */
.pr-tabs {
    border-bottom: none;
    padding: 0 1rem;
    gap: .25rem;
}
.pr-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;
    padding: 1rem 1.1rem;
    font-weight: 600;
    font-size: .875rem;
    color: var(--tblr-secondary);
    display: flex;
    align-items: center;
    gap: .45rem;
    transition: color .15s, border-color .15s;
}
.pr-tabs .nav-link:hover    { color: var(--tblr-primary); }
.pr-tabs .nav-link.active   {
    color: var(--tblr-primary);
    border-bottom-color: var(--tblr-primary);
    background: transparent;
}
.pr-tabs .nav-link .ti      { font-size: 1.1rem; }

/* Password strength bar */
.pr-strength { height: 5px; border-radius: 3px; overflow: hidden; background: var(--tblr-border-color); margin-top: .5rem; }
.pr-strength-fill { height: 100%; border-radius: 3px; transition: width .25s, background .25s; }

/* Readonly field dimmed */
.form-control[readonly] { background-color: var(--tblr-light) !important; cursor: not-allowed; }
</style>

<div class="container-xl py-4">

    <!-- ── Flash alerts ──────────────────────────────────────────────── -->
    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="ti ti-circle-check fs-3 text-success flex-shrink-0"></i>
            <div><?= $escape($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="ti ti-alert-circle fs-3 text-danger flex-shrink-0"></i>
            <div><?= $escape($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ══════════════════════════════════════════════════════════════
             CỘT TRÁI – Thẻ thông tin tóm tắt
        ══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-4 col-xl-3">
            <div class="card shadow-sm border-0 overflow-hidden h-100">

                <!-- Hero banner -->
                <div class="pr-hero">
                    <div class="d-flex flex-column align-items-center text-center gap-3 position-relative">
                        <!-- Avatar -->
                        <span class="pr-avatar <?= $escape($avatarBg) ?>">
                            <?= $escape($initials) ?>
                        </span>
                        <!-- Tên & username -->
                        <div class="text-white">
                            <h2 class="h4 fw-bold mb-1 text-white"><?= $escape($user['full_name'] ?? '') ?></h2>
                            <p class="mb-0 opacity-75 small">@<?= $escape($user['username'] ?? '') ?></p>
                        </div>
                        <!-- Vai trò -->
                        <div class="d-flex flex-wrap justify-content-center gap-1">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $role): ?>
                                    <span class="badge bg-white bg-opacity-20 text-white border border-white border-opacity-25 fw-medium">
                                        <?= $escape($roleLabels[$role] ?? $role) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="badge bg-white bg-opacity-20 text-white">Chưa có vai trò</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Thông tin chi tiết (kéo lên chồng hero) -->
                <div class="pr-body-offset">
                    <div class="card border shadow-sm rounded-3 p-3">

                        <!-- Email -->
                        <div class="pr-stat">
                            <span class="pr-stat-icon bg-blue-lt text-blue">
                                <i class="ti ti-mail"></i>
                            </span>
                            <div>
                                <div class="pr-stat-label">Email</div>
                                <div class="pr-stat-value"><?= $escape($user['email'] ?? '') ?></div>
                            </div>
                        </div>

                        <!-- Điện thoại -->
                        <div class="pr-stat">
                            <span class="pr-stat-icon bg-teal-lt text-teal">
                                <i class="ti ti-phone"></i>
                            </span>
                            <div>
                                <div class="pr-stat-label">Điện thoại</div>
                                <?php if (!empty($user['phone'])): ?>
                                    <div class="pr-stat-value"><?= $escape($user['phone']) ?></div>
                                <?php else: ?>
                                    <div class="pr-stat-value text-secondary fst-italic">Chưa cập nhật</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Trạng thái -->
                        <div class="pr-stat">
                            <span class="pr-stat-icon <?= $isActive ? 'bg-green-lt text-green' : 'bg-red-lt text-red' ?>">
                                <i class="ti <?= $isActive ? 'ti-shield-check' : 'ti-shield-off' ?>"></i>
                            </span>
                            <div>
                                <div class="pr-stat-label">Trạng thái</div>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success-lt text-success">
                                        <i class="ti ti-check me-1"></i>Đang hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-lt text-danger">
                                        <i class="ti ti-lock me-1"></i>Bị khóa
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Ngày tham gia -->
                        <div class="pr-stat">
                            <span class="pr-stat-icon bg-azure-lt text-azure">
                                <i class="ti ti-calendar-event"></i>
                            </span>
                            <div>
                                <div class="pr-stat-label">Ngày tham gia</div>
                                <div class="pr-stat-value">
                                    <?php if (!empty($user['created_at'])): ?>
                                        <?= $escape(date('d/m/Y', strtotime($user['created_at']))) ?>
                                    <?php else: ?>
                                        <span class="text-secondary fst-italic">Không rõ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div><!-- /inner card -->
                </div><!-- /pr-body-offset -->
            </div><!-- /card -->
        </div><!-- /col-left -->

        <!-- ══════════════════════════════════════════════════════════════
             CỘT PHẢI – Tab chỉnh sửa thông tin
        ══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-8 col-xl-9">
            <div class="card shadow-sm border-0 h-100">

                <!-- Tab header -->
                <div class="card-header p-0 border-bottom">
                    <ul class="nav pr-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'info' ? 'active' : '' ?>"
                                    id="tab-btn-info"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-info"
                                    type="button" role="tab"
                                    aria-controls="tab-info"
                                    aria-selected="<?= $activeTab === 'info' ? 'true' : 'false' ?>">
                                <i class="ti ti-user-circle"></i>Thông tin cá nhân
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'password' ? 'active' : '' ?>"
                                    id="tab-btn-password"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-password"
                                    type="button" role="tab"
                                    aria-controls="tab-password"
                                    aria-selected="<?= $activeTab === 'password' ? 'true' : 'false' ?>">
                                <i class="ti ti-lock"></i>Đổi mật khẩu
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Tab content -->
                <div class="tab-content" id="profileTabsContent">

                    <!-- ═══════════════════════════════════
                         TAB 1 – Thông tin cá nhân
                    ═══════════════════════════════════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'info' ? 'show active' : '' ?>"
                         id="tab-info" role="tabpanel" aria-labelledby="tab-btn-info">
                        <div class="card-body p-4">

                            <!-- Lỗi info -->
                            <?php if (!empty($infoErrors)): ?>
                                <div class="alert alert-danger d-flex gap-2 mb-4" role="alert">
                                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-semibold mb-1">Không thể lưu thông tin:</div>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($infoErrors as $e): ?>
                                                <li><?= $escape($e) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= $escape($baseUrl) ?>/profile"
                                  autocomplete="on" novalidate>
                                <input type="hidden" name="_csrf"   value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="_action" value="update_profile">

                                <div class="row g-3">

                                    <!-- Họ và tên -->
                                    <div class="col-12">
                                        <label class="form-label required" for="full_name">Họ và tên</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-user"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control"
                                                   id="full_name" name="full_name"
                                                   value="<?= $escape($user['full_name'] ?? '') ?>"
                                                   maxlength="100"
                                                   autocomplete="name"
                                                   placeholder="Nhập họ và tên đầy đủ"
                                                   required>
                                        </div>
                                        <div class="form-hint">Tối đa 100 ký tự.</div>
                                    </div>

                                    <!-- Số điện thoại -->
                                    <div class="col-12">
                                        <label class="form-label" for="phone">Số điện thoại</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-phone"></i>
                                            </span>
                                            <input type="tel"
                                                   class="form-control"
                                                   id="phone" name="phone"
                                                   value="<?= $escape($user['phone'] ?? '') ?>"
                                                   minlength="8" maxlength="20"
                                                   pattern="[0-9+\s().\-]{8,20}"
                                                   autocomplete="tel"
                                                   placeholder="Ví dụ: 0901 234 567">
                                        </div>
                                        <div class="form-hint">Để trống nếu chưa muốn cập nhật.</div>
                                    </div>

                                    <!-- Email (readonly) -->
                                    <div class="col-md-6">
                                        <label class="form-label" for="email_ro">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-mail"></i>
                                            </span>
                                            <input type="email"
                                                   class="form-control"
                                                   id="email_ro"
                                                   value="<?= $escape($user['email'] ?? '') ?>"
                                                   readonly tabindex="-1">
                                        </div>
                                        <div class="form-hint">Quản lý bởi quản trị viên.</div>
                                    </div>

                                    <!-- Tên đăng nhập (readonly) -->
                                    <div class="col-md-6">
                                        <label class="form-label" for="username_ro">Tên đăng nhập</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-at"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control"
                                                   id="username_ro"
                                                   value="<?= $escape($user['username'] ?? '') ?>"
                                                   readonly tabindex="-1">
                                        </div>
                                        <div class="form-hint">Không thể thay đổi.</div>
                                    </div>

                                </div><!-- /row -->

                                <!-- Roles display -->
                                <?php if (!empty($roles)): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="form-label text-secondary small mb-2">
                                            <i class="ti ti-shield me-1"></i>Vai trò được gán
                                        </div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($roles as $role): ?>
                                                <span class="badge <?= $escape($roleBadgeClass[$role] ?? 'bg-secondary') ?>-lt text-<?= $escape(str_replace('bg-', '', $roleBadgeClass[$role] ?? 'bg-secondary')) ?> border border-<?= $escape(str_replace('bg-', '', $roleBadgeClass[$role] ?? 'bg-secondary')) ?>-subtle px-3 py-2">
                                                    <i class="ti ti-user-check me-1"></i>
                                                    <?= $escape($roleLabels[$role] ?? $role) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Lưu thay đổi
                                    </button>
                                </div>

                            </form>
                        </div><!-- /card-body -->
                    </div><!-- /tab-pane info -->

                    <!-- ═══════════════════════════════════
                         TAB 2 – Đổi mật khẩu
                    ═══════════════════════════════════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'password' ? 'show active' : '' ?>"
                         id="tab-password" role="tabpanel" aria-labelledby="tab-btn-password">
                        <div class="card-body p-4">

                            <!-- Lỗi password -->
                            <?php if (!empty($pwErrors)): ?>
                                <div class="alert alert-danger d-flex gap-2 mb-4" role="alert">
                                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-semibold mb-1">Không thể đổi mật khẩu:</div>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($pwErrors as $e): ?>
                                                <li><?= $escape($e) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Hướng dẫn bảo mật -->
                            <div class="alert alert-info d-flex gap-2 mb-4" role="alert">
                                <i class="ti ti-shield-lock fs-3 flex-shrink-0 text-info"></i>
                                <div>
                                    <div class="fw-semibold text-dark">Yêu cầu mật khẩu</div>
                                    <ul class="mb-0 small mt-1 ps-3 text-secondary">
                                        <li>Ít nhất <strong>6 ký tự</strong></li>
                                        <li>Kết hợp chữ HOA, chữ thường, số và ký tự đặc biệt để tăng độ bảo mật</li>
                                        <li>Không dùng lại mật khẩu cũ</li>
                                    </ul>
                                </div>
                            </div>

                            <form method="POST" action="<?= $escape($baseUrl) ?>/profile"
                                  autocomplete="off" novalidate id="form-change-password">
                                <input type="hidden" name="_csrf"   value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="_action" value="change_password">

                                <div class="row g-3">

                                    <!-- Mật khẩu hiện tại -->
                                    <div class="col-12">
                                        <label class="form-label required" for="current_password">
                                            Mật khẩu hiện tại
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="current_password" name="current_password"
                                                   autocomplete="current-password"
                                                   placeholder="Nhập mật khẩu hiện tại"
                                                   required>
                                            <button class="btn btn-outline-secondary toggle-pw"
                                                    type="button"
                                                    data-target="current_password"
                                                    aria-label="Hiện / ẩn mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mật khẩu mới -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="new_password">
                                            Mật khẩu mới
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-key"></i></span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="new_password" name="new_password"
                                                   autocomplete="new-password"
                                                   minlength="6" maxlength="72"
                                                   placeholder="Tối thiểu 6 ký tự"
                                                   required>
                                            <button class="btn btn-outline-secondary toggle-pw"
                                                    type="button"
                                                    data-target="new_password"
                                                    aria-label="Hiện / ẩn mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                        <!-- Strength bar -->
                                        <div id="pw-strength-wrap" style="display:none" class="mt-2">
                                            <div class="pr-strength">
                                                <div class="pr-strength-fill" id="pw-strength-fill"></div>
                                            </div>
                                            <div class="form-hint mt-1" id="pw-strength-label"></div>
                                        </div>
                                    </div>

                                    <!-- Xác nhận mật khẩu mới -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="new_password_confirm">
                                            Xác nhận mật khẩu mới
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-key"></i></span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="new_password_confirm" name="new_password_confirm"
                                                   autocomplete="new-password"
                                                   placeholder="Nhập lại mật khẩu mới"
                                                   required>
                                            <button class="btn btn-outline-secondary toggle-pw"
                                                    type="button"
                                                    data-target="new_password_confirm"
                                                    aria-label="Hiện / ẩn mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-hint mt-1" id="pw-match-hint"></div>
                                    </div>

                                </div><!-- /row -->

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="reset" class="btn btn-ghost-secondary">
                                        <i class="ti ti-x me-1"></i>Hủy
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-lock-check me-1"></i>Cập nhật mật khẩu
                                    </button>
                                </div>

                            </form>
                        </div><!-- /card-body -->
                    </div><!-- /tab-pane password -->

                </div><!-- /tab-content -->
            </div><!-- /card right -->
        </div><!-- /col-right -->

    </div><!-- /row -->
</div><!-- /container-xl -->

<script>
(function () {
    'use strict';

    /* ── Toggle hiện / ẩn mật khẩu ─────────────────────────── */
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var icon  = btn.querySelector('i');
            if (!input) return;
            var isHidden = input.type === 'password';
            input.type   = isHidden ? 'text' : 'password';
            icon.classList.toggle('ti-eye',     !isHidden);
            icon.classList.toggle('ti-eye-off',  isHidden);
        });
    });

    /* ── Strength meter ─────────────────────────────────────── */
    var newPwInput    = document.getElementById('new_password');
    var strengthWrap  = document.getElementById('pw-strength-wrap');
    var strengthFill  = document.getElementById('pw-strength-fill');
    var strengthLabel = document.getElementById('pw-strength-label');

    var levels = [
        { pct:  20, color: '#ef4444', label: 'Rất yếu'   },
        { pct:  40, color: '#f97316', label: 'Yếu'        },
        { pct:  60, color: '#eab308', label: 'Trung bình' },
        { pct:  80, color: '#06b6d4', label: 'Mạnh'       },
        { pct: 100, color: '#22c55e', label: 'Rất mạnh'   },
    ];

    function calcScore(pw) {
        var s = 0;
        if (pw.length >= 6)           s++;
        if (pw.length >= 10)          s++;
        if (/[A-Z]/.test(pw))         s++;
        if (/[0-9]/.test(pw))         s++;
        if (/[^A-Za-z0-9]/.test(pw))  s++;
        return s;
    }

    if (newPwInput) {
        newPwInput.addEventListener('input', function () {
            var pw = newPwInput.value;
            if (!pw) { strengthWrap.style.display = 'none'; return; }
            strengthWrap.style.display = 'block';
            var lvl = levels[Math.min(calcScore(pw), 5) - 1] || levels[0];
            strengthFill.style.width      = lvl.pct + '%';
            strengthFill.style.background = lvl.color;
            strengthLabel.textContent     = 'Độ mạnh: ' + lvl.label;
            checkMatch();
        });
    }

    /* ── Kiểm tra mật khẩu khớp ─────────────────────────────── */
    var confirmInput = document.getElementById('new_password_confirm');
    var matchHint    = document.getElementById('pw-match-hint');

    function checkMatch() {
        if (!confirmInput || !newPwInput || !confirmInput.value) {
            if (matchHint) matchHint.textContent = '';
            return;
        }
        var match = newPwInput.value === confirmInput.value;
        matchHint.textContent = match ? '✓ Mật khẩu khớp' : '✗ Mật khẩu không khớp';
        matchHint.style.color = match ? 'var(--tblr-success)' : 'var(--tblr-danger)';
        matchHint.style.fontWeight = '600';
    }

    if (confirmInput) confirmInput.addEventListener('input', checkMatch);

    /* ── Client-side guard trước khi submit ────────────────── */
    var pwForm = document.getElementById('form-change-password');
    if (pwForm) {
        pwForm.addEventListener('submit', function (e) {
            var cur  = document.getElementById('current_password');
            var nw   = document.getElementById('new_password');
            var conf = document.getElementById('new_password_confirm');

            if (!cur.value.trim() || !nw.value.trim() || !conf.value.trim()) {
                e.preventDefault();
                showInlineError('Vui lòng điền đầy đủ tất cả các trường mật khẩu.');
                return;
            }
            if (nw.value !== conf.value) {
                e.preventDefault();
                showInlineError('Mật khẩu xác nhận không khớp.');
                return;
            }
            if (nw.value.length < 6) {
                e.preventDefault();
                showInlineError('Mật khẩu mới phải có ít nhất 6 ký tự.');
            }
        });
    }

    function showInlineError(msg) {
        var existing = document.getElementById('pw-client-error');
        if (existing) existing.remove();
        var div = document.createElement('div');
        div.id = 'pw-client-error';
        div.className = 'alert alert-danger d-flex align-items-center gap-2 mt-3';
        div.setAttribute('role', 'alert');
        div.innerHTML = '<i class="ti ti-alert-circle fs-3"></i><div>' + msg + '</div>';
        if (pwForm) pwForm.prepend(div);
        setTimeout(function () { if (div.parentNode) div.remove(); }, 5000);
    }

})();
</script>

<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
