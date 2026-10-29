<?php

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Khởi tạo kết nối PDO Singleton an toàn
     * 
     * @return PDO
     * @throws RuntimeException khi kết nối thất bại (không để lộ mật khẩu hay credentials)
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configPath = dirname(__DIR__, 2) . '/config/database.php';
            $exampleConfigPath = dirname(__DIR__, 2) . '/config/database.example.php';

            if (file_exists($configPath)) {
                $config = require $configPath;
            } elseif (file_exists($exampleConfigPath)) {
                $config = require $exampleConfigPath;
            } else {
                throw new RuntimeException("Tệp cấu hình cơ sở dữ liệu không tồn tại. Vui lòng tạo config/database.php từ config/database.example.php.");
            }

            $host     = $config['host'] ?? '127.0.0.1';
            $port     = $config['port'] ?? 3306;
            $dbname   = $config['dbname'] ?? '';
            $charset  = $config['charset'] ?? 'utf8mb4';
            $username = $config['username'] ?? 'root';
            $password = $config['password'] ?? '';
            $options  = $config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                // Ghi nhận lỗi vào hệ thống log để debug nội bộ
                error_log("Database Connection Error: " . $e->getMessage());

                // Tuyệt đối không hiển thị mật khẩu hoặc thông số DSN ra màn hình
                throw new RuntimeException("Kết nối cơ sở dữ liệu thất bại. Vui lòng kiểm tra dịch vụ MySQL và cấu hình kết nối.");
            }
        }

        return self::$instance;
    }

    /**
     * Ngăn chặn clone và deserialize instance
     */
    private function __construct() {}
    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}
