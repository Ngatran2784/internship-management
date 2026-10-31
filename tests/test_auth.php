<?php
/**
 * KIỂM THỬ XÁC THỰC & NGƯỜI DÙNG - SPRINT 1
 * Chạy: C:\xampp\php\php.exe tests/test_auth.php
 *
 * Yêu cầu: MySQL đang chạy, đã import database/schema.sql, đã có config/database.php.
 * Toàn bộ dữ liệu test nằm trong 1 transaction và được ROLLBACK ở cuối => không làm bẩn CSDL.
 */

$root = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($root) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Core\Database;
use App\Models\Role;
use App\Models\User;

$pass = 0;
$fail = 0;

function t(string $desc, bool $cond): void
{
    global $pass, $fail;
    echo ($cond ? '[ PASS ] ' : '[ FAIL ] ') . $desc . "\n";
    $cond ? $pass++ : $fail++;
}

echo "\n==== KIỂM THỬ AUTH / USER / ROLE - SPRINT 1 ====\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "Không kết nối được CSDL: " . $e->getMessage() . "\n";
    echo "Hãy bật MySQL trong XAMPP, import database/schema.sql và tạo config/database.php.\n";
    exit(1);
}

$pdo->beginTransaction();

try {
    // --- Dữ liệu mẫu (chỉ tồn tại trong transaction) ---
    foreach (['admin' => 'Quản trị hệ thống', 'student' => 'Sinh viên'] as $name => $display) {
        if (Role::findByName($name) === null) {
            $pdo->prepare('INSERT INTO roles (name, display_name) VALUES (:n, :d)')
                ->execute(['n' => $name, 'd' => $display]);
        }
    }

    $suffix   = bin2hex(random_bytes(4));
    $username = "test_{$suffix}";
    $email    = "test_{$suffix}@example.com";
    $plain    = 'Mat-khau-test-123';

    // --- Role ---
    t('Role::all() trả về mảng, có ít nhất 2 vai trò', count(Role::all()) >= 2);
    t("Role::findByName('admin') tìm thấy", Role::findByName('admin') !== null);
    t('Role::findByName(tên không tồn tại) trả về null', Role::findByName('khong_ton_tai_xyz') === null);

    // --- User::create / find ---
    $id = User::create([
        'username' => $username, 'email' => $email, 'password' => $plain,
        'full_name' => 'Người Dùng Test', 'phone' => '0912345678',
    ]);
    t('User::create() trả về id > 0', $id > 0);

    $row = User::findById($id);
    t('User::findById() tìm thấy user vừa tạo', $row !== null && $row['username'] === $username);
    t('Mật khẩu được băm Bcrypt, không lưu dạng thô', $row['password'] !== $plain && str_starts_with($row['password'], '$2y$'));
    t('password_verify khớp với mật khẩu gốc', password_verify($plain, $row['password']));
    t('User::findById(id không tồn tại) trả về null', User::findById(999999999) === null);
    t('findByUsernameOrEmail theo username', User::findByUsernameOrEmail($username)['id'] == $id);
    t('findByUsernameOrEmail theo email', User::findByUsernameOrEmail($email)['id'] == $id);
    t('findByUsernameOrEmail(không tồn tại) trả về null', User::findByUsernameOrEmail('khong_co_ai') === null);
    t("Chuỗi SQL injection \"' OR '1'='1\" không trả về user nào", User::findByUsernameOrEmail("' OR '1'='1") === null);

    $dupThrown = false;
    try {
        User::create(['username' => $username, 'email' => "khac_{$suffix}@example.com", 'password' => 'x', 'full_name' => 'Trùng']);
    } catch (PDOException $e) {
        $dupThrown = true;
    }
    t('Tạo user trùng username bị CSDL từ chối', $dupThrown);

    $missingThrown = false;
    try {
        User::create(['username' => 'a']);
    } catch (InvalidArgumentException $e) {
        $missingThrown = true;
    }
    t('Tạo user thiếu trường bắt buộc ném InvalidArgumentException', $missingThrown);

    // --- Vai trò ---
    t('User mới chưa có vai trò', User::getRoles($id) === []);
    $adminId = (int)Role::findByName('admin')['id'];
    $studentId = (int)Role::findByName('student')['id'];
    User::assignRoles($id, [$adminId, $studentId]);
    t('assignRoles + getRoles trả về đúng 2 vai trò', User::getRoles($id) === ['admin', 'student']);
    User::assignRoles($id, [$studentId]);
    t('assignRoles thay thế tập vai trò cũ', User::getRoles($id) === ['student']);

    $all = array_values(array_filter(User::all(), fn($u) => (int)$u['id'] === $id));
    t('User::all() có user mới kèm vai trò', count($all) === 1 && $all[0]['roles'] === ['student']);
    t('User::all() KHÔNG trả về cột password', !array_key_exists('password', $all[0]));

    // --- Cập nhật hồ sơ ---
    User::updateProfile($id, ['full_name' => 'Tên Mới', 'phone' => '']);
    $row = User::findById($id);
    t('updateProfile đổi họ tên, phone rỗng thành NULL', $row['full_name'] === 'Tên Mới' && $row['phone'] === null);

    // --- Đăng nhập ---
    $r = AuthController::attempt($username, $plain);
    t('Đăng nhập đúng (username) thành công', $r['ok'] === true && (int)$r['user']['id'] === $id);
    t('Đăng nhập đúng (email) thành công', AuthController::attempt($email, $plain)['ok'] === true);

    $wrongPw   = AuthController::attempt($username, 'sai-mat-khau');
    $unknown   = AuthController::attempt('khong_co_ai', $plain);
    t('Sai mật khẩu bị từ chối', $wrongPw['ok'] === false);
    t('Tài khoản không tồn tại bị từ chối', $unknown['ok'] === false);
    t('Thông báo sai mật khẩu và sai tài khoản giống nhau (không lộ user tồn tại)', $wrongPw['error'] === $unknown['error']);
    t('Bỏ trống thông tin bị từ chối', AuthController::attempt('', '')['ok'] === false);

    $pdo->prepare('UPDATE users SET status = 0 WHERE id = :id')->execute(['id' => $id]);
    $locked = AuthController::attempt($username, $plain);
    t('Tài khoản bị khóa không đăng nhập được', $locked['ok'] === false && str_contains($locked['error'], 'khóa'));
    $lockedWrong = AuthController::attempt($username, 'sai');
    t('Tài khoản khóa + sai mật khẩu: không tiết lộ trạng thái khóa', $lockedWrong['error'] === $wrongPw['error']);

    // --- Session, quyền, đăng xuất ---
    $_SESSION = [];
    t('Chưa đăng nhập: check() = false', AuthController::check() === false);

    AuthController::signIn(User::findById($id));
    t('signIn ghi user_id vào session', ($_SESSION['user_id'] ?? 0) === $id);
    t('Đã đăng nhập: check() = true', AuthController::check() === true);
    t('Session lưu đúng vai trò', ($_SESSION['roles'] ?? []) === ['student']);
    t('Sinh viên KHÔNG có quyền admin (truy cập trái quyền bị chặn)', AuthController::hasRole('admin') === false);
    t('Sinh viên có vai trò student', AuthController::hasRole('student') === true);
    t('hasRole nhiều vai trò: chỉ cần khớp một', AuthController::hasRole('admin', 'student') === true);

    AuthController::clearSession();
    t('Đăng xuất xóa sạch session', $_SESSION === []);
    t('Sau đăng xuất: check() = false', AuthController::check() === false);

    // --- CSRF ---
    $_SESSION = [];
    $token = AuthController::csrfToken();
    t('csrfToken() sinh chuỗi 64 ký tự hex', strlen($token) === 64 && ctype_xdigit($token));
    t('csrfToken() ổn định trong cùng session', AuthController::csrfToken() === $token);
    $_POST = ['_csrf' => $token];
    t('verifyCsrf chấp nhận token đúng', AuthController::verifyCsrf() === true);
    $_POST = ['_csrf' => 'token-gia'];
    t('verifyCsrf từ chối token sai', AuthController::verifyCsrf() === false);
    $_POST = [];
    unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
    t('Không token, không Origin/Referer: bị từ chối', AuthController::verifyCsrf() === false);
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
    t('Không token nhưng Origin cùng host: chấp nhận (tạm thời)', AuthController::verifyCsrf() === true);
    $_SERVER['HTTP_ORIGIN'] = 'http://evil.example';
    t('Không token, Origin khác host: bị từ chối', AuthController::verifyCsrf() === false);
} catch (Throwable $e) {
    echo "\n[ LỖI ] " . get_class($e) . ': ' . $e->getMessage() . "\n";
    $fail++;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "\n----------------------------------------------\n";
echo "KẾT QUẢ: {$pass} PASS, {$fail} FAIL.\n";
echo "(Dữ liệu test đã được rollback, CSDL không bị thay đổi)\n\n";
exit($fail > 0 ? 1 : 0);
