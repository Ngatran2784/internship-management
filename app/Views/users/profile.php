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
    'admin'           => 'bg-primary text-white',
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

// ── Màu avatar nhất quán theo username khi chưa có ảnh ──────────────────
$avatarPalette = ['bg-blue','bg-azure','bg-indigo','bg-purple','bg-pink',
                  'bg-red','bg-orange','bg-yellow','bg-lime','bg-green','bg-teal','bg-cyan'];
$avatarBg      = $avatarPalette[abs(crc32($user['username'] ?? '')) % count($avatarPalette)];

// ── Trạng thái ảnh đại diện ─────────────────────────────────────────────
$userAvatar = $user['avatar_path'] ?? ($user['avatar'] ?? null);
$hasAvatar  = !empty($userAvatar);
$avatarUrl  = $hasAvatar ? ($baseUrl . '/' . ltrim($userAvatar, '/')) : '';

// ── Phân loại lỗi cho đúng tab ───────────────────────────────────────────
$pwErrors     = [];
$infoErrors   = [];
$avatarErrors = [];
foreach ($errors as $e) {
    if (str_contains($e, 'ật khẩu')) {
        $pwErrors[] = $e;
    } elseif (str_contains($e, 'ảnh') || str_contains($e, 'Ảnh') || str_contains($e, 'tệp') || str_contains($e, 'Tệp')) {
        $avatarErrors[] = $e;
    } else {
        $infoErrors[] = $e;
    }
}

$tabParam = $_GET['tab'] ?? ($activeTab ?? 'info');
if (!empty($avatarErrors)) {
    $activeTab = 'avatar';
} elseif (!empty($pwErrors)) {
    $activeTab = 'password';
} elseif (!empty($infoErrors)) {
    $activeTab = 'info';
} else {
    $activeTab = in_array($tabParam, ['info', 'password', 'avatar'], true) ? $tabParam : 'info';
}

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
    background: linear-gradient(135deg, #002B55 0%, #003B71 60%, #1769AA 100%);
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

/* Avatar tròn lớn */
.pr-avatar {
    width: 6rem;
    height: 6rem;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,.5);
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    font-size: 1.85rem;
    font-weight: 800;
    letter-spacing: .02em;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #fff;
    object-fit: cover;
    background-color: #002B55;
}

/* Preview ảnh đại diện */
.pr-avatar-preview {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}

.pr-avatar-dropzone {
    border: 2px dashed #b8c9dc;
    border-radius: 12px;
    background-color: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    padding: 1.75rem 1.25rem;
    text-align: center;
}
.pr-avatar-dropzone:hover, .pr-avatar-dropzone.dragover {
    border-color: var(--vinhuni-primary, #003B71);
    background-color: #eaf2fa;
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
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--tblr-secondary);
    margin-bottom: .15rem;
}
.pr-stat-value {
    font-size: .875rem;
    font-weight: 600;
    color: var(--tblr-body-color);
    word-break: break-all;
}

