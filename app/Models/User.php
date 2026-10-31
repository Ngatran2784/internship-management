<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * User Model - Thao tác với bảng `users` và liên kết `user_roles`
 *
 * PHÂN CÔNG: Thành viên 2 (Trúc) - Sprint 1
 * Mọi truy vấn đều dùng PDO Prepared Statement.
 * Lưu ý: Database dùng EMULATE_PREPARES = false nên MỖI tên tham số
 * chỉ được xuất hiện 1 lần trong câu SQL (vì vậy dùng :username và :email riêng).
 */
class User
{
    /**
     * Tìm người dùng theo ID (trả về cả cột password đã hash để Controller đối soát nếu cần)
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM `users` WHERE `id` = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Tìm người dùng theo Username hoặc Email (phục vụ đăng nhập)
     */
    public static function findByUsernameOrEmail(string $identifier): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM `users` WHERE `username` = :username OR `email` = :email LIMIT 1'
        );
        $stmt->execute(['username' => $identifier, 'email' => $identifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Lấy danh sách TÊN vai trò của một người dùng, ví dụ ['admin', 'lecturer']
     */
    public static function getRoles(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT r.`name`
               FROM `roles` r
         INNER JOIN `user_roles` ur ON ur.`role_id` = r.`id`
              WHERE ur.`user_id` = :user_id
           ORDER BY r.`id` ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Tạo mới người dùng, mật khẩu được băm bằng Bcrypt.
     *
     * @param array $data username, email, password, full_name (bắt buộc); phone, status (tùy chọn)
     * @return int ID người dùng vừa tạo
     * @throws InvalidArgumentException khi thiếu dữ liệu bắt buộc
     * @throws \PDOException khi trùng username/email (ràng buộc UNIQUE)
     */
    public static function create(array $data): int
    {
        foreach (['username', 'email', 'password', 'full_name'] as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                throw new InvalidArgumentException("Thiếu trường bắt buộc: {$field}");
            }
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `status`)
             VALUES (:username, :email, :password, :full_name, :phone, :status)'
        );
        $stmt->execute([
            'username'  => trim((string)$data['username']),
            'email'     => trim((string)$data['email']),
            'password'  => password_hash((string)$data['password'], PASSWORD_BCRYPT),
            'full_name' => trim((string)$data['full_name']),
            'phone'     => isset($data['phone']) && $data['phone'] !== '' ? (string)$data['phone'] : null,
            'status'    => (int)($data['status'] ?? 1),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Danh sách người dùng kèm vai trò (KHÔNG trả về cột password)
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();

        $users = $pdo->query(
            'SELECT `id`, `username`, `email`, `full_name`, `phone`, `status`, `created_at`
               FROM `users` ORDER BY `id` ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $rolesByUser = [];
        $rows = $pdo->query(
            'SELECT ur.`user_id`, r.`name`
               FROM `user_roles` ur
         INNER JOIN `roles` r ON r.`id` = ur.`role_id`
           ORDER BY r.`id` ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $rolesByUser[(int)$row['user_id']][] = $row['name'];
        }

        foreach ($users as &$user) {
            $user['roles'] = $rolesByUser[(int)$user['id']] ?? [];
        }
        unset($user);

        return $users;
    }

    /**
     * Gán lại toàn bộ vai trò cho người dùng (thay thế tập vai trò cũ bằng tập mới)
     *
     * @param int[] $roleIds
     */
    public static function assignRoles(int $userId, array $roleIds): void
    {
        $pdo = Database::getConnection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $delete = $pdo->prepare('DELETE FROM `user_roles` WHERE `user_id` = :user_id');
            $delete->execute(['user_id' => $userId]);

            $insert = $pdo->prepare(
                'INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES (:user_id, :role_id)'
            );
            foreach (array_unique(array_map('intval', $roleIds)) as $roleId) {
                $insert->execute(['user_id' => $userId, 'role_id' => $roleId]);
            }

            if ($ownTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cập nhật hồ sơ cá nhân (họ tên, số điện thoại)
     */
    public static function updateProfile(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE `users` SET `full_name` = :full_name, `phone` = :phone WHERE `id` = :id'
        );

        return $stmt->execute([
            'full_name' => (string)$data['full_name'],
            'phone'     => ($data['phone'] ?? '') !== '' ? (string)$data['phone'] : null,
            'id'        => $id,
        ]);
    }
}
