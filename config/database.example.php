<?php
/**
 * CẤU HÌNH KẾT NỐI DATABASE MẪU
 * 
 * Hướng dẫn:
 * 1. Sao chép file này và đổi tên thành 'database.php' trong cùng thư mục config/
 * 2. Cập nhật thông tin kết nối phù hợp với môi trường cục bộ của bạn
 * 3. File 'database.php' đã được thêm vào .gitignore để đảm bảo bảo mật thông tin
 */

return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'internship_management',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