/* Nav tabs */
.pr-tabs {
    border-bottom: 2px solid var(--tblr-border-color);
    padding: 0 1.5rem;
    gap: .5rem;
}
.pr-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 1rem .75rem;
    font-weight: 600;
    font-size: .875rem;
    color: var(--tblr-secondary);
    margin-bottom: -2px;
    display: flex;
    align-items: center;
    gap: .45rem;
    transition: color .15s, border-color .15s;
}
.pr-tabs .nav-link:hover    { color: var(--vinhuni-primary, #003B71); }
.pr-tabs .nav-link.active   {
    color: var(--vinhuni-primary, #003B71);
    border-bottom-color: var(--vinhuni-primary, #003B71);
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
             CỘT TRÁI – Thẻ thông tin tóm tắt & Avatar
        ══════════════════════════════════════════════════════════════════ -->
        <div class="col-lg-4 col-xl-3">
            <div class="card shadow-sm border-0 overflow-hidden h-100">

                <!-- Hero banner -->
                <div class="pr-hero">
                    <div class="d-flex flex-column align-items-center text-center gap-3 position-relative">
                        <!-- Avatar Wrapper -->
                        <div class="position-relative d-inline-block">
                            <?php if ($hasAvatar): ?>
                                <img src="<?= $escape($avatarUrl) ?>"
                                     alt="Ảnh đại diện"
                                     class="pr-avatar rounded-circle"
                                     id="heroAvatarImg">
                            <?php else: ?>
                                <span class="pr-avatar rounded-circle <?= $escape($avatarBg) ?>" id="heroAvatarPlaceholder">
                                    <?= $escape($initials) ?>
                                </span>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-sm btn-icon rounded-circle btn-primary position-absolute bottom-0 end-0 shadow"
                                    style="transform: translate(10%, 10%); width: 2.25rem; height: 2.25rem;"
                                    onclick="document.getElementById('tab-btn-avatar').click();"
                                    title="Thay đổi ảnh đại diện">
                                <i class="ti ti-camera fs-2"></i>
                            </button>
                        </div>
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

                        <!-- Số điện thoại -->
                        <div class="pr-stat">
                            <span class="pr-stat-icon bg-green-lt text-green">
                                <i class="ti ti-phone"></i>
                            </span>
                            <div>
                                <div class="pr-stat-label">Số điện thoại</div>
                                <div class="pr-stat-value">
                                    <?php if (!empty($user['phone'])): ?>
                                        <?= $escape($user['phone']) ?>
                                    <?php else: ?>
                                        <span class="text-secondary fw-normal fst-italic">Chưa cập nhật</span>
                                    <?php endif; ?>
                                </div>
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
             CỘT PHẢI – Tab chức năng hồ sơ
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
                            <button class="nav-link <?= $activeTab === 'avatar' ? 'active' : '' ?>"
                                    id="tab-btn-avatar"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-avatar"
                                    type="button" role="tab"
                                    aria-controls="tab-avatar"
                                    aria-selected="<?= $activeTab === 'avatar' ? 'true' : 'false' ?>">
                                <i class="ti ti-photo-edit"></i>Ảnh đại diện
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
                                        <label class="form-label" for="ro_email">
                                            Email
                                            <span class="badge bg-secondary-lt ms-1">Cố định</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text text-secondary">
                                                <i class="ti ti-mail"></i>
                                            </span>
                                            <input type="email"
                                                   class="form-control"
                                                   id="ro_email"
                                                   value="<?= $escape($user['email'] ?? '') ?>"
                                                   readonly
                                                   tabindex="-1">
                                        </div>
                                        <div class="form-hint">Liên hệ Quản trị viên nếu cần đổi email.</div>
                                    </div>

                                    <!-- Tên đăng nhập (readonly) -->
                                    <div class="col-md-6">
                                        <label class="form-label" for="ro_username">
                                            Tên đăng nhập
                                            <span class="badge bg-secondary-lt ms-1">Cố định</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text text-secondary">
                                                <i class="ti ti-at"></i>
                                            </span>
                                            <input type="text"
                                                   class="form-control"
                                                   id="ro_username"
                                                   value="<?= $escape($user['username'] ?? '') ?>"
                                                   readonly
                                                   tabindex="-1">
                                        </div>
                                        <div class="form-hint">Tên đăng nhập không thể thay đổi.</div>
                                    </div>

                                </div><!-- /row g-3 -->

                                <div class="mt-4 pt-3 border-top d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-device-floppy me-1"></i>Lưu thay đổi
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div><!-- /tab-info -->

                    <!-- ═══════════════════════════════════
                         TAB 2 – Quản lý Ảnh đại diện
                    ═══════════════════════════════════════ -->
                    <div class="tab-pane fade <?= $activeTab === 'avatar' ? 'show active' : '' ?>"
                         id="tab-avatar" role="tabpanel" aria-labelledby="tab-btn-avatar">
                        <div class="card-body p-4">

                            <!-- Lỗi avatar -->
                            <?php if (!empty($avatarErrors)): ?>
                                <div class="alert alert-danger d-flex gap-2 mb-4" role="alert">
                                    <i class="ti ti-alert-circle fs-3 flex-shrink-0"></i>
                                    <div>
                                        <div class="fw-semibold mb-1">Không thể cập nhật ảnh đại diện:</div>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($avatarErrors as $e): ?>
                                                <li><?= $escape($e) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row g-4 align-items-center">
                                <!-- Khung xem trước ảnh tròn -->
                                <div class="col-md-5 text-center">
                                    <div class="mb-3">
                                        <div class="text-secondary small fw-bold text-uppercase mb-2">Ảnh hiện tại / Xem trước</div>
                                        <div id="avatarPreviewContainer" class="d-inline-block position-relative">
                                            <?php if ($hasAvatar): ?>
                                                <img src="<?= $escape($avatarUrl) ?>"
                                                     alt="Ảnh đại diện"
                                                     class="pr-avatar-preview"
                                                     id="avatarPreviewImg">
                                            <?php else: ?>
                                                <span class="pr-avatar-preview <?= $escape($avatarBg) ?> d-inline-flex align-items-center justify-content-center text-white fs-1 fw-bold"
                                                      id="avatarPreviewPlaceholder">
                                                    <?= $escape($initials) ?>
                                                </span>
                                                <img src="" alt="Xem trước" class="pr-avatar-preview d-none" id="avatarPreviewImg">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div id="fileInfoBadge" class="d-none">
                                        <span class="badge bg-primary-lt px-2 py-1" id="fileInfoText"></span>
                                    </div>
                                </div>

                                <!-- Form tải lên & tùy chọn -->
                                <div class="col-md-7">
                                    <h3 class="h4 fw-bold text-dark mb-2">Tải lên ảnh mới</h3>
                                    <p class="text-secondary small mb-3">
                                        Tải lên hình ảnh khuôn mặt rõ nét của bạn. Ảnh sẽ được hiển thị dạng hình tròn tại Trang cá nhân và Thanh điều hướng Header.
                                    </p>

                                    <form action="<?= $escape($baseUrl) ?>/profile" method="POST" enctype="multipart/form-data" id="form-upload-avatar">
                                        <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="_action" value="upload_avatar">
                                        <input type="file" id="avatarFileInput" name="avatar" accept="image/jpeg,image/png,image/webp" class="d-none">

                                        <!-- Vùng kéo thả file -->
                                        <div class="pr-avatar-dropzone mb-3" id="avatarDropzone" onclick="document.getElementById('avatarFileInput').click();">
                                            <i class="ti ti-cloud-upload fs-1 text-primary mb-2 d-block"></i>
                                            <div class="fw-bold text-dark mb-1">Nhấp để chọn ảnh hoặc kéo thả vào đây</div>
                                            <div class="text-secondary small">Chấp nhận JPG, PNG hoặc WebP &bull; Dung lượng tối đa 2 MB</div>
                                        </div>

                                        <!-- Nút thao tác -->
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('avatarFileInput').click();">
                                                <i class="ti ti-photo me-1"></i>Chọn ảnh từ máy
                                            </button>
                                            <button type="submit" class="btn btn-primary" id="saveAvatarBtn" disabled>
                                                <i class="ti ti-check me-1"></i>Lưu ảnh đại diện
                                            </button>
                                            <button type="button" class="btn btn-ghost-secondary d-none" id="cancelAvatarBtn">
                                                Hủy chọn
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Nút xóa ảnh đại diện nếu đang có ảnh -->
                                    <?php if ($hasAvatar): ?>
                                        <hr class="my-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <div class="fw-semibold text-danger">Xóa ảnh đại diện</div>
                                                <div class="text-secondary small">Gỡ ảnh hiện tại và quay về hiển thị chữ viết tắt của bạn.</div>
                                            </div>
                                            <form action="<?= $escape($baseUrl) ?>/profile" method="POST" id="form-delete-avatar" onsubmit="return confirm('Bạn có chắc chắn muốn xóa ảnh đại diện hiện tại?');" class="m-0">
                                                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="_action" value="delete_avatar">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="ti ti-trash me-1"></i>Xóa ảnh
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>

                        </div>
                    </div><!-- /tab-avatar -->

                    <!-- ═══════════════════════════════════
                         TAB 3 – Đổi mật khẩu
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

                            <div class="alert alert-info d-flex gap-2 mb-4" role="alert">
                                <i class="ti ti-info-circle fs-3 flex-shrink-0"></i>
                                <div class="small">
                                    Mật khẩu mới phải có <strong>ít nhất 6 ký tự</strong>. Nên kết hợp
                                    chữ hoa, chữ thường, số và ký tự đặc biệt để tài khoản được bảo vệ tốt nhất.
                                </div>
                            </div>

                            <form method="POST" action="<?= $escape($baseUrl) ?>/profile"
                                  id="form-change-password" autocomplete="off" novalidate>
                                <input type="hidden" name="_csrf"   value="<?= $escape($csrfToken) ?>">
                                <input type="hidden" name="_action" value="change_password">

                                <div class="row g-3">

                                    <!-- Mật khẩu hiện tại -->
                                    <div class="col-12">
                                        <label class="form-label required" for="current_password">Mật khẩu hiện tại</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-key"></i>
                                            </span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="current_password" name="current_password"
                                                   autocomplete="current-password"
                                                   placeholder="Nhập mật khẩu hiện tại"
                                                   required>
                                            <button class="btn btn-outline-secondary btn-icon"
                                                    type="button"
                                                    data-toggle-target="current_password"
                                                    aria-label="Ẩn / Hiện mật khẩu"
                                                    title="Ẩn / Hiện mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Mật khẩu mới -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="new_password">Mật khẩu mới</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-lock"></i>
                                            </span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="new_password" name="new_password"
                                                   minlength="6"
                                                   autocomplete="new-password"
                                                   placeholder="Tối thiểu 6 ký tự"
                                                   required>
                                            <button class="btn btn-outline-secondary btn-icon"
                                                    type="button"
                                                    data-toggle-target="new_password"
                                                    aria-label="Ẩn / Hiện mật khẩu"
                                                    title="Ẩn / Hiện mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                        <!-- Thanh đo độ mạnh mật khẩu -->
                                        <div class="pr-strength" id="pw-strength-wrap" style="display:none;">
                                            <div class="pr-strength-fill" id="pw-strength-fill" style="width:0%;"></div>
                                        </div>
                                        <div class="form-hint" id="pw-strength-label"></div>
                                    </div>

                                    <!-- Xác nhận mật khẩu mới -->
                                    <div class="col-md-6">
                                        <label class="form-label required" for="new_password_confirm">Xác nhận mật khẩu mới</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-lock-check"></i>
                                            </span>
                                            <input type="password"
                                                   class="form-control"
                                                   id="new_password_confirm" name="new_password_confirm"
                                                   minlength="6"
                                                   autocomplete="new-password"
                                                   placeholder="Nhập lại mật khẩu mới"
                                                   required>
                                            <button class="btn btn-outline-secondary btn-icon"
                                                    type="button"
                                                    data-toggle-target="new_password_confirm"
                                                    aria-label="Ẩn / Hiện mật khẩu"
                                                    title="Ẩn / Hiện mật khẩu">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-hint" id="pw-match-hint"></div>
                                    </div>

                                </div><!-- /row g-3 -->

                                <div class="mt-4 pt-3 border-top d-flex gap-2 justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="ti ti-lock-check me-1"></i>Cập nhật mật khẩu
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div><!-- /tab-password -->

                </div><!-- /tab-content -->
            </div><!-- /card -->
        </div><!-- /col-right -->

    </div><!-- /row g-4 -->
</div><!-- /container -->

<script>
(function () {
    'use strict';

    /* ── Ẩn / Hiện mật khẩu ──────────────────────────────────── */
    document.querySelectorAll('[data-toggle-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-toggle-target');
            var input    = document.getElementById(targetId);
            var icon     = this.querySelector('.ti');
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                if (icon) { icon.classList.remove('ti-eye'); icon.classList.add('ti-eye-off'); }
            } else {
                input.type = 'password';
                if (icon) { icon.classList.remove('ti-eye-off'); icon.classList.add('ti-eye'); }
            }
        });
    });

    /* ── Xử lý Live Preview & Drag and Drop Ảnh đại diện ────── */
    var fileInput          = document.getElementById('avatarFileInput');
    var dropzone           = document.getElementById('avatarDropzone');
    var previewImg         = document.getElementById('avatarPreviewImg');
    var previewPlaceholder = document.getElementById('avatarPreviewPlaceholder');
    var saveAvatarBtn      = document.getElementById('saveAvatarBtn');
    var cancelAvatarBtn    = document.getElementById('cancelAvatarBtn');
    var fileInfoBadge      = document.getElementById('fileInfoBadge');
    var fileInfoText       = document.getElementById('fileInfoText');

    function handleSelectedFile(file) {
        if (!file) return;

        var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (allowedTypes.indexOf(file.type) === -1) {
            alert('Định dạng tệp không hợp lệ. Vui lòng chỉ chọn ảnh JPG, PNG hoặc WebP.');
            if (fileInput) fileInput.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            alert('Dung lượng tệp vượt quá 2 MB (' + (file.size / 1024 / 1024).toFixed(2) + ' MB). Vui lòng chọn ảnh nhỏ hơn 2 MB.');
            if (fileInput) fileInput.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            if (previewPlaceholder) previewPlaceholder.classList.add('d-none');
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
            }
            if (saveAvatarBtn) saveAvatarBtn.removeAttribute('disabled');
            if (cancelAvatarBtn) cancelAvatarBtn.classList.remove('d-none');
            if (fileInfoBadge && fileInfoText) {
                fileInfoText.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                fileInfoBadge.classList.remove('d-none');
            }
        };
        reader.readAsDataURL(file);
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                handleSelectedFile(fileInput.files[0]);
            }
        });
    }

    if (cancelAvatarBtn) {
        cancelAvatarBtn.addEventListener('click', function () {
            if (fileInput) fileInput.value = '';
            if (fileInfoBadge) fileInfoBadge.classList.add('d-none');
            if (saveAvatarBtn) saveAvatarBtn.setAttribute('disabled', 'disabled');
            cancelAvatarBtn.classList.add('d-none');

            <?php if ($hasAvatar): ?>
                if (previewImg) {
                    previewImg.src = '<?= $escape($avatarUrl) ?>';
                    previewImg.classList.remove('d-none');
                }
                if (previewPlaceholder) previewPlaceholder.classList.add('d-none');
            <?php else: ?>
                if (previewImg) previewImg.classList.add('d-none');
                if (previewPlaceholder) previewPlaceholder.classList.remove('d-none');
            <?php endif; ?>
        });
    }

    if (dropzone) {
        ['dragenter', 'dragover'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (eventName) {
            dropzone.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            var dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) {
                if (fileInput) fileInput.files = dt.files;
                handleSelectedFile(dt.files[0]);
            }
        });
    }

    /* ── Đo độ mạnh mật khẩu ─────────────────────────────────── */
    var newPwInput    = document.getElementById('new_password');
    var strengthWrap  = document.getElementById('pw-strength-wrap');
    var strengthFill  = document.getElementById('pw-strength-fill');
    var strengthLabel = document.getElementById('pw-strength-label');

    var levels = [
        { label: 'Rất yếu', color: 'var(--tblr-danger)',  pct: 20 },
        { label: 'Yếu',     color: 'var(--tblr-warning)', pct: 40 },
        { label: 'Trung bình', color: 'var(--tblr-yellow)', pct: 60 },
        { label: 'Khá',     color: 'var(--tblr-info)',    pct: 80 },
        { label: 'Mạnh',    color: 'var(--tblr-success)', pct: 100 },
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