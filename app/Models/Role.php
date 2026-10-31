<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Role Model - Thao tác với bảng `roles`
 */
class Role
{
    /**
     * Lấy toàn bộ danh sách vai trò hệ thống (sắp theo id tăng dần)
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT * FROM `roles` ORDER BY `id` ASC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm vai trò theo tên định danh (ví dụ: 'admin', 'student')
     */
    public static function findByName(string $name): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM `roles` WHERE `name` = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }
}
