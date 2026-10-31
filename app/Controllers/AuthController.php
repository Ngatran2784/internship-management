<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * AuthController - Xác thực người dùng (Login, Logout, Session, CSRF, kiểm tra quyền)
 *
 * Các hàm static (startSession, check, hasRole, requireLogin, requireRole, csrfToken,
 * verifyCsrf...) được UserController và route /dashboard dùng lại.
 */
class AuthController extends Controller
{
    /** Hash Bcrypt giả, dùng để thời gian phản hồi không lộ việc tài khoản có tồn tại hay không */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    private const MSG_INVALID = 'Tên đăng nhập hoặc mật khẩu không chính xác.';

    // ------------------------------------------------------------------
    // Các route (được Router gọi)
    // ------------------------------------------------------------------

    /**
     * GET /login - Hiển thị form đăng nhập
     */
    public function showLoginForm(): void
    {
        self::startSession();

        if (self::check()) {
            self::go('/dashboard');
        }

        $this->render('auth/login', [
            'error'       => self::pullFlash('error'),
            'oldUsername' => self::pullFlash('old_username'),
            'csrfToken'   => self::csrfToken(),
        ]);
    }

    /**
     * POST /login - Xử lý đăng nhập
     */
    public function login(): void
    {
        self::startSession();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            self::go('/login');
        }

        if (!self::verifyCsrf()) {
            self::setFlash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
            self::go('/login');
        }

        $identifier = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
        $password   = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        try {
            $result = self::attempt($identifier, $password);
        } catch (\Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $result = ['ok' => false, 'error' => 'Hệ thống đang gặp sự cố, vui lòng thử lại sau.'];
        }

        if (!$result['ok']) {
            self::setFlash('error', $result['error']);
            self::setFlash('old_username', trim($identifier));
            self::go('/login');
        }

        self::signIn($result['user']);
        self::go('/dashboard');
    }

    /**
     * POST|GET /logout - Đăng xuất, xóa toàn bộ session
     */
    public function logout(): void
    {
        self::startSession();

        // Nếu gửi bằng POST thì phải đúng CSRF (GET /logout vẫn cho phép để link trên sidebar hoạt động)
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !self::verifyCsrf()) {
            self::go('/dashboard');
        }

        self::clearSession();
        self::go('/login');
    }

    // ------------------------------------------------------------------
    // Logic xác thực (tách riêng khỏi HTTP để dễ kiểm thử)
    // ------------------------------------------------------------------

    /**
     * Đối soát tài khoản + mật khẩu.
     *
     * @return array ['ok' => true, 'user' => array] hoặc ['ok' => false, 'error' => string]
     */
    public static function attempt(string $identifier, string $password): array
    {
        $identifier = trim($identifier);

        if ($identifier === '' || $password === '') {
            return ['ok' => false, 'error' => 'Vui lòng nhập tên đăng nhập/email và mật khẩu.'];
        }

        if (mb_strlen($identifier) > 100 || strlen($password) > 255) {
            return ['ok' => false, 'error' => self::MSG_INVALID];
        }

        $user = User::findByUsernameOrEmail($identifier);

        // Luôn gọi password_verify (kể cả khi không có user) để tránh lộ thông tin qua thời gian phản hồi
        $valid = password_verify($password, $user['password'] ?? self::DUMMY_HASH);

        if ($user === null || !$valid) {
            return ['ok' => false, 'error' => self::MSG_INVALID];
        }

        // Chỉ báo "bị khóa" sau khi đã đúng mật khẩu, tránh lộ danh sách tài khoản
        if ((int)$user['status'] !== 1) {
            return ['ok' => false, 'error' => 'Tài khoản đã bị khóa. Vui lòng liên hệ Quản trị viên.'];
        }

        return ['ok' => true, 'user' => $user];
    }

    /**
     * Ghi thông tin đăng nhập vào session (đổi session id để chống session fixation)
     */
    public static function signIn(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['full_name']  = $user['full_name'];
        $_SESSION['roles']      = User::getRoles((int)$user['id']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Xóa toàn bộ session và cookie session
     */
    public static function clearSession(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
        }
    }

    // ------------------------------------------------------------------
    // Session, quyền, CSRF, flash (dùng chung)
    // ------------------------------------------------------------------

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
            session_name('INTERNSHIP_SESSID'); // tránh trùng cookie với dự án khác trên localhost
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function check(): bool
    {
        self::startSession();

        return !empty($_SESSION['user_id']);
    }

    public static function roles(): array
    {
        self::startSession();

        return is_array($_SESSION['roles'] ?? null) ? $_SESSION['roles'] : [];
    }

    /** Người dùng hiện tại có ÍT NHẤT MỘT trong các vai trò được liệt kê không */
    public static function hasRole(string ...$roles): bool
    {
        return count(array_intersect($roles, self::roles())) > 0;
    }

    /** Bắt buộc đăng nhập, nếu chưa thì chuyển về /login */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::go('/login');
        }
    }

    /** Bắt buộc đăng nhập và có ít nhất một vai trò, nếu không trả 403 */
    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();

        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>403</title></head>'
               . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
               . '<h1>403</h1><p>Bạn không có quyền truy cập chức năng này.</p>'
               . '<a href="' . self::escape(self::url('/dashboard')) . '">Về trang chủ</a></body></html>';
            exit;
        }
    }

    public static function csrfToken(): string
    {
        self::startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Kiểm tra CSRF cho request POST.
     * - Nếu form gửi kèm trường ẩn `_csrf`: so khớp token trong session.
     * - Chấp nhận nếu Origin/Referer cùng host khi kiểm thử.
     */
    public static function verifyCsrf(): bool
    {
        self::startSession();

        $sent     = $_POST['_csrf'] ?? null;
        $expected = $_SESSION['csrf_token'] ?? '';

        if (is_string($sent) && $sent !== '') {
            return $expected !== '' && hash_equals($expected, $sent);
        }

        return self::isSameOrigin();
    }

    private static function isSameOrigin(): bool
    {
        $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        $host   = $_SERVER['HTTP_HOST'] ?? '';

        if ($source === '' || $host === '') {
            return false;
        }

        $parts = parse_url($source);
        if (empty($parts['host'])) {
            return false;
        }

        $authority = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        return strtolower($authority) === strtolower($host);
    }

    public static function setFlash(string $key, string $value): void
    {
        self::startSession();
        $_SESSION['flash'][$key] = $value;
    }

    /** Lấy thông báo flash rồi xóa luôn (chỉ hiển thị 1 lần) */
    public static function pullFlash(string $key): ?string
    {
        self::startSession();
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);

        return $value;
    }

    // ------------------------------------------------------------------
    // URL & chuyển hướng (tương thích XAMPP chạy trong thư mục con)
    // ------------------------------------------------------------------

    /** Ghép đường dẫn nội bộ với thư mục gốc public, ví dụ '/login' -> '/CNPM/internship-management/public/login' */
    public static function url(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        return $base . '/' . ltrim($path, '/');
    }

    public static function go(string $path): never
    {
        header('Location: ' . self::url($path));
        exit;
    }
}
