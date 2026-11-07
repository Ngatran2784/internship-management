<?php
$pageTitle       = 'Quản lý người dùng';
$pageHeading     = 'Quản lý người dùng';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage      = 'users';
require dirname(__DIR__) . '/layouts/header.php';

$escape  = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$users      = $users    ?? [];
$allUsers   = $allUsers ?? $users;
$roles      = $roles    ?? [];
$filters    = $filters  ?? ['role' => '', 'keyword' => '', 'status' => ''];
$success    = $success  ?? null;
$error      = $error    ?? null;

$roleLabels = [
    'admin'           => 'Quản trị viên',
    'training_dept'   => 'Phòng đào tạo',
    'department_head' => 'Trưởng bộ môn',
    'lecturer'        => 'Giảng viên',
    'student'         => 'Sinh viên',
    'enterprise'      => 'Doanh nghiệp',
];
$roleBadgeColor = [
    'admin'           => 'bg-red-lt text-red',
    'training_dept'   => 'bg-purple-lt text-purple',
    'department_head' => 'bg-orange-lt text-orange',
    'lecturer'        => 'bg-blue-lt text-blue',
    'student'         => 'bg-green-lt text-green',
    'enterprise'      => 'bg-teal-lt text-teal',
];

$initials = static function (string $name): string {
    $name  = trim($name);
    if ($name === '') return '?';
    $parts = preg_split('/\s+/u', $name) ?: [$name];
    return mb_strtoupper(mb_substr((string) end($parts), 0, 1, 'UTF-8'), 'UTF-8');
};
$avatarBgs = ['bg-blue','bg-azure','bg-indigo','bg-purple','bg-pink','bg-red','bg-orange','bg-yellow','bg-lime','bg-green','bg-teal','bg-cyan'];

