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

// 3. ĐĂNG KÝ ROUTE KIỂM TRA HOẠT ĐỘNG HỆ THỐNG (GET /)
$router->get('/', function () {
    // Kiểm tra trạng thái kết nối Database PDO (thử nghiệm an toàn)
    $dbStatus = 'Chưa kết nối (Cần tạo config/database.php hoặc bảng dữ liệu)';
    $dbConnected = false;
    try {
        $pdo = Database::getConnection();
        if ($pdo) {
            $dbStatus = 'Kết nối MySQL thành công qua PDO (Port 3306)';
            $dbConnected = true;
        }
    } catch (\Throwable $e) {
        $dbStatus = 'Thông báo: ' . $e->getMessage();
    }

    $phpVersion = PHP_VERSION;
    $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Server';

    echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Hệ thống Quản lý Thực tập & Đồ án - Sprint 1</title>
    <style>
        :root {
            --primary: #206bc4;
            --success: #2fb344;
            --warning: #f76707;
            --bg: #f4f6fa;
            --card-bg: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
        }
        body {
            margin: 0;
            padding: 40px 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }
        .container {
            max-width: 860px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: var(--primary);
            margin: 0 0 10px 0;
            font-size: 1.85rem;
        }
        .header p {
            color: var(--muted);
            margin: 0;
            font-size: 1rem;
        }
        .badge-sprint {
            display: inline-block;
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .card h2 {
            font-size: 1.2rem;
            margin: 0 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .status-item {
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 6px;
            border-left: 4px solid var(--primary);
        }
        .status-item.success { border-left-color: var(--success); }
        .status-item.warning { border-left-color: var(--warning); }
        .status-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 600;
        }
        .status-value {
            font-size: 0.95rem;
            font-weight: 500;
            margin-top: 4px;
            word-break: break-word;
        }
        .member-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .member-table th, .member-table td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
        }
        .member-table th {
            background-color: #f8fafc;
            color: var(--muted);
        }
        .tag {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .tag-lead { background: #dbeafe; color: #1e40af; }
        .tag-tv2 { background: #fef3c7; color: #92400e; }
        .tag-tv3 { background: #dcfce7; color: #166534; }
        .footer-note {
            text-align: center;
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <span class='badge-sprint'>Sprint 1: Kiến trúc MVC Thuần & Khung Hệ thống</span>
            <h1>Hệ thống Quản lý Thực tập & Đồ án Sinh viên</h1>
            <p>Dự án môn Công nghệ Phần mềm - Nhóm 3 thành viên</p>
        </div>

        <div class='card'>
            <h2>Trạng thái Môi trường & Core Framework (Nhóm trưởng)</h2>
            <div class='status-grid'>
                <div class='status-item success'>
                    <div class='status-label'>Phiên bản PHP</div>
                    <div class='status-value'>PHP {$phpVersion} (Thuần, Không Framework)</div>
                </div>
                <div class='status-item success'>
                    <div class='status-label'>Web Server</div>
                    <div class='status-value'>{$serverSoftware}</div>
                </div>
                <div class='status-item " . ($dbConnected ? 'success' : 'warning') . "'>
                    <div class='status-label'>Cơ sở dữ liệu (PDO MySQL)</div>
                    <div class='status-value'>{$dbStatus}</div>
                </div>
                <div class='status-item success'>
                    <div class='status-label'>Kiến trúc Mã nguồn</div>
                    <div class='status-value'>Mô hình MVC Tối giản (Autoload PSR-4)</div>
                </div>
            </div>
        </div>

        <div class='card'>
            <h2>Phân công Nhiệm vụ Sprint 1</h2>
            <table class='member-table'>
                <thead>
                    <tr>
                        <th>Vai trò</th>
                        <th>Thành viên</th>
                        <th>Trách nhiệm chính</th>
                        <th>Trạng thái Sprint 1</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class='tag tag-lead'>TV1 (Nhóm trưởng)</span></td>
                        <td>Software Architect</td>
                        <td>Kiến trúc MVC, Router, Database, Controller, SQL Schema, Docs</td>
                        <td><strong>HOÀN THÀNH</strong></td>
                    </tr>
                    <tr>
                        <td><span class='tag tag-tv2'>TV2</span></td>
                        <td>Backend & Auth</td>
                        <td>AuthController, UserController, User & Role Model, Xác thực</td>
                        <td>Đã tạo khung file & TODO</td>
                    </tr>
                    <tr>
                        <td><span class='tag tag-tv3'>TV3</span></td>
                        <td>Frontend & UI</td>
                        <td>Tích hợp Tabler UI, Layouts (Header/Sidebar/Footer), Views</td>
                        <td>Đã tạo khung file & TODO</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class='footer-note'>
            Hệ thống phát triển bằng PHP thuần 8.2 + MySQL XAMPP • Không phụ thuộc Laravel framework
        </div>
    </div>
</body>
</html>";
});

// 4. ĐIỀU HƯỚNG REQUEST
$router->dispatch();
