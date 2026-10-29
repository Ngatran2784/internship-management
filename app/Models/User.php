<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * User Model - Thao tác với bảng `users` và liên kết `user_roles`
 * 
 * PHÂN CÔNG: Thành viên 2 (Backend & Database)
 * SPRINT: Sprint 1
 */
class User
{
    /**
     * Tìm người dùng theo ID
     * 
     * TODO [TV2 - Task #107]:
     * - Viết câu lệnh Prepared Statement SELECT từ bảng `users` WHERE id = :id.
     */
    public static function findById(int $id): ?array
    {
        // TODO: Triển khai truy vấn tìm user theo ID
        return null;
    }

    /**
     * Tìm người dùng theo Username hoặc Email
     * 
     * TODO [TV2 - Task #108]:
     * - Viết câu lệnh Prepared Statement SELECT từ bảng `users` WHERE username = :identifier OR email = :identifier.
     * - Trả về mảng dữ liệu user hoặc null nếu không tìm thấy.
     */
    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        // TODO: Triển khai truy vấn tìm user phục vụ đăng nhập
        return null;
    }

    /**
     * Lấy danh sách vai trò của một người dùng
     * 
     * TODO [TV2 - Task #109]:
     * - Thực hiện JOIN giữa bảng `roles` và `user_roles` WHERE user_id = :userId.
     * - Trả về mảng tên vai trò (ví dụ: ['admin', 'lecturer']).
     */
    public static function getRoles(int $userId): array
    {
        // TODO: Triển khai truy vấn lấy vai trò từ user_roles
        return [];
    }

    /**
     * Tạo mới người dùng
     * 
     * TODO [TV2 - Task #110]:
     * - Hash mật khẩu bằng password_hash($password, PASSWORD_BCRYPT).
     * - INSERT vào bảng `users`.
     */
    public static function create(array $data): int
    {
        // TODO: Triển khai thêm người dùng mới
        return 0;
    }
}
