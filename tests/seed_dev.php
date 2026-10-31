<?php
/**
 * TẠO DỮ LIỆU MẪU ĐỂ ĐĂNG NHẬP THỬ (CHỈ DÙNG TRÊN MÁY CÁ NHÂN / DEV)
 * Chạy: C:\xampp\php\php.exe tests/seed_dev.php
 *
 * - Thêm 6 vai trò nếu chưa có.
 * - Tạo tài khoản: admin / Admin@123 (vai trò admin) và sv001 / Sv@12345 (vai trò student) nếu chưa có.
 * Có thể chạy nhiều lần, không tạo trùng.
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

use App\Core\Database;
use App\Models\Role;
use App\Models\User;

$pdo = Database::getConnection();

$roles = [
    'admin'           => ['Quản trị hệ thống', 'Toàn quyền quản trị hệ thống'],
    'training_dept'   => ['Phòng đào tạo', 'Quản lý chung đợt thực tập, đồ án'],
    'department_head' => ['Trưởng bộ môn', 'Phê duyệt đề tài, phân công'],
    'lecturer'        => ['Giảng viên', 'Hướng dẫn và phản biện'],
    'student'         => ['Sinh viên', 'Đăng ký đề tài, nộp báo cáo'],
    'enterprise'      => ['Doanh nghiệp', 'Người hướng dẫn tại doanh nghiệp'],
];

foreach ($roles as $name => [$display, $desc]) {
    if (Role::findByName($name) === null) {
        $pdo->prepare('INSERT INTO roles (name, display_name, description) VALUES (:n, :d, :s)')
            ->execute(['n' => $name, 'd' => $display, 's' => $desc]);
        echo "+ Thêm vai trò: {$name}\n";
    }
}

$accounts = [
    ['admin', 'admin@example.com', 'Admin@123', 'Quản trị viên', 'admin'],
    ['sv001', 'sv001@example.com', 'Sv@12345', 'Sinh viên Mẫu', 'student'],
];

foreach ($accounts as [$username, $email, $password, $fullName, $roleName]) {
    if (User::findByUsernameOrEmail($username) !== null) {
        echo "= Đã có tài khoản: {$username}\n";
        continue;
    }
    $id = User::create(compact('username', 'email', 'password') + ['full_name' => $fullName]);
    User::assignRoles($id, [(int)Role::findByName($roleName)['id']]);
    echo "+ Tạo tài khoản: {$username} / {$password} ({$roleName})\n";
}

echo "\nXong. Mở http://localhost/CNPM/internship-management/public/login để đăng nhập thử.\n";
