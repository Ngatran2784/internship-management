<?php
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
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\InternshipPeriodController;
use App\Controllers\DashboardController;
use App\Controllers\TopicController;
use App\Controllers\TopicApprovalController;
use App\Controllers\TopicWishController;
use App\Controllers\TopicAssignmentController;

// 2. KHỞI TẠO ROUTER
$router = new Router();

// 3. ĐĂNG KÝ ROUTE
// Route / : Bảo vệ đăng nhập - chuyển hướng sang /dashboard nếu đã đăng nhập, ngược lại sang /login
$router->get('/', function () {
    AuthController::startSession();
    if (AuthController::check()) {
        AuthController::go('/dashboard');
    } else {
        AuthController::go('/login');
    }
});

// --- Xác thực & Quản lý phiên làm việc ---
$router->get('/login',   [AuthController::class, 'showLoginForm']);
$router->post('/login',  [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/logout', function () {
    AuthController::go('/dashboard');
});

// Sau khi đăng nhập chuyển về /dashboard (đồng bộ dữ liệu thực tế)
$router->get('/dashboard', [DashboardController::class, 'index']);

// --- Quản lý Người dùng & Hồ sơ cá nhân ---
$router->get('/users',        [UserController::class, 'index']);
$router->post('/users/roles', [UserController::class, 'assignRoles']);
$router->get('/users/create',  [UserController::class, 'create']);
$router->post('/users/create', [UserController::class, 'store']);
$router->post('/users',        [UserController::class, 'store']);
$router->get('/users/edit',    [UserController::class, 'edit']);
$router->post('/users/update', [UserController::class, 'update']);
$router->post('/users/status', [UserController::class, 'updateStatus']);
$router->get('/profile',      [UserController::class, 'profile']);
$router->post('/profile',     [UserController::class, 'profile']);
$router->post('/profile/avatar',        [UserController::class, 'profile']);
$router->post('/profile/avatar/delete', [UserController::class, 'profile']);

// --- Quản lý Đợt thực tập & Đồ án tốt nghiệp ---
$router->get('/periods',          [InternshipPeriodController::class, 'index']);
$router->get('/periods/create',   [InternshipPeriodController::class, 'create']);
$router->post('/periods/create',  [InternshipPeriodController::class, 'store']);
$router->post('/periods',         [InternshipPeriodController::class, 'store']);
$router->get('/periods/detail',   [InternshipPeriodController::class, 'show']);
$router->get('/periods/edit',     [InternshipPeriodController::class, 'edit']);
$router->post('/periods/update',  [InternshipPeriodController::class, 'update']);
$router->post('/periods/edit',    [InternshipPeriodController::class, 'update']);
$router->post('/periods/status',  [InternshipPeriodController::class, 'updateStatus']);
$router->post('/periods/delete',  [InternshipPeriodController::class, 'destroy']);
$router->get('/periods/delete',   function () { AuthController::go('/periods'); });

// Hỗ trợ alias trực tiếp /periods/{id} sang xem chi tiết đợt
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (preg_match('#/periods/(\d+)$#', $reqPath, $matches)) {
    $_GET['id'] = (int)$matches[1];
    $router->get('/periods/' . $matches[1], [InternshipPeriodController::class, 'show']);
}

// --- Quản lý Đề tài thực tập & Đồ án tốt nghiệp ---
$router->get('/topics',         [TopicController::class, 'index']);
$router->get('/topics/create',  [TopicController::class, 'create']);
$router->post('/topics/create', [TopicController::class, 'store']);
$router->post('/topics',        [TopicController::class, 'store']);
$router->get('/topics/detail',  [TopicController::class, 'show']);
$router->get('/topics/edit',    [TopicController::class, 'edit']);
$router->post('/topics/update', [TopicController::class, 'update']);
$router->post('/topics/edit',   [TopicController::class, 'update']);

// --- Xét duyệt đề tài ---
$router->get('/topics/pending',  [TopicApprovalController::class, 'pending']);
$router->get('/topics/history',  [TopicApprovalController::class, 'history']);
$router->post('/topics/submit',  [TopicApprovalController::class, 'submit']);
$router->post('/topics/approve', [TopicApprovalController::class, 'approve']);
$router->post('/topics/reject',  [TopicApprovalController::class, 'reject']);

// --- Đăng ký nguyện vọng đề tài (Sinh viên) ---
$router->get('/wishes',          [TopicWishController::class, 'index']);
$router->get('/wishes/create',   [TopicWishController::class, 'create']);
$router->get('/wishes/edit',     [TopicWishController::class, 'create']);
$router->post('/wishes/save',    [TopicWishController::class, 'save']);
$router->post('/wishes/update',  [TopicWishController::class, 'save']);
$router->post('/wishes/delete',  [TopicWishController::class, 'delete']);

// --- Phân công đề tài & giảng viên ---
$router->get('/assignments',             [TopicAssignmentController::class, 'index']);
$router->get('/assignments/preview',     [TopicAssignmentController::class, 'preview']);
$router->post('/assignments/auto-match', [TopicAssignmentController::class, 'autoMatch']);

// Hỗ trợ alias trực tiếp /topics/{id} sang xem chi tiết đề tài
if (preg_match('#/topics/(\d+)$#', $reqPath, $topicMatches)) {
    $_GET['id'] = (int)$topicMatches[1];
    $router->get('/topics/' . $topicMatches[1], [TopicController::class, 'show']);
}

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

// 4. ĐIỀU HƯỚNG REQUEST (chỉ tự động dispatch khi truy cập qua Web hoặc chạy trực tiếp file index.php)
if (php_sapi_name() !== 'cli' || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $router->dispatch();
}
