<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Role;
use App\Models\User;

/**
 * UserController - Danh sách người dùng, hồ sơ cá nhân, gán vai trò (phạm vi Sprint 1)
 */
class UserController extends Controller
{
    /**
     * GET /users - Danh sách người dùng kèm vai trò (chỉ admin)
     */
    public function index(): void
    {
        AuthController::requireRole('admin');

        $this->respond('users/index', [
            'users'     => User::all(),
            'roles'     => Role::all(),
            'success'   => AuthController::pullFlash('success'),
            'error'     => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET|POST /profile - Xem và cập nhật hồ sơ cá nhân của chính mình
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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!AuthController::verifyCsrf()) {
                AuthController::setFlash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
                AuthController::go('/profile');
            }

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
                AuthController::go('/profile');
            }

            // Giữ lại dữ liệu người dùng vừa nhập để hiển thị lại
            $user['full_name'] = $fullName;
            $user['phone']     = $phone;
        }

        unset($user['password']); // không bao giờ đưa hash mật khẩu ra View

        $this->respond('users/profile', [
            'user'      => $user,
            'roles'     => User::getRoles($userId),
            'errors'    => $errors,
            'success'   => AuthController::pullFlash('success'),
            'error'     => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /users/roles - Gán vai trò cho người dùng (chỉ admin)
     * Tham số: user_id, role_ids[]
     */
    public function assignRoles(): void
    {
        AuthController::requireRole('admin');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/users');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            AuthController::go('/users');
        }

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

    /**
     * Render View nếu đã có; nếu View chưa được làm (Sprint sau) thì trả JSON để vẫn kiểm tra được dữ liệu.
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
