<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Role;
use App\Models\User;

/**
 * UserController - Quản lý người dùng: danh sách, thêm, sửa, khóa/mở khóa, gán vai trò, hồ sơ cá nhân
 *
 * Chỉ Admin được quản lý tài khoản. Mọi thao tác ghi dữ liệu đều kiểm tra CSRF.
 */
class UserController extends Controller
{
    /**
     * GET /users?role=&keyword=&status= - Danh sách người dùng kèm vai trò, có bộ lọc (chỉ admin)
     */
    public function index(): void
    {
        AuthController::requireRole('admin');

        $role    = self::text($_GET, 'role');
        $keyword = mb_substr(self::text($_GET, 'keyword'), 0, 100);
        $statusRaw = self::text($_GET, 'status');
        $status  = ($statusRaw === '0' || $statusRaw === '1') ? (int)$statusRaw : null;

        $this->respond('users/index', [
            'users'       => User::all($role !== '' ? $role : null, $keyword !== '' ? $keyword : null, $status),
            'allUsers'    => User::all(),   // dùng cho stat cards toàn cục (không filter)
            'roles'       => Role::all(),
            'filters'     => ['role' => $role, 'keyword' => $keyword, 'status' => $statusRaw],
            'success'     => AuthController::pullFlash('success'),
            'error'       => AuthController::pullFlash('error'),
            'csrfToken'   => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET /users/create - Form thêm tài khoản (chỉ admin)
     */
    public function create(): void
    {
        AuthController::requireRole('admin');

        $this->renderCreateForm([], []);
    }

    /**
     * POST /users hoặc /users/create - Lưu tài khoản mới (chỉ admin)
     * Tham số: username, email, password, full_name, phone, status (tùy chọn), role_ids[] (tùy chọn)
     */
    public function store(): void
    {
        AuthController::requireRole('admin');
        $this->guardPost('/users/create');

        $validRoleIds = array_map(fn($r) => (int)$r['id'], Role::all());
        $result       = self::validateNewUser($_POST, $validRoleIds);

        if ($result['errors']) {
            http_response_code(422);
            $this->renderCreateForm($result['errors'], self::oldInput($_POST));
            return;
        }

        $data = $result['data'];

        try {
            User::createWithRoles($data, $data['role_ids']);
        } catch (\PDOException $e) {
            // Hai người cùng tạo một lúc: CSDL chặn trùng bằng ràng buộc UNIQUE
            if ((string)$e->getCode() === '23000') {
                http_response_code(422);
                $this->renderCreateForm(['Tên đăng nhập hoặc email đã tồn tại.'], self::oldInput($_POST));
                return;
            }
            throw $e;
        }

        AuthController::setFlash('success', 'Đã tạo tài khoản mới.');
        AuthController::go('/users');
    }

    /**
     * GET /users/edit?id=... - Form sửa tài khoản (chỉ admin)
     */
    public function edit(): void
    {
        AuthController::requireRole('admin');

        $user = $this->findUserFromRequest($_GET['id'] ?? null);
        $this->renderEditForm($user, [], []);
    }

    /**
     * POST /users/update - Lưu thông tin sửa (chỉ admin)
     * Tham số: id, full_name, email, phone, password (để trống = giữ nguyên), status (tùy chọn)
     */
    public function update(): void
    {
        AuthController::requireRole('admin');
        $this->guardPost('/users');

        $user = $this->findUserFromRequest($_POST['id'] ?? null);
        $id   = (int)$user['id'];

        $result = self::validateUserUpdate($id, $_POST, (int)($_SESSION['user_id'] ?? 0));

        if ($result['errors']) {
            http_response_code(422);
            $this->renderEditForm($user, $result['errors'], self::oldInput($_POST));
            return;
        }

        try {
            User::update($id, $result['data']);

            // Cập nhật vai trò nếu form có gửi phần roles (có hidden field _roles_submitted)
            if (!empty($_POST['_roles_submitted'])) {
                $validRoleIds = array_map(fn($r) => (int)$r['id'], Role::all());
                $rawRoleIds   = is_array($_POST['role_ids'] ?? null) ? $_POST['role_ids'] : [];
                $newRoleIds   = [];

                foreach ($rawRoleIds as $raw) {
                    $rid = filter_var($raw, FILTER_VALIDATE_INT);
                    if ($rid !== false && in_array($rid, $validRoleIds, true)) {
                        $newRoleIds[] = $rid;
                    }
                }

                // Không cho admin tự gỡ quyền admin của chính mình
                if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                    $adminRole = \App\Models\Role::findByName('admin');
                    if ($adminRole !== null && !in_array((int)$adminRole['id'], $newRoleIds, true)) {
                        $this->renderEditForm($user, ['Bạn không thể tự gỡ vai trò Quản trị viên của chính mình.'], self::oldInput($_POST));
                        return;
                    }
                }

                User::assignRoles($id, $newRoleIds);

                // Cập nhật session roles nếu admin tự sửa chính mình
                if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['roles'] = User::getRoles($id);
                }
            }
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                http_response_code(422);
                $this->renderEditForm($user, ['Email đã được sử dụng.'], self::oldInput($_POST));
                return;
            }
            throw $e;
        }

        // Admin tự sửa họ tên của mình thì cập nhật luôn tên hiển thị trong session
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['full_name'] = $result['data']['full_name'];
        }

