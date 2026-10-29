<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Role Model - Thao tác với bảng `roles`
 * 
 * PHÂN CÔNG: Thành viên 2 (Backend & Database)
 * SPRINT: Sprint 1
 */
class Role
{
    /**
     * Lấy toàn bộ danh sách vai trò hệ thống
     * 
     * TODO [TV2 - Task #111]:
     * - Viết câu lệnh SELECT * FROM `roles` ORDER BY id ASC.
     */
    public static function all(): array
    {
        // TODO: Triển khai truy vấn lấy danh sách vai trò
        return [];
    }

    /**
     * Tìm vai trò theo ID hoặc Tên
     * 
     * TODO [TV2 - Task #112]:
     * - SELECT * FROM `roles` WHERE name = :name.
     */
    public static function findByName(string $name): ?array
    {
        // TODO: Triển khai truy vấn tìm vai trò theo định danh
        return null;
    }
}
