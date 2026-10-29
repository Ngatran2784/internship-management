<?php
/**
 * AUTOMATED TESTING SCRIPT - SPRINT 1 CORE FRAMEWORK
 * Chạy bằng lệnh: C:\xampp\php\php.exe tests/test_core.php
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/public/index.php'; // Đã bao gồm autoloader

echo "\n======================================================\n";
echo "   KIỂM THỬ HỆ THỐNG TỰ ĐỘNG - SPRINT 1 (NHÓM TRƯỞNG)\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "[ PASS ] - " . $description . "\n";
        $passCount++;
    } else {
        echo "[ FAIL ] - " . $description . "\n";
        $failCount++;
    }
}

// 1. Kiểm tra Autoload các lớp Core
assertTest("Autoload App\Core\Database tồn tại", class_exists('App\Core\Database'));
assertTest("Autoload App\Core\Router tồn tại", class_exists('App\Core\Router'));
assertTest("Autoload App\Core\Controller tồn tại", class_exists('App\Core\Controller'));

// 2. Kiểm tra Autoload các lớp khung của TV2
assertTest("Autoload App\Controllers\AuthController tồn tại", class_exists('App\Controllers\AuthController'));
assertTest("Autoload App\Controllers\UserController tồn tại", class_exists('App\Controllers\UserController'));
assertTest("Autoload App\Models\User tồn tại", class_exists('App\Models\User'));
assertTest("Autoload App\Models\Role tồn tại", class_exists('App\Models\Role'));

// 3. Kiểm tra Base Controller Escape chống XSS
$malicious = "<script>alert('XSS')</script>";
$escaped = \App\Core\Controller::escape($malicious);
assertTest("Controller::escape phòng chống tấn công XSS", $escaped === "&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;");

// 4. Kiểm tra Router GET registration
$testRouter = new \App\Core\Router();
$testRouter->get('/ping', function () {
    return 'pong';
});
$reflection = new ReflectionClass($testRouter);
$routesProp = $reflection->getProperty('routes');
$routesProp->setAccessible(true);
$routes = $routesProp->getValue($testRouter);
assertTest("Router đăng ký thành công route GET /ping", isset($routes['GET']['/ping']));

// 5. Kiểm tra file cấu hình mẫu và .gitignore
assertTest("Tệp cấu hình mẫu config/database.example.php tồn tại", file_exists($projectRoot . '/config/database.example.php'));
assertTest("Tệp .gitignore tồn tại", file_exists($projectRoot . '/.gitignore'));
$gitignoreContent = file_get_contents($projectRoot . '/.gitignore');
assertTest("Tệp .gitignore đã loại trừ config/database.php", strpos($gitignoreContent, 'config/database.php') !== false);

// 6. Kiểm tra các tệp khung View của TV3
assertTest("Tệp view layouts/header.php tồn tại", file_exists($projectRoot . '/app/Views/layouts/header.php'));
assertTest("Tệp view layouts/sidebar.php tồn tại", file_exists($projectRoot . '/app/Views/layouts/sidebar.php'));
assertTest("Tệp view layouts/footer.php tồn tại", file_exists($projectRoot . '/app/Views/layouts/footer.php'));
assertTest("Tệp view auth/login.php tồn tại", file_exists($projectRoot . '/app/Views/auth/login.php'));
assertTest("Tệp view dashboard/index.php tồn tại", file_exists($projectRoot . '/app/Views/dashboard/index.php'));

// 7. Kiểm tra tệp DDL database/schema.sql
assertTest("Tệp DDL database/schema.sql tồn tại", file_exists($projectRoot . '/database/schema.sql'));
$schemaSql = file_get_contents($projectRoot . '/database/schema.sql');
assertTest("Schema chứa bảng roles, users, user_roles với InnoDB", 
    strpos($schemaSql, 'CREATE TABLE IF NOT EXISTS `roles`') !== false &&
    strpos($schemaSql, 'CREATE TABLE IF NOT EXISTS `users`') !== false &&
    strpos($schemaSql, 'CREATE TABLE IF NOT EXISTS `user_roles`') !== false &&
    strpos($schemaSql, 'ENGINE=InnoDB') !== false
);

// 8. Kiểm tra an toàn cho dự án Laravel cũ
assertTest("Dự án Laravel cũ tại C:\\xampp\\htdocs\\internship-management nguyên vẹn", file_exists('C:\xampp\htdocs\internship-management'));

echo "\n------------------------------------------------------\n";
echo "KẾT QUẢ: {$passCount} KIỂM TRA ĐẠT (PASS), {$failCount} THẤT BẠI (FAIL).\n";
echo "======================================================\n\n";
