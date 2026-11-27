<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * InternshipLog Model - Quản lý bảng `internship_logs` (Nhật ký thực tập của sinh viên)
 *
 * Chức năng:
 * - Lưu trữ nhật ký công việc hằng tuần của sinh viên.
 * - Liên kết toàn vẹn với kết quả phân công (`topic_assignments`), sinh viên và đợt thực tập.
 * - Hỗ trợ quy trình phê duyệt / xác nhận của Giảng viên hướng dẫn hoặc Cán bộ Doanh nghiệp.
 */
class InternshipLog
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED  = 'rejected';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_CONFIRMED,
        self::STATUS_REJECTED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Bản nháp',
        self::STATUS_SUBMITTED => 'Đã gửi duyệt',
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_REJECTED  => 'Yêu cầu viết lại',
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'bg-secondary-lt text-secondary',
        self::STATUS_SUBMITTED => 'bg-warning-lt text-warning',
        self::STATUS_CONFIRMED => 'bg-success-lt text-success',
        self::STATUS_REJECTED  => 'bg-danger-lt text-danger',
    ];

    /**
     * Tìm nhật ký theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT l.*,
                   u_s.full_name AS student_name,
                   u_s.username AS student_code,
                   u_c.full_name AS confirmer_name,
                   p.name AS period_name,
                   t.name AS topic_name
            FROM `internship_logs` l
            INNER JOIN `users` u_s ON l.student_id = u_s.id
            LEFT JOIN `users` u_c ON l.confirmed_by = u_c.id
            INNER JOIN `internship_periods` p ON l.period_id = p.id
            INNER JOIN `topic_assignments` a ON l.assignment_id = a.id
            INNER JOIN `topics` t ON a.topic_id = t.id
            WHERE l.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Lấy danh sách nhật ký của một sinh viên
     */
    public static function getByStudent(int $studentId, ?int $periodId = null): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT l.*,
                   u_c.full_name AS confirmer_name
            FROM `internship_logs` l
            LEFT JOIN `users` u_c ON l.confirmed_by = u_c.id
            WHERE l.student_id = :student_id
        ";

        $params = [':student_id' => $studentId];

        if ($periodId !== null) {
            $sql .= " AND l.period_id = :period_id";
            $params[':period_id'] = $periodId;
        }

        $sql .= " ORDER BY l.week_number ASC, l.log_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách nhật ký theo phân công
     */
    public static function getByAssignment(int $assignmentId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT l.*, u_c.full_name AS confirmer_name
            FROM `internship_logs` l
            LEFT JOIN `users` u_c ON l.confirmed_by = u_c.id
            WHERE l.assignment_id = :assignment_id
            ORDER BY l.week_number ASC
        ");
        $stmt->execute([':assignment_id' => $assignmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách nhật ký cần duyệt của các sinh viên do một Giảng viên hướng dẫn
     */
    public static function getByLecturer(int $lecturerId, int $periodId, ?string $status = null): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT l.*,
                   u_s.full_name AS student_name,
                   u_s.username AS student_code,
                   t.name AS topic_name
            FROM `internship_logs` l
            INNER JOIN `topic_assignments` a ON l.assignment_id = a.id
            INNER JOIN `users` u_s ON l.student_id = u_s.id
            INNER JOIN `topics` t ON a.topic_id = t.id
            WHERE a.lecturer_id = :lecturer_id
              AND a.period_id = :period_id
        ";

        $params = [
            ':lecturer_id' => $lecturerId,
            ':period_id'   => $periodId,
        ];

        if ($status !== null) {
            $sql .= " AND l.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY l.week_number DESC, l.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo nhật ký thực tập mới
     */
    public static function create(array $data): int
    {
        if (empty($data['assignment_id']) || empty($data['student_id']) || empty($data['week_number']) || empty($data['work_done'])) {
            throw new InvalidArgumentException('Vui lòng điền đầy đủ các thông tin bắt buộc của nhật ký thực tập.');
        }

        $pdo = Database::getConnection();

        // Kiểm tra xem tuần này đã có nhật ký chưa
        $stmtCheck = $pdo->prepare("
            SELECT id FROM `internship_logs` 
            WHERE assignment_id = :aid AND week_number = :week
        ");
        $stmtCheck->execute([
            ':aid'  => (int)$data['assignment_id'],
            ':week' => (int)$data['week_number'],
        ]);
        if ($stmtCheck->fetch()) {
            throw new InvalidArgumentException("Nhật ký tuần thứ {$data['week_number']} đã tồn tại trong đợt phân công này.");
        }

        $sql = "
            INSERT INTO `internship_logs` (
                `assignment_id`, `student_id`, `period_id`, `week_number`, `log_date`,
                `work_done`, `difficulties`, `plan_next_week`, `status`, `created_at`
            ) VALUES (
                :assignment_id, :student_id, :period_id, :week_number, :log_date,
                :work_done, :difficulties, :plan_next_week, :status, NOW()
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':assignment_id' => (int)$data['assignment_id'],
            ':student_id'    => (int)$data['student_id'],
            ':period_id'     => (int)$data['period_id'],
            ':week_number'   => (int)$data['week_number'],
            ':log_date'      => $data['log_date'] ?? date('Y-m-d'),
            ':work_done'     => trim($data['work_done']),
            ':difficulties'  => !empty($data['difficulties']) ? trim($data['difficulties']) : null,
            ':plan_next_week'=> !empty($data['plan_next_week']) ? trim($data['plan_next_week']) : null,
            ':status'        => $data['status'] ?? self::STATUS_DRAFT,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Cập nhật nội dung nhật ký
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Bản ghi nhật ký không tồn tại.');
        }

        if ($existing['status'] === self::STATUS_CONFIRMED) {
            throw new InvalidArgumentException('Nhật ký đã được xác nhận, không thể chỉnh sửa.');
        }

        $pdo = Database::getConnection();

        $sql = "
            UPDATE `internship_logs`
            SET `work_done`       = :work_done,
                `difficulties`    = :difficulties,
                `plan_next_week`  = :plan_next_week,
                `log_date`        = :log_date,
                `status`          = :status,
                `updated_at`      = NOW()
            WHERE `id` = :id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'            => $id,
            ':work_done'     => trim($data['work_done'] ?? $existing['work_done']),
            ':difficulties'  => isset($data['difficulties']) ? trim($data['difficulties']) : $existing['difficulties'],
            ':plan_next_week'=> isset($data['plan_next_week']) ? trim($data['plan_next_week']) : $existing['plan_next_week'],
            ':log_date'      => $data['log_date'] ?? $existing['log_date'],
            ':status'        => $data['status'] ?? $existing['status'],
        ]);
    }

    /**
     * Giảng viên hoặc Cán bộ DN xác nhận nhật ký
     */
    public static function confirm(int $id, int $confirmerId, ?string $comment = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `internship_logs`
            SET `status`             = :status,
                `confirmed_by`       = :confirmer_id,
                `supervisor_comment` = :comment,
                `confirmed_at`       = NOW(),
                `updated_at`         = NOW()
            WHERE `id` = :id
        ");

        return $stmt->execute([
            ':id'           => $id,
            ':status'       => self::STATUS_CONFIRMED,
            ':confirmer_id' => $confirmerId,
            ':comment'      => $comment ? trim($comment) : null,
        ]);
    }

    /**
     * Yêu cầu viết lại nhật ký
     */
    public static function reject(int $id, int $confirmerId, ?string $comment = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `internship_logs`
            SET `status`             = :status,
                `confirmed_by`       = :confirmer_id,
                `supervisor_comment` = :comment,
                `confirmed_at`       = NOW(),
                `updated_at`         = NOW()
            WHERE `id` = :id
        ");

        return $stmt->execute([
            ':id'           => $id,
            ':status'       => self::STATUS_REJECTED,
            ':confirmer_id' => $confirmerId,
            ':comment'      => $comment ? trim($comment) : null,
        ]);
    }

    /**
     * Xóa nhật ký (chỉ khi ở trạng thái draft)
     */
    public static function delete(int $id): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Nhật ký không tồn tại.');
        }

        if ($existing['status'] === self::STATUS_CONFIRMED) {
            throw new InvalidArgumentException('Không thể xóa nhật ký đã được xác nhận.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `internship_logs` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
}