        AuthController::setFlash('success', 'Đã cập nhật tài khoản.');
        AuthController::go('/users');
    }

    /**
     * POST /users/status - Khóa / mở khóa tài khoản (chỉ admin)
     * Tham số: id, status (0 = khóa, 1 = mở khóa)
     */
    public function updateStatus(): void
    {
        AuthController::requireRole('admin');
        $this->guardPost('/users');

        $user   = $this->findUserFromRequest($_POST['id'] ?? null);
        $status = filter_var($_POST['status'] ?? null, FILTER_VALIDATE_INT);

        if ($status !== 0 && $status !== 1) {
            AuthController::setFlash('error', 'Trạng thái không hợp lệ.');
            AuthController::go('/users');
        }

        if (!self::canChangeStatus((int)($_SESSION['user_id'] ?? 0), (int)$user['id'], $status)) {
            AuthController::setFlash('error', 'Bạn không thể tự khóa tài khoản của chính mình.');
            AuthController::go('/users');
        }

        User::updateStatus((int)$user['id'], $status);

        AuthController::setFlash('success', $status === 1 ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
        AuthController::go('/users');
    }

    /**
     * GET|POST /profile - Xem và cập nhật hồ sơ cá nhân của chính mình
     * POST _action=update_profile  → cập nhật họ tên, số điện thoại
     * POST _action=change_password → đổi mật khẩu (xác minh mật khẩu hiện tại)
     */
        public function profile(): void
    {
        AuthController::requireLogin();
        $userId = (int)$_SESSION['user_id'];

        $user = User::findById($userId);
        if ($user === null || (int)$user['status'] !== 1) {
            // Tài khoản đã bị xóa/khóa trong lúc đang đăng nhập
            AuthController::clearSession();
            AuthController::go('/login');
        }

        $errors = [];
        $activeTab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'info';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!AuthController::verifyCsrf()) {
                AuthController::setFlash('error', 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
                AuthController::go('/profile');
            }

            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $action = is_string($_POST['_action'] ?? null) ? $_POST['_action'] : 'update_profile';
            if (str_contains($uri, '/profile/avatar/delete')) {
                $action = 'delete_avatar';
            } elseif (str_contains($uri, '/profile/avatar')) {
                $action = 'upload_avatar';
            }

            if ($action === 'upload_avatar') {
                // ── Tải lên / Thay đổi ảnh đại diện ──────────────────────────
                $activeTab = 'avatar';
                $avatarFile = $_FILES['avatar'] ?? null;
                $uploadResult = self::processAvatarUpload($avatarFile);

                if (!$uploadResult['ok']) {
                    $errors[] = $uploadResult['error'];
                } else {
                    $newRelPath = $uploadResult['path'];
                    $oldPath = $user['avatar_path'] ?? $user['avatar'] ?? null;

                    // Lưu vào CSDL
                    User::updateAvatar($userId, $newRelPath);

                    // Xóa file ảnh cũ nếu có
                    if ($oldPath && $oldPath !== $newRelPath) {
                        self::deleteAvatarFile($oldPath);
                    }

                    // Đồng bộ session
                    $_SESSION['avatar_path'] = $newRelPath;
                    $_SESSION['avatar']      = $newRelPath;

                    AuthController::setFlash('success', 'Cập nhật ảnh đại diện thành công.');
                    AuthController::go('/profile?tab=avatar');
                }
            } elseif ($action === 'delete_avatar') {
                // ── Xóa ảnh đại diện ──────────────────────────────────────────
                $activeTab = 'avatar';
                $oldPath = $user['avatar_path'] ?? $user['avatar'] ?? null;

                if ($oldPath) {
                    self::deleteAvatarFile($oldPath);
                }

                User::updateAvatar($userId, null);
                $_SESSION['avatar_path'] = null;
                $_SESSION['avatar']      = null;

                AuthController::setFlash('success', 'Đã xóa ảnh đại diện thành công.');
                AuthController::go('/profile?tab=avatar');
            } elseif ($action === 'change_password') {
                // ── Đổi mật khẩu ──────────────────────────────────────────────
                $activeTab = 'password';
                $currentPw = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
                $newPw     = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
                $confirmPw = is_string($_POST['new_password_confirm'] ?? null) ? $_POST['new_password_confirm'] : '';

                if ($currentPw === '') {
                    $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
                } elseif (!password_verify($currentPw, $user['password'] ?? '')) {
                    $errors[] = 'Mật khẩu hiện tại không đúng.';
                }

                $errors = array_merge($errors, self::checkPassword($newPw, $confirmPw, true));

                if (!$errors) {
                    User::update($userId, [
                        'full_name' => $user['full_name'],
                        'email'     => $user['email'],
                        'phone'     => $user['phone'] ?? '',
                        'password'  => $newPw,
                    ]);
                    AuthController::setFlash('success', 'Đổi mật khẩu thành công.');
                    AuthController::go('/profile?tab=password');
                }
            } else {
                // ── Cập nhật thông tin cá nhân (mặc định) ─────────────────────
                $activeTab = 'info';
                $fullName = is_string($_POST['full_name'] ?? null) ? trim($_POST['full_name']) : '';
                $phone    = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';

                if ($fullName === '' || mb_strlen($fullName) > 100) {
                    $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
                }
                if ($phone !== '' && !preg_match('/^[0-9+\-\s().]{8,20}$/', $phone)) {
                    $errors[] = 'Số điện thoại không hợp lệ.';
                }

                if (!$errors) {
                    User::updateProfile($userId, ['full_name' => $fullName, 'phone' => $phone]);
                    $_SESSION['full_name'] = $fullName;
                    AuthController::setFlash('success', 'Cập nhật hồ sơ thành công.');
                    AuthController::go('/profile?tab=info');
                }

                $user['full_name'] = $fullName;
                $user['phone']     = $phone;
            }
        }

        unset($user['password']);

        $this->respond('users/profile', [
            'user'      => $user,
            'roles'     => User::getRoles($userId),
            'errors'    => $errors,
            'activeTab' => $activeTab,
            'success'   => AuthController::pullFlash('success'),
            'error'     => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    /**
     * Kiểm tra và xử lý tải lên ảnh đại diện:
     * - Định dạng cho phép: JPG, PNG, WebP
     * - Dung lượng tối đa: 2 MB
     * - Kiểm tra cả MIME thực tế và nội dung cấu trúc ảnh
     * - Đặt tên ngẫu nhiên, lưu vào public/uploads/avatars/
     *
     * @param array|null $file $_FILES['avatar']
     * @return array{ok: bool, path: ?string, error: ?string}
     */
    public static function processAvatarUpload(?array $file): array
    {
        if ($file === null || !isset($file['error'])) {
            return ['ok' => false, 'error' => 'Không tìm thấy dữ liệu tệp tải lên.', 'path' => null];
        }

        $err = (int)$file['error'];
        if ($err === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Vui lòng chọn một tệp hình ảnh để tải lên.', 'path' => null];
        }

        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'Dung lượng ảnh vượt quá giới hạn 2 MB cho phép.', 'path' => null];
        }

        if ($err !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Quá trình tải tệp lên gặp sự cố (mã lỗi: ' . $err . ').', 'path' => null];
        }

        $maxBytes = 2 * 1024 * 1024; // 2 MB
        $size = (int)($file['size'] ?? 0);
        if ($size > $maxBytes) {
            return ['ok' => false, 'error' => 'Dung lượng ảnh vượt quá giới hạn 2 MB cho phép.', 'path' => null];
        }

        if ($size <= 0) {
            return ['ok' => false, 'error' => 'Tệp ảnh tải lên bị rỗng.', 'path' => null];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            return ['ok' => false, 'error' => 'Tệp tạm không tồn tại.', 'path' => null];
        }

        if (!is_uploaded_file($tmpPath) && php_sapi_name() !== 'cli') {
            return ['ok' => false, 'error' => 'Tệp tải lên không hợp lệ hoặc không an toàn.', 'path' => null];
        }

        // 1. Kiểm tra MIME thực tế bằng Fileinfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$realMime])) {
            return [
                'ok'    => false,
                'error' => 'Định dạng tệp không được hỗ trợ. Vui lòng chỉ tải ảnh JPG, PNG hoặc WebP.',
                'path'  => null,
            ];
        }

        // 2. Kiểm tra tính toàn vẹn và nội dung ảnh bằng getimagesize
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            return ['ok' => false, 'error' => 'Tệp bị lỗi hoặc nội dung không phải là hình ảnh hợp lệ.', 'path' => null];
        }

        $imageType = (int)($imageInfo[2] ?? 0);
        $validImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (defined('IMAGETYPE_WEBP')) {
            $validImageTypes[] = IMAGETYPE_WEBP;
        }

        if (!in_array($imageType, $validImageTypes, true)) {
            return ['ok' => false, 'error' => 'Cấu trúc tệp không khớp với định dạng ảnh cho phép.', 'path' => null];
        }

        // 3. Đặt tên file ngẫu nhiên an toàn (UUID / random_bytes + timestamp)
        $ext = $allowedMimes[$realMime];
        $randomFileName = 'avatar_' . bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;

        // 4. Đường dẫn thư mục upload chuyên dụng
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                return ['ok' => false, 'error' => 'Không thể tạo thư mục lưu trữ ảnh trên máy chủ.', 'path' => null];
            }
        }

        $destination = $uploadDir . DIRECTORY_SEPARATOR . $randomFileName;

        $saved = false;
        if (is_uploaded_file($tmpPath)) {
            $saved = move_uploaded_file($tmpPath, $destination);
        } else {
            // Hỗ trợ kiểm thử tự động CLI
            $saved = @copy($tmpPath, $destination);
        }

        if (!$saved) {
            return ['ok' => false, 'error' => 'Không thể lưu tệp ảnh vào thư mục máy chủ.', 'path' => null];
        }

        // Đảm bảo quyền đọc an toàn cho file vừa tải
        @chmod($destination, 0644);

        $relativePath = 'uploads/avatars/' . $randomFileName;

        return ['ok' => true, 'path' => $relativePath, 'error' => null];
    }

    /**
     * Xóa file ảnh đại diện an toàn trên đĩa (chống Path Traversal)
     */
    public static function deleteAvatarFile(?string $relativePath): void
    {
        if (empty($relativePath)) {
            return;
        }

        $cleanRel = ltrim(str_replace('\\', '/', $relativePath), '/');
        // Bắt buộc phải bắt đầu bằng uploads/avatars/
        if (!str_starts_with($cleanRel, 'uploads/avatars/')) {
            return;
        }

        $publicDir = realpath(dirname(__DIR__, 2) . '/public');
        if (!$publicDir) {
            return;
        }

        $fullPath = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);
        $realFile = realpath($fullPath);
        $realUploadDir = realpath($publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars');

        if ($realFile && $realUploadDir && str_starts_with($realFile, $realUploadDir)) {
            $baseName = basename($realFile);
            if (!in_array($baseName, ['index.html', '.gitkeep', '.htaccess'], true) && is_file($realFile)) {
                @unlink($realFile);
            }
        }
    }

    /**
     * POST /users/roles - Gán vai trò cho người dùng (chỉ admin)
     * Tham số: user_id, role_ids[]
     */
    public function assignRoles(): void
    {
        AuthController::requireRole('admin');
        $this->guardPost('/users');

        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        if ($userId === false || $userId === null || User::findById($userId) === null) {
            AuthController::setFlash('error', 'Người dùng không tồn tại.');
            AuthController::go('/users');
        }

        $rawRoleIds = $_POST['role_ids'] ?? [];
        if (!is_array($rawRoleIds)) {
            AuthController::setFlash('error', 'Dữ liệu vai trò không hợp lệ.');
            AuthController::go('/users');
        }

        $validIds = array_map(fn($r) => (int)$r['id'], Role::all());
        $roleIds  = [];
        foreach ($rawRoleIds as $raw) {
            $id = filter_var($raw, FILTER_VALIDATE_INT);
            if ($id === false || !in_array($id, $validIds, true)) {
                AuthController::setFlash('error', 'Có vai trò không hợp lệ.');
                AuthController::go('/users');
            }
            $roleIds[] = $id;
        }

        // Không cho admin tự gỡ quyền admin của chính mình (tránh khóa mất quyền quản trị)
        if ($userId === (int)$_SESSION['user_id']) {
            $adminRole = Role::findByName('admin');
            if ($adminRole === null || !in_array((int)$adminRole['id'], $roleIds, true)) {
                AuthController::setFlash('error', 'Bạn không thể tự gỡ vai trò Quản trị viên của chính mình.');
                AuthController::go('/users');
            }
        }

        User::assignRoles($userId, $roleIds);

        if ($userId === (int)$_SESSION['user_id']) {
            $_SESSION['roles'] = User::getRoles($userId);
        }

        AuthController::setFlash('success', 'Đã cập nhật vai trò cho người dùng.');
        AuthController::go('/users');
    }

    // ------------------------------------------------------------------
    // Kiểm tra dữ liệu & quy tắc nghiệp vụ (static để dễ kiểm thử)
    // ------------------------------------------------------------------

    /**
     * Kiểm tra dữ liệu form thêm tài khoản.
     * Vai trò (role_ids) là tùy chọn; password_confirm chỉ kiểm tra nếu form có gửi lên.
     *
     * @param int[] $validRoleIds danh sách ID vai trò hợp lệ trong hệ thống
     * @return array ['errors' => string[], 'data' => array dữ liệu đã làm sạch]
     */
    public static function validateNewUser(array $input, array $validRoleIds): array
    {
        $errors   = [];
        $username = self::text($input, 'username');
        $email    = self::text($input, 'email');
        $fullName = self::text($input, 'full_name');
        $phone    = self::text($input, 'phone');
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirm  = array_key_exists('password_confirm', $input)
            ? (is_string($input['password_confirm']) ? $input['password_confirm'] : '')
            : null;

        if (!preg_match('/^[A-Za-z0-9_.\-]{3,50}$/', $username)) {
            $errors[] = 'Tên đăng nhập gồm 3-50 ký tự: chữ, số, dấu chấm, gạch dưới hoặc gạch ngang.';
        } elseif (User::existsUsername($username)) {
            $errors[] = 'Tên đăng nhập đã tồn tại.';
        }

        $errors = array_merge($errors, self::checkEmail($email, null));
        $errors = array_merge($errors, self::checkPassword($password, $confirm, true));
        $errors = array_merge($errors, self::checkNameAndPhone($fullName, $phone));

        $status = filter_var($input['status'] ?? 1, FILTER_VALIDATE_INT);
        if ($status !== 0 && $status !== 1) {
            $errors[] = 'Trạng thái tài khoản không hợp lệ.';
        }

        $roleIds  = [];
        $rawRoles = $input['role_ids'] ?? [];
        if (!is_array($rawRoles)) {
            $errors[] = 'Dữ liệu vai trò không hợp lệ.';
        } else {
            foreach ($rawRoles as $raw) {
                $id = filter_var($raw, FILTER_VALIDATE_INT);
                if ($id === false || !in_array($id, $validRoleIds, true)) {
                    $errors[] = 'Có vai trò không hợp lệ.';
                    break;
                }
                $roleIds[] = $id;
            }
        }

        return [
            'errors' => $errors,
            'data'   => [
                'username'  => $username,
                'email'     => $email,
                'password'  => $password,
                'full_name' => $fullName,
                'phone'     => $phone,
                'status'    => $status === 0 ? 0 : 1,
                'role_ids'  => array_values(array_unique($roleIds)),
            ],
        ];
    }

    /**
     * Kiểm tra dữ liệu form sửa tài khoản (password để trống = giữ nguyên mật khẩu cũ).
     * Nếu form có gửi `status` thì kiểm tra 0/1 và chặn admin tự khóa chính mình.
     *
     * @param int $actorId ID admin đang thao tác
     * @return array ['errors' => string[], 'data' => array dữ liệu đã làm sạch]
     */
    public static function validateUserUpdate(int $id, array $input, int $actorId = 0): array
    {
        $email    = self::text($input, 'email');
        $fullName = self::text($input, 'full_name');
        $phone    = self::text($input, 'phone');
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirm  = array_key_exists('password_confirm', $input)
            ? (is_string($input['password_confirm']) ? $input['password_confirm'] : '')
            : null;

        $errors = array_merge(
            self::checkEmail($email, $id),
            self::checkPassword($password, $confirm, false),
            self::checkNameAndPhone($fullName, $phone)
        );

        $status = null;
        if (isset($input['status']) && $input['status'] !== '') {
            $status = filter_var($input['status'], FILTER_VALIDATE_INT);
            if ($status !== 0 && $status !== 1) {
                $errors[] = 'Trạng thái tài khoản không hợp lệ.';
                $status = null;
            } elseif (!self::canChangeStatus($actorId, $id, $status)) {
                $errors[] = 'Bạn không thể tự khóa tài khoản của chính mình.';
                $status = null;
            }
        }

        return [
            'errors' => $errors,
            'data'   => [
                'email'     => $email,
                'full_name' => $fullName,
                'phone'     => $phone,
                'password'  => $password,
                'status'    => $status,
            ],
        ];
    }

    /**
     * Quy tắc khóa tài khoản: admin không được tự khóa chính mình (mở khóa thì luôn được).
     */
    public static function canChangeStatus(int $actorId, int $targetId, int $newStatus): bool
    {
        return !($actorId === $targetId && $newStatus !== 1);
    }

    // ------------------------------------------------------------------
    // Hàm hỗ trợ nội bộ
    // ------------------------------------------------------------------

    private static function text(array $input, string $key): string
    {
        return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    }

    private static function checkEmail(string $email, ?int $excludeId): array
    {
        if ($email === '' || mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['Email không hợp lệ.'];
        }
        if (User::existsEmail($email, $excludeId)) {
            return ['Email đã được sử dụng.'];
        }

        return [];
    }

    private static function checkPassword(string $password, ?string $confirm, bool $required): array
    {
        if ($password === '' && !$required) {
            return [];
        }

        $errors = [];
        if (strlen($password) < 6 || strlen($password) > 72) {
            $errors[] = 'Mật khẩu phải có từ 6 đến 72 ký tự.';
        }
        if ($confirm !== null && $password !== $confirm) {
            $errors[] = 'Mật khẩu xác nhận không khớp.';
        }

        return $errors;
    }

    private static function checkNameAndPhone(string $fullName, string $phone): array
    {
        $errors = [];
        if ($fullName === '' || mb_strlen($fullName) > 100) {
            $errors[] = 'Họ tên không được để trống và tối đa 100 ký tự.';
        }
        if ($phone !== '' && !preg_match('/^[0-9+\-\s().]{8,20}$/', $phone)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        }

        return $errors;
    }

    /** Dữ liệu người dùng vừa nhập để hiển thị lại trên form (KHÔNG gồm mật khẩu) */
    private static function oldInput(array $input): array
    {
        $roleIds = [];
        if (is_array($input['role_ids'] ?? null)) {
            foreach ($input['role_ids'] as $raw) {
                $id = filter_var($raw, FILTER_VALIDATE_INT);
                if ($id !== false) {
                    $roleIds[] = $id;
                }
            }
        }

        return [
            'username'  => self::text($input, 'username'),
            'email'     => self::text($input, 'email'),
            'full_name' => self::text($input, 'full_name'),
            'phone'     => self::text($input, 'phone'),
            'status'    => self::text($input, 'status'),
            'role_ids'  => $roleIds,
        ];
    }

    /** Yêu cầu request là POST và đúng CSRF, nếu không thì quay lại trang chỉ định */
    private function guardPost(string $backPath): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go($backPath);
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            AuthController::go($backPath);
        }
    }

    /** Tìm user theo id lấy từ request; không có thì báo lỗi và quay về danh sách */
    private function findUserFromRequest(mixed $rawId): array
    {
        $id   = filter_var($rawId, FILTER_VALIDATE_INT);
        $user = ($id !== false && $id !== null) ? User::findById($id) : null;

        if ($user === null) {
            AuthController::setFlash('error', 'Người dùng không tồn tại.');
            AuthController::go('/users');
        }

        return $user;
    }

    private function renderCreateForm(array $errors, array $old): void
    {
        $this->respond('users/create', [
            'user'      => [],
            'roles'     => Role::all(),
            'userRoles' => [],
            'errors'    => $errors,
            'old'       => $old,
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    private function renderEditForm(array $user, array $errors, array $old): void
    {
        $id = (int)$user['id'];
        unset($user['password']); // không bao giờ đưa hash mật khẩu ra View

        $this->respond('users/edit', [
            'user'      => $user,
            'roles'     => Role::all(),
            'userRoles' => User::getRoles($id),
            'errors'    => $errors,
            'old'       => $old,
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    /**
     * Render View nếu đã có; nếu View chưa được làm thì trả JSON để vẫn kiểm tra được dữ liệu.
     */
    private function respond(string $view, array $data): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($viewPath)) {
            $this->render($view, $data);
            return;
        }

        unset($data['csrfToken']);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
