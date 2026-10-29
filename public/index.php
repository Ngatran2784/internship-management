<?php
/**
 * FRONT CONTROLLER - ENTRY POINT DUY NHẤT CỦA HỆ THỐNG
 * Dự án: Website Quản lý & Theo dõi Tiến độ Thực tập và Đồ án Sinh viên
 * Sprint 1 - Core Framework Setup (Nhóm trưởng - TV1)
 */

declare(strict_types=1);

// Thiết lập múi giờ và hiển thị lỗi môi trường phát triển
date_default_timezone_set('Asia/Ho_Chi_Minh');
ini_set('display_errors', '1');
error_reporting(E_ALL);

// 1. ĐĂNG KÝ AUTOLOADER CHO NAMESPACE DỰ ÁN (App\ -> app/)
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Core\Database;

// 2. KHỞI TẠO ROUTER
$router = new Router();

// 3. ĐĂNG KÝ ROUTE GET / HIỂN THỊ GIAO DIỆN TABLER DASHBOARD MẪU
$router->get('/', function () {
    // Gọi View Dashboard mẫu từ template Tabler chính thức
    // Dữ liệu được nạp vào View, TV3 sẽ tiếp tục tách layout và TV2 xử lý auth
    $viewPath = dirname(__DIR__) . '/app/Views/dashboard/index.php';
    if (file_exists($viewPath)) {
        require $viewPath;
    } else {
        echo "Dashboard view không tồn tại: " . htmlspecialchars($viewPath);
    }
});

// Route kiểm tra trạng thái hệ thống và PDO (Dành cho kiểm thử nội bộ)
$router->get('/status', function () {
    $dbConnected = false;
    $dbStatus = 'Chưa kết nối';
    try {
        $pdo = Database::getConnection();
        if ($pdo) {
            $dbConnected = true;
            $dbStatus = 'Kết nối MySQL thành công qua PDO (Port 3306)';
        }
    } catch (\Throwable $e) {
        $dbStatus = 'Lỗi: ' . $e->getMessage();
    }

    echo "<h3>Trạng thái hệ thống:</h3>";
    echo "<ul>";
    echo "<li>PHP: " . PHP_VERSION . "</li>";
    echo "<li>Web Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'CLI') . "</li>";
    echo "<li>Database: " . htmlspecialchars($dbStatus) . "</li>";
    echo "</ul>";
});

// 4. ĐIỀU HƯỚNG REQUEST
$router->dispatch();
