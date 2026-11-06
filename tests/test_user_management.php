<?php
/**
 * KIỂM THỬ QUẢN LÝ NGƯỜI DÙNG - SPRINT 2 (Trúc)
 * Chạy: C:\xampp\php\php.exe tests/test_user_management.php
 *
 * Yêu cầu: MySQL đang chạy, đã import database/schema.sql.
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
use App\Controllers\UserController;
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

function authHas(string $method): bool
{
    return method_exists(AuthController::class, $method);
}

function hasError(array $result, string $needle): bool
{
    foreach ($result['errors'] as $e) {
        if (str_contains($e, $needle)) {
            return true;
        }
    }
    return false;
}

echo "\n==== KIỂM THỬ QUẢN LÝ NGƯỜI DÙNG - SPRINT 2 (TRÚC) ====\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "Không kết nối được CSDL: " . $e->getMessage() . "\n";
    exit(1);
}

$pdo->beginTransaction();

try {
    foreach (['admin' => 'Quản trị hệ thống', 'student' => 'Sinh viên', 'lecturer' => 'Giảng viên'] as $name => $display) {
        if (Role::findByName($name) === null) {
            $pdo->prepare('INSERT INTO roles (name, display_name) VALUES (:n, :d)')
                ->execute(['n' => $name, 'd' => $display]);
        }
    }
    $adminRoleId    = (int)Role::findByName('admin')['id'];
    $studentRoleId  = (int)Role::findByName('student')['id'];
    $lecturerRoleId = (int)Role::findByName('lecturer')['id'];
    $validRoleIds   = array_map(fn($r) => (int)$r['id'], Role::all());

    $sfx   = bin2hex(random_bytes(4));
    $uName = "sp2_{$sfx}";
    $uMail = "sp2_{$sfx}@example.com";
    $pw    = 'Matkhau123';

    // --- Phân quyền & các hàm tồn tại ---
    foreach (['create', 'store', 'edit', 'update', 'updateStatus'] as $m) {
        t("UserController::{$m}() tồn tại và public", method_exists(UserController::class, $m)
            && (new ReflectionMethod(UserController::class, $m))->isPublic());
    }

    if (authHas('hasRole')) {
        $_SESSION = ['user_id' => 1, 'roles' => ['student']];
        t('Sinh viên KHÔNG được quản lý tài khoản (hasRole admin = false)', AuthController::hasRole('admin') === false);
        $_SESSION = ['user_id' => 1, 'roles' => ['lecturer']];
        t('Giảng viên KHÔNG được quản lý tài khoản', AuthController::hasRole('admin') === false);
        $_SESSION = ['user_id' => 1, 'roles' => ['admin']];
        t('Admin được quản lý tài khoản', AuthController::hasRole('admin') === true);
    }

    // --- existsUsername / existsEmail ---
    $id = User::createWithRoles(
        ['username' => $uName, 'email' => $uMail, 'password' => $pw, 'full_name' => 'Người Test Sprint 2'],
        [$studentRoleId]
    );
    t('createWithRoles trả về id > 0', $id > 0);
    t('createWithRoles gán đúng vai trò', User::getRoles($id) === ['student']);

    t('existsUsername: username đã có -> true', User::existsUsername($uName) === true);
    t('existsUsername: không phân biệt hoa thường', User::existsUsername(strtoupper($uName)) === true);
    t('existsUsername: username chưa có -> false', User::existsUsername("khong_co_{$sfx}") === false);
    t('existsUsername: bỏ qua chính user đang sửa (excludeId)', User::existsUsername($uName, $id) === false);
    t('existsEmail: email đã có -> true', User::existsEmail($uMail) === true);
    t('existsEmail: email chưa có -> false', User::existsEmail("khong_co_{$sfx}@example.com") === false);
    t('existsEmail: bỏ qua chính user đang sửa (excludeId)', User::existsEmail($uMail, $id) === false);

    // --- Tạo trùng bị chặn ---
    $dup = false;
    try {
        User::createWithRoles(['username' => $uName, 'email' => "khac_{$sfx}@example.com", 'password' => $pw, 'full_name' => 'Trùng'], [$studentRoleId]);
    } catch (PDOException $e) {
        $dup = ((string)$e->getCode() === '23000');
    }
    t('Tạo trùng username bị CSDL chặn (SQLSTATE 23000)', $dup);

    // --- User::update ---
    $before = User::findById($id);
    User::update($id, ['full_name' => 'Tên Đã Sửa', 'email' => "moi_{$sfx}@example.com", 'phone' => '0987654321', 'password' => '']);
    $after = User::findById($id);
    t('update đổi họ tên, email, số điện thoại', $after['full_name'] === 'Tên Đã Sửa'
        && $after['email'] === "moi_{$sfx}@example.com" && $after['phone'] === '0987654321');
    t('update với mật khẩu rỗng giữ nguyên mật khẩu cũ', $after['password'] === $before['password']);
    t('update KHÔNG đổi username', $after['username'] === $uName);

    User::update($id, ['full_name' => 'Tên Đã Sửa', 'email' => "moi_{$sfx}@example.com", 'phone' => '', 'password' => 'MatKhauMoi456']);
    $after2 = User::findById($id);
    t('update có mật khẩu mới: được băm Bcrypt', str_starts_with($after2['password'], '$2y$') && $after2['password'] !== 'MatKhauMoi456');
    t('update có mật khẩu mới: khớp mật khẩu mới', password_verify('MatKhauMoi456', $after2['password']));
    t('update có mật khẩu mới: mật khẩu cũ không còn khớp', !password_verify($pw, $after2['password']));
    t('update với phone rỗng lưu NULL', $after2['phone'] === null);

    $missing = false;
    try {
        User::update($id, ['full_name' => '', 'email' => 'a@b.com']);
    } catch (InvalidArgumentException $e) {
        $missing = true;
    }
    t('update thiếu họ tên ném InvalidArgumentException', $missing);

    // --- updateStatus (khóa / mở khóa) ---
    User::updateStatus($id, 0);
    t('updateStatus(0): tài khoản bị khóa', (int)User::findById($id)['status'] === 0);
    if (authHas('attempt')) {
        $locked = AuthController::attempt($uName, 'MatKhauMoi456');
        t('Tài khoản bị khóa không đăng nhập được', $locked['ok'] === false);
    }
    User::updateStatus($id, 1);
    t('updateStatus(1): mở khóa thành công', (int)User::findById($id)['status'] === 1);
    if (authHas('attempt')) {
        t('Sau khi mở khóa đăng nhập lại được', AuthController::attempt($uName, 'MatKhauMoi456')['ok'] === true);
    }

    $badStatus = false;
    try {
        User::updateStatus($id, 2);
    } catch (InvalidArgumentException $e) {
        $badStatus = true;
    }
    t('updateStatus(2) ném InvalidArgumentException', $badStatus);

    // --- canChangeStatus: admin không tự khóa mình ---
    t('Admin KHÔNG được tự khóa chính mình', UserController::canChangeStatus(5, 5, 0) === false);
    t('Admin được khóa người khác', UserController::canChangeStatus(5, 6, 0) === true);
    t('Admin được mở khóa (kể cả của mình)', UserController::canChangeStatus(5, 5, 1) === true);

    // --- validateNewUser ---
    $good = [
        'username' => "moi_{$sfx}", 'email' => "moi2_{$sfx}@example.com", 'password' => 'Abcdef12', 'full_name' => 'Nguyễn Văn A', 'phone' => '0912345678',
        'status' => '1', 'role_ids' => [(string)$studentRoleId, (string)$lecturerRoleId],
    ];
    $r = UserController::validateNewUser($good, $validRoleIds);
    t('Dữ liệu hợp lệ: không có lỗi', $r['errors'] === []);
    t('Dữ liệu hợp lệ: role_ids được ép sang số nguyên', $r['data']['role_ids'] === [$studentRoleId, $lecturerRoleId]);
    $r = UserController::validateNewUser(array_merge($good, ['username' => 'a.b-c_9']), $validRoleIds);
    t('Username có chấm, gạch ngang, gạch dưới được chấp nhận', $r['errors'] === []);

    $r = UserController::validateNewUser(array_merge($good, ['username' => '']), $validRoleIds);
    t('Thiếu username bị từ chối', hasError($r, 'Tên đăng nhập'));
    $r = UserController::validateNewUser(array_merge($good, ['username' => 'ab']), $validRoleIds);
    t('Username quá ngắn bị từ chối', hasError($r, 'Tên đăng nhập'));
    $r = UserController::validateNewUser(array_merge($good, ['username' => 'co khoang trang']), $validRoleIds);
    t('Username có khoảng trắng/ký tự lạ bị từ chối', hasError($r, 'Tên đăng nhập'));
    $r = UserController::validateNewUser(array_merge($good, ['username' => $uName]), $validRoleIds);
    t('Username trùng bị từ chối', hasError($r, 'đã tồn tại'));
    $r = UserController::validateNewUser(array_merge($good, ['email' => 'khong-phai-email']), $validRoleIds);
    t('Email sai định dạng bị từ chối', hasError($r, 'Email không hợp lệ'));
    $r = UserController::validateNewUser(array_merge($good, ['email' => "moi_{$sfx}@example.com"]), $validRoleIds);
    t('Email trùng bị từ chối', hasError($r, 'Email đã được sử dụng'));
    $r = UserController::validateNewUser(array_merge($good, ['password' => 'Ab1']), $validRoleIds);
    t('Mật khẩu ngắn hơn 6 ký tự bị từ chối', hasError($r, 'Mật khẩu'));
    $r = UserController::validateNewUser(array_merge($good, ['password' => '']), $validRoleIds);
    t('Mật khẩu để trống khi tạo mới bị từ chối', hasError($r, 'Mật khẩu'));
    $r = UserController::validateNewUser(array_merge($good, ['password' => 'abc123']), $validRoleIds);
    t('Mật khẩu đúng 6 ký tự được chấp nhận', $r['errors'] === []);
    $r = UserController::validateNewUser(array_merge($good, ['password_confirm' => 'Khac12345']), $validRoleIds);
    t('Có gửi password_confirm mà không khớp thì bị từ chối', hasError($r, 'xác nhận'));
    $r = UserController::validateNewUser(array_merge($good, ['full_name' => '   ']), $validRoleIds);
    t('Họ tên trống bị từ chối', hasError($r, 'Họ tên'));
    $r = UserController::validateNewUser(array_merge($good, ['phone' => 'abc']), $validRoleIds);
    t('Số điện thoại sai bị từ chối', hasError($r, 'điện thoại'));
    $r = UserController::validateNewUser(array_merge($good, ['status' => '7']), $validRoleIds);
    t('Trạng thái ngoài 0/1 bị từ chối', hasError($r, 'Trạng thái'));
    $r = UserController::validateNewUser(array_merge($good, ['role_ids' => []]), $validRoleIds);
    t('Không chọn vai trò nào vẫn hợp lệ (vai trò là tùy chọn)', $r['errors'] === []);
    $noRoleKey = $good;
    unset($noRoleKey['role_ids']);
    $r = UserController::validateNewUser($noRoleKey, $validRoleIds);
    t('Form không gửi role_ids vẫn hợp lệ', $r['errors'] === [] && $r['data']['role_ids'] === []);
    $r = UserController::validateNewUser(array_merge($good, ['role_ids' => ['999999999']]), $validRoleIds);
    t('Vai trò không tồn tại bị từ chối', hasError($r, 'vai trò không hợp lệ'));
    $r = UserController::validateNewUser(array_merge($good, ['role_ids' => 'khong-phai-mang']), $validRoleIds);
    t('role_ids không phải mảng bị từ chối', $r['errors'] !== []);
    $r = UserController::validateNewUser(array_merge($good, ['username' => ['a']]), $validRoleIds);
    t('Dữ liệu dạng mảng giả mạo không gây lỗi PHP, chỉ báo sai', $r['errors'] !== []);

    // --- validateUserUpdate ---
    $upd = ['email' => "moi_{$sfx}@example.com", 'full_name' => 'Tên Khác', 'phone' => '', 'password' => ''];
    $r = UserController::validateUserUpdate($id, $upd, 999);
    t('Sửa: giữ nguyên email của chính mình không báo trùng', $r['errors'] === []);
    t('Sửa: không gửi status thì status = null (giữ nguyên)', $r['data']['status'] === null);

    User::create(['username' => "other_{$sfx}", 'email' => "other_{$sfx}@example.com", 'password' => $pw, 'full_name' => 'Người Khác']);
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['email' => "other_{$sfx}@example.com"]), 999);
    t('Sửa: đổi sang email của người khác bị từ chối', hasError($r, 'Email đã được sử dụng'));
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['password' => 'Moi12345']), 999);
    t('Sửa: mật khẩu mới hợp lệ được chấp nhận', $r['errors'] === [] && $r['data']['password'] === 'Moi12345');
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['password' => '123']), 999);
    t('Sửa: mật khẩu mới ngắn hơn 6 ký tự bị từ chối', hasError($r, 'Mật khẩu'));
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['email' => 'sai-email']), 999);
    t('Sửa: email sai định dạng bị từ chối', hasError($r, 'Email không hợp lệ'));
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['status' => '0']), 999);
    t('Sửa: admin khóa người khác qua form sửa được chấp nhận', $r['errors'] === [] && $r['data']['status'] === 0);
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['status' => '5']), 999);
    t('Sửa: status ngoài 0/1 bị từ chối', hasError($r, 'Trạng thái'));
    $r = UserController::validateUserUpdate($id, array_merge($upd, ['status' => '0']), $id);
    t('Sửa: admin KHÔNG được tự khóa chính mình qua form sửa', hasError($r, 'tự khóa'));

    // --- User::all() có bộ lọc ---
    User::assignRoles($id, [$lecturerRoleId]);
    $ids = fn(array $list) => array_map(fn($u) => (int)$u['id'], $list);
    t('all(): không lọc thì có user vừa tạo', in_array($id, $ids(User::all()), true));
    t('all(): lọc theo vai trò lecturer có user này', in_array($id, $ids(User::all('lecturer')), true));
    t('all(): lọc theo vai trò admin không có user này', !in_array($id, $ids(User::all('admin')), true));
    t('all(): tìm theo từ khóa username', in_array($id, $ids(User::all(null, $uName)), true));
    t('all(): từ khóa không khớp trả về rỗng', User::all(null, "khong_khop_{$sfx}_zzz") === []);
    t('all(): ký tự % trong từ khóa không bị hiểu là ký tự đại diện', User::all(null, '%') === []
        || !in_array($id, $ids(User::all(null, '%')), true));
    $row = array_values(array_filter(User::all(), fn($u) => (int)$u['id'] === $id))[0];
    t('all(): có roles_list dạng chuỗi', $row['roles_list'] === 'lecturer' && $row['roles'] === ['lecturer']);
    t('all(): không trả về cột password', !array_key_exists('password', $row));

    // --- CSRF ---
    if (authHas('csrfToken') && authHas('verifyCsrf')) {
        $_SESSION = [];
        $token = AuthController::csrfToken();
        $_POST = ['_csrf' => 'sai'];
        t('POST với CSRF token sai bị từ chối', AuthController::verifyCsrf() === false);
        $_POST = ['_csrf' => $token];
        t('POST với CSRF token đúng được chấp nhận', AuthController::verifyCsrf() === true);
    }
} catch (Throwable $e) {
    echo "\n[ LỖI ] " . get_class($e) . ': ' . $e->getMessage() . ' (dòng ' . $e->getLine() . ")\n";
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