// Khi server đã xử lý filter status, $users là kết quả filter
// Stat cards luôn tính từ $allUsers (toàn bộ hệ thống, không theo filter)
$filteredUsers = $users;
$totalActive   = count(array_filter($allUsers, fn($u) => (int)($u['status'] ?? 1) === 1));
$totalLocked   = count($allUsers) - $totalActive;
?>
<div class="container-xl">

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ti ti-circle-check fs-3"></i>
            <div><?= $escape($success) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="ti ti-alert-circle fs-3"></i>
            <div><?= $escape($error) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-blue-lt text-blue">
                                <i class="ti ti-users fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Tổng tài khoản</div>
                            <div class="h3 mb-0"><?= count($allUsers) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-green-lt text-green">
                                <i class="ti ti-user-check fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Đang hoạt động</div>
                            <div class="h3 mb-0"><?= $totalActive ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-red-lt text-red">
                                <i class="ti ti-user-off fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Bị khóa</div>
                            <div class="h3 mb-0"><?= $totalLocked ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="avatar bg-purple-lt text-purple">
                                <i class="ti ti-shield fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="subheader">Số vai trò</div>
                            <div class="h3 mb-0"><?= count($roles) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter + Table card -->
    <div class="card shadow-sm">
        <div class="card-header d-flex flex-column flex-md-row align-items-md-center gap-3 py-3">
            <div class="flex-fill">
                <form method="GET" action="<?= $escape($baseUrl) ?>/users"
                      class="row g-2 align-items-end">
                    <!-- Lọc vai trò -->
                    <div class="col-auto">
                        <label class="form-label visually-hidden" for="role-filter">Vai trò</label>
                        <select class="form-select form-select-sm" id="role-filter" name="role"
                                style="min-width:160px">
                            <option value="">Tất cả vai trò</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $escape($role['name']) ?>"
                                    <?= ($filters['role'] ?? '') === $role['name'] ? 'selected' : '' ?>>
                                    <?= $escape($role['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Lọc trạng thái -->
                    <div class="col-auto">
                        <label class="form-label visually-hidden" for="status-filter">Trạng thái</label>
                        <select class="form-select form-select-sm" id="status-filter" name="status"
                                style="min-width:150px">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>
                                Đang hoạt động
                            </option>
                            <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>
                                Bị khóa
                            </option>
                        </select>
                    </div>
                    <!-- Tìm kiếm -->
                    <div class="col">
                        <label class="form-label visually-hidden" for="keyword-filter">Tìm kiếm</label>
                        <input type="text" class="form-control form-control-sm"
                               id="keyword-filter" name="keyword"
                               value="<?= $escape($filters['keyword'] ?? '') ?>"
                               placeholder="Tìm theo tên, email, tài khoản...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-search me-1"></i>Tìm
                        </button>
                        <?php if (!empty($filters['role']) || !empty($filters['keyword']) || !empty($filters['status'])): ?>
                            <a href="<?= $escape($baseUrl) ?>/users"
                               class="btn btn-sm btn-outline-secondary ms-1">
                                <i class="ti ti-x me-1"></i>Xóa lọc
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0">
                <a href="<?= $escape($baseUrl) ?>/users/create" class="btn btn-primary btn-sm">
                    <i class="ti ti-user-plus me-1"></i>Tạo tài khoản
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th class="w-1 text-secondary">#</th>
                        <th>Người dùng</th>
                        <th>Tên đăng nhập</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th class="text-secondary small">Ngày tạo</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($filteredUsers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">
                            <i class="ti ti-database-off d-block mb-2" style="font-size:2rem;opacity:.4"></i>
                            <div class="fw-medium">Không có người dùng nào phù hợp</div>
                            <div class="small mt-1">Thử thay đổi bộ lọc hoặc
                                <a href="<?= $escape($baseUrl) ?>/users/create">tạo tài khoản mới</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredUsers as $user): ?>
                        <?php
                            $rolesList    = is_array($user['roles'] ?? null) ? $user['roles'] : [];
                            $statusActive = (int)($user['status'] ?? 1) === 1;
                            $avatarBg     = $avatarBgs[(int)($user['id'] ?? 0) % count($avatarBgs)];
                            $initial      = $initials((string)($user['full_name'] ?? ''));
                            $createdAt    = !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—';
                        ?>
                        <tr>
                            <td class="text-secondary small"><?= (int)$user['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar avatar-sm <?= $escape($avatarBg) ?> text-white">
                                        <?= $escape($initial) ?>
                                    </span>
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            <?= $escape($user['full_name'] ?? '') ?>
                                        </div>
                                        <div class="text-secondary small">
                                            <?= $escape($user['email'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium">
                                    <?= $escape($user['username'] ?? '') ?>
                                </span>
                                <?php if (!empty($user['phone'])): ?>
                                    <div class="text-secondary small">
                                        <i class="ti ti-phone me-1"></i><?= $escape($user['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (empty($rolesList)): ?>
                                    <span class="text-secondary small fst-italic">Chưa phân quyền</span>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($rolesList as $rn): ?>
                                            <span class="badge <?= $escape($roleBadgeColor[$rn] ?? 'bg-secondary-lt text-secondary') ?>">
                                                <?= $escape($roleLabels[$rn] ?? $rn) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($statusActive): ?>
                                    <span class="badge bg-success-lt text-success">
                                        <i class="ti ti-check me-1"></i>Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-lt text-danger">
                                        <i class="ti ti-lock me-1"></i>Bị khóa
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small">
                                <?= $escape($createdAt) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= $escape($baseUrl) ?>/users/edit?id=<?= (int)$user['id'] ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Chỉnh sửa tài khoản">
                                        <i class="ti ti-pencil me-1"></i>Sửa
                                    </a>
                                    <form action="<?= $escape($baseUrl) ?>/users/status" method="POST" class="m-0">
                                        <input type="hidden" name="_csrf"   value="<?= $escape($csrfToken) ?>">
                                        <input type="hidden" name="id"     value="<?= (int)$user['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $statusActive ? 0 : 1 ?>">
                                        <button type="submit"
                                                class="btn btn-sm <?= $statusActive ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                title="<?= $statusActive ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>"
                                                onclick="return confirm('<?= $statusActive ? 'Xác nhận khóa tài khoản này?' : 'Xác nhận mở khóa tài khoản này?' ?>')">
                                            <i class="ti <?= $statusActive ? 'ti-lock' : 'ti-lock-open' ?> me-1"></i>
                                            <?= $statusActive ? 'Khóa' : 'Mở khóa' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($filteredUsers)): ?>
            <div class="card-footer d-flex align-items-center justify-content-between py-2">
                <p class="m-0 text-secondary small">
                    Hiển thị <strong><?= count($filteredUsers) ?></strong>
                    <?php if (count($filteredUsers) < count($users)): ?>
                        / <?= count($users) ?>
                    <?php endif; ?>
                    tài khoản
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
