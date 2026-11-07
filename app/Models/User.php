<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * User Model - Thao tác với bảng `users` và liên kết `user_roles`
 *
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
     * Danh sách người dùng kèm vai trò (KHÔNG trả về cột password).
     * Mỗi user có `roles` (mảng tên vai trò) và `roles_list` (chuỗi, ví dụ "lecturer, department_head").
     *
     * @param string|null $role    chỉ lấy người có vai trò này (tên, ví dụ 'student')
     * @param string|null $keyword tìm theo username, email hoặc họ tên
     * @param int|null    $status  lọc theo trạng thái: 1 = hoạt động, 0 = bị khóa, null = tất cả
     */
    public static function all(?string $role = null, ?string $keyword = null, ?int $status = null): array
    {
        $pdo    = Database::getConnection();
        $where  = [];
        $params = [];

        if ($role !== null && $role !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM `user_roles` ur2
                                INNER JOIN `roles` r2 ON r2.`id` = ur2.`role_id`
                                WHERE ur2.`user_id` = u.`id` AND r2.`name` = :role)';
            $params['role'] = $role;
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $like = '%' . addcslashes(trim($keyword), '\\%_') . '%';
            $where[] = '(u.`username` LIKE :kw_username OR u.`email` LIKE :kw_email OR u.`full_name` LIKE :kw_name)';
            $params['kw_username'] = $like;
            $params['kw_email']    = $like;
            $params['kw_name']     = $like;
        }

        if ($status === 0 || $status === 1) {
            $where[] = 'u.`status` = :status_filter';
            $params['status_filter'] = $status;
        }

        $sql = 'SELECT u.`id`, u.`username`, u.`email`, u.`full_name`, u.`phone`, u.`status`, u.`created_at`
                  FROM `users` u'
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY u.`id` ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $user['roles']      = $rolesByUser[(int)$user['id']] ?? [];
            $user['roles_list'] = implode(', ', $user['roles']);
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

    /**
     * Cập nhật đường dẫn ảnh đại diện cho người dùng
     *
     * @param int $id ID người dùng
     * @param string|null $avatarPath Đường dẫn tương đối từ thư mục web (ví dụ: uploads/avatars/xxx.jpg) hoặc null khi xóa
     * @return bool
     */
    public static function updateAvatar(int $id, ?string $avatarPath): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE `users` SET `avatar_path` = :avatar_path, `avatar` = :avatar WHERE `id` = :id'
        );

        return $stmt->execute([
            'avatar_path' => $avatarPath,
            'avatar'      => $avatarPath,
            'id'          => $id,
        ]);
    }

    /**
     * Kiểm tra username đã tồn tại chưa (không phân biệt hoa thường, giống ràng buộc UNIQUE).
     *
     * @param int|null $excludeId bỏ qua user này (dùng khi sửa chính user đó)
     */
    public static function existsUsername(string $username, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM `users` WHERE `username` = :username AND `id` <> :exclude_id LIMIT 1'
        );
        $stmt->execute(['username' => trim($username), 'exclude_id' => $excludeId ?? 0]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Kiểm tra email đã tồn tại chưa
     *
     * @param int|null $excludeId bỏ qua user này (dùng khi sửa chính user đó)
     */
    public static function existsEmail(string $email, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT 1 FROM `users` WHERE `email` = :email AND `id` <> :exclude_id LIMIT 1'
        );
        $stmt->execute(['email' => trim($email), 'exclude_id' => $excludeId ?? 0]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Cập nhật thông tin tài khoản: full_name, email, phone (bắt buộc);
     * `password` (không rỗng thì đổi mật khẩu, rỗng thì giữ nguyên); `status` (0/1, tùy chọn).
     * Username không được đổi.
     *
     * @throws InvalidArgumentException khi thiếu full_name/email hoặc status không hợp lệ
     * @throws \PDOException khi email trùng người khác (ràng buộc UNIQUE)
     */
    public static function update(int $id, array $data): bool
    {
        foreach (['full_name', 'email'] as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                throw new InvalidArgumentException("Thiếu trường bắt buộc: {$field}");
            }
        }

        $sets   = ['`full_name` = :full_name', '`email` = :email', '`phone` = :phone'];
        $params = [
            'full_name' => trim((string)$data['full_name']),
            'email'     => trim((string)$data['email']),
            'phone'     => ($data['phone'] ?? '') !== '' ? (string)$data['phone'] : null,
            'id'        => $id,
        ];

        if (isset($data['password']) && $data['password'] !== '') {
            $sets[] = '`password` = :password';
            $params['password'] = password_hash((string)$data['password'], PASSWORD_BCRYPT);
        }

        if (isset($data['status'])) {
            $status = (int)$data['status'];
            if ($status !== 0 && $status !== 1) {
                throw new InvalidArgumentException('Trạng thái chỉ được là 0 (khóa) hoặc 1 (hoạt động).');
            }
            $sets[] = '`status` = :status';
            $params['status'] = $status;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE `users` SET ' . implode(', ', $sets) . ' WHERE `id` = :id');

        return $stmt->execute($params);
    }

    /**
     * Khóa (0) hoặc mở khóa (1) tài khoản
     *
     * @throws InvalidArgumentException khi status không phải 0 hoặc 1
     */
    public static function updateStatus(int $id, int $status): bool
    {
        if ($status !== 0 && $status !== 1) {
            throw new InvalidArgumentException('Trạng thái chỉ được là 0 (khóa) hoặc 1 (hoạt động).');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE `users` SET `status` = :status WHERE `id` = :id');

        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /**
     * Tạo người dùng và gán vai trò trong cùng một transaction (lỗi giữa chừng thì hoàn tác toàn bộ)
     *
     * @param int[] $roleIds
     * @return int ID người dùng vừa tạo
     */
    public static function createWithRoles(array $data, array $roleIds): int
    {
        $pdo = Database::getConnection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $id = self::create($data);
            if ($roleIds) {
                self::assignRoles($id, $roleIds);
            }

            if ($ownTransaction) {
                $pdo->commit();
            }

            return $id;
        } catch (\Throwable $e) {
            if ($ownTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
