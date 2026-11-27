<?php

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * Milestone Model - Quản lý các mốc tiến độ thực tập / đồ án
 *
 * Chức năng:
 * - Quản lý các mốc tiến độ (Milestones) theo đợt thực tập / đồ án.
 * - Kiểm tra tính hợp lệ của thời hạn (start_date <= due_date, nằm trong đợt).
 * - Cung cấp dữ liệu tiến độ (tổng số mốc, hoàn thành, quá hạn, tỷ lệ %).
 * - Kiểm tra dữ liệu liên quan trước khi xóa (bảo vệ báo cáo đã nộp).
 */
class Milestone
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_OVERDUE   = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Chờ bắt đầu',
        self::STATUS_ACTIVE    => 'Đang diễn ra',
        self::STATUS_COMPLETED => 'Đã hoàn thành',
        self::STATUS_OVERDUE   => 'Quá hạn',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PENDING   => 'bg-secondary-lt text-secondary',
        self::STATUS_ACTIVE    => 'bg-primary-lt text-primary',
        self::STATUS_COMPLETED => 'bg-success-lt text-success',
        self::STATUS_OVERDUE   => 'bg-danger-lt text-danger',
        self::STATUS_CANCELLED => 'bg-dark-lt text-dark',
    ];

    /**
     * Tìm mốc tiến độ theo ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   p.code AS period_code, 
                   p.name AS period_name,
                   p.status AS period_status,
                   p.start_date AS period_start_date,
                   p.end_date AS period_end_date,
                   u.full_name AS creator_name,
                   u.username AS creator_username
            FROM `milestones` m
            INNER JOIN `internship_periods` p ON m.period_id = p.id
            INNER JOIN `users` u ON m.created_by = u.id
            WHERE m.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Lấy toàn bộ danh sách milestone kèm bộ lọc
     */
    public static function all(?int $periodId = null, ?string $status = null, ?string $keyword = null): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT m.*, 
                   p.code AS period_code, 
                   p.name AS period_name,
                   p.status AS period_status,
                   u.full_name AS creator_name,
                   (SELECT COUNT(*) FROM `internship_reports` r WHERE r.milestone_id = m.id) AS total_reports,
                   (SELECT COUNT(*) FROM `internship_reports` r WHERE r.milestone_id = m.id AND r.status = 'approved') AS approved_reports
            FROM `milestones` m
            INNER JOIN `internship_periods` p ON m.period_id = p.id
            INNER JOIN `users` u ON m.created_by = u.id
            WHERE 1=1
        ";

        $params = [];

        if ($periodId !== null) {
            $sql .= " AND m.period_id = :period_id";
            $params[':period_id'] = $periodId;
        }

        if ($status !== null && in_array($status, self::ALLOWED_STATUSES, true)) {
            $sql .= " AND m.status = :status";
            $params[':status'] = $status;
        }

        if ($keyword !== null && trim($keyword) !== '') {
            $sql .= " AND (m.title LIKE :kw1 OR m.description LIKE :kw2)";
            $kwVal = '%' . trim($keyword) . '%';
            $params[':kw1'] = $kwVal;
            $params[':kw2'] = $kwVal;
        }

        $sql .= " ORDER BY p.id DESC, m.order_index ASC, m.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách milestone theo đợt
     */
    public static function getByPeriod(int $periodId, bool $withStats = false): array
    {
        return self::all($periodId);
    }

    /**
     * Kiểm tra và tự động đồng bộ trạng thái quá hạn (overdue)
     */
    public static function syncOverdueStatus(?int $periodId = null): int
    {
        $pdo = Database::getConnection();
        $now = date('Y-m-d H:i:s');

        $sql = "
            UPDATE `milestones`
            SET `status` = :overdue_status
            WHERE `due_date` < :now
              AND `status` IN (:pending_status, :active_status)
        ";

        $params = [
            ':overdue_status' => self::STATUS_OVERDUE,
            ':now'            => $now,
            ':pending_status' => self::STATUS_PENDING,
            ':active_status'  => self::STATUS_ACTIVE,
        ];

        if ($periodId !== null) {
            $sql .= " AND `period_id` = :period_id";
            $params[':period_id'] = $periodId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Tạo mới một milestone
     */
    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(reset($errors));
        }

        $pdo = Database::getConnection();

        // Tự động tính order_index nếu không truyền
        if (empty($data['order_index'])) {
            $stmtOrder = $pdo->prepare("SELECT COALESCE(MAX(order_index), 0) + 1 FROM `milestones` WHERE period_id = :period_id");
            $stmtOrder->execute([':period_id' => (int)$data['period_id']]);
            $orderIndex = (int)$stmtOrder->fetchColumn();
        } else {
            $orderIndex = (int)$data['order_index'];
        }

        $sql = "
            INSERT INTO `milestones` (
                `period_id`, `title`, `description`, `start_date`, `due_date`, 
                `order_index`, `status`, `is_mandatory`, `created_by`, `created_at`
            ) VALUES (
                :period_id, :title, :description, :start_date, :due_date,
                :order_index, :status, :is_mandatory, :created_by, NOW()
            )
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':period_id'    => (int)$data['period_id'],
            ':title'        => trim($data['title']),
            ':description'  => !empty($data['description']) ? trim($data['description']) : null,
            ':start_date'   => $data['start_date'],
            ':due_date'     => $data['due_date'],
            ':order_index'  => $orderIndex,
            ':status'       => $data['status'] ?? self::STATUS_PENDING,
            ':is_mandatory' => isset($data['is_mandatory']) ? (int)(bool)$data['is_mandatory'] : 1,
            ':created_by'   => (int)$data['created_by'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Cập nhật thông tin milestone
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if (!$existing) {
            throw new InvalidArgumentException('Mốc tiến độ không tồn tại.');
        }

        $merged = array_merge($existing, $data);
        $errors = self::validate($merged, $id);
        if (!empty($errors)) {
            throw new InvalidArgumentException(reset($errors));
        }

        $pdo = Database::getConnection();

        $sql = "
            UPDATE `milestones`
            SET `period_id`    = :period_id,
                `title`        = :title,
                `description`  = :description,
                `start_date`   = :start_date,
                `due_date`     = :due_date,
                `order_index`  = :order_index,
                `status`       = :status,
                `is_mandatory` = :is_mandatory,
                `updated_at`   = NOW()
            WHERE `id` = :id
        ";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'           => $id,
            ':period_id'    => (int)$merged['period_id'],
            ':title'        => trim($merged['title']),
            ':description'  => !empty($merged['description']) ? trim($merged['description']) : null,
            ':start_date'   => $merged['start_date'],
            ':due_date'     => $merged['due_date'],
            ':order_index'  => (int)$merged['order_index'],
            ':status'       => $merged['status'],
            ':is_mandatory' => (int)(bool)$merged['is_mandatory'],
        ]);
    }

    /**
     * Cập nhật nhanh trạng thái
     */
    public static function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Trạng thái không hợp lệ.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `milestones` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([':id' => $id, ':status' => $status]);
    }

    /**
     * Kiểm tra điều kiện trước khi xóa milestone
     */
    public static function canDelete(int $id, string &$reason = ''): bool
    {
        $pdo = Database::getConnection();

        // Kiểm tra xem đã có báo cáo thực tập nộp vào mốc này chưa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `internship_reports` WHERE milestone_id = :id");
        $stmt->execute([':id' => $id]);
        $reportCount = (int)$stmt->fetchColumn();

        if ($reportCount > 0) {
            $reason = "Không thể xóa mốc tiến độ này vì đã có {$reportCount} báo cáo của sinh viên được nộp.";
            return false;
        }

        return true;
    }

    /**
     * Xóa milestone khi thỏa mãn điều kiện nghiệp vụ
     */
    public static function delete(int $id): bool
    {
        $reason = '';
        if (!self::canDelete($id, $reason)) {
            throw new InvalidArgumentException($reason);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `milestones` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Lấy danh sách milestone kèm trạng thái nộp báo cáo của một sinh viên cụ thể
     * (Phục vụ theo dõi tiến độ và nộp bài của sinh viên)
     */
    public static function getStudentMilestones(int $studentId, ?int $periodId = null): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT m.*,
                   p.code AS period_code,
                   p.name AS period_name,
                   r.id AS report_id,
                   r.title AS report_title,
                   r.status AS report_status,
                   r.grade AS report_grade,
                   r.current_version AS report_version,
                   r.submitted_at AS report_submitted_at,
                   sub.file_name AS latest_file_name,
                   sub.file_path AS latest_file_path,
                   sub.file_size AS latest_file_size,
                   sub.is_late AS submission_is_late,
                   CASE 
                       WHEN r.status = 'approved' THEN 'completed'
                       WHEN r.id IS NOT NULL THEN 'submitted'
                       WHEN m.due_date < NOW() THEN 'overdue'
                       WHEN m.start_date <= CURDATE() THEN 'in_progress'
                       ELSE 'pending'
                   END AS student_progress_state
            FROM `milestones` m
            INNER JOIN `internship_periods` p ON m.period_id = p.id
            LEFT JOIN `internship_reports` r ON r.milestone_id = m.id AND r.student_id = :student_id
            LEFT JOIN `report_submissions` sub ON sub.report_id = r.id AND sub.version = r.current_version
            WHERE 1=1
        ";

        $params = [':student_id' => $studentId];

        if ($periodId !== null) {
            $sql .= " AND m.period_id = :period_id";
            $params[':period_id'] = $periodId;
        }

        $sql .= " ORDER BY m.order_index ASC, m.due_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tính toán số liệu thống kê tiến độ hoàn thành theo đợt / sinh viên
     * Phục vụ hiển thị Progress Bar, Dashboard và Widget tiến độ
     */
    public static function getProgressStats(int $periodId, ?int $studentId = null): array
    {
        $pdo = Database::getConnection();

        // 1. Thống kê chung về mốc của đợt
        $stmtTotal = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_milestones,
                SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) AS completed_milestones,
                SUM(CASE WHEN `status` = 'overdue' THEN 1 ELSE 0 END) AS overdue_milestones,
                SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) AS active_milestones,
                SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) AS pending_milestones
            FROM `milestones`
            WHERE `period_id` = :period_id AND `status` != 'cancelled'
        ");
        $stmtTotal->execute([':period_id' => $periodId]);
        $stats = $stmtTotal->fetch(PDO::FETCH_ASSOC) ?: [
            'total_milestones'     => 0,
            'completed_milestones' => 0,
            'overdue_milestones'   => 0,
            'active_milestones'    => 0,
            'pending_milestones'   => 0,
        ];

        $total = (int)$stats['total_milestones'];
        $stats['completion_rate'] = $total > 0 ? round(((int)$stats['completed_milestones'] / $total) * 100, 1) : 0.0;

        // 2. Nếu có student_id, tính thêm tiến độ cá nhân của sinh viên
        if ($studentId !== null) {
            $stmtStudent = $pdo->prepare("
                SELECT 
                    COUNT(m.id) AS student_total_milestones,
                    SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) AS student_completed_milestones,
                    SUM(CASE WHEN r.id IS NOT NULL AND r.status != 'approved' THEN 1 ELSE 0 END) AS student_submitted_milestones,
                    SUM(CASE WHEN r.id IS NULL AND m.due_date < NOW() THEN 1 ELSE 0 END) AS student_overdue_milestones
                FROM `milestones` m
                LEFT JOIN `internship_reports` r ON r.milestone_id = m.id AND r.student_id = :student_id
                WHERE m.period_id = :period_id AND m.status != 'cancelled'
            ");
            $stmtStudent->execute([
                ':period_id'   => $periodId,
                ':student_id' => $studentId,
            ]);
            $stuStats = $stmtStudent->fetch(PDO::FETCH_ASSOC) ?: [];
            $stuTotal = (int)($stuStats['student_total_milestones'] ?? 0);
            $stuDone  = (int)($stuStats['student_completed_milestones'] ?? 0);

            $stats['student_total']      = $stuTotal;
            $stats['student_completed']  = $stuDone;
            $stats['student_submitted']  = (int)($stuStats['student_submitted_milestones'] ?? 0);
            $stats['student_overdue']    = (int)($stuStats['student_overdue_milestones'] ?? 0);
            $stats['student_rate']       = $stuTotal > 0 ? round(($stuDone / $stuTotal) * 100, 1) : 0.0;
        }

        return $stats;
    }

    /**
     * Xác thực dữ liệu đầu vào của mốc tiến độ (Validation)
     */
    public static function validate(array $data, ?int $milestoneId = null): array
    {
        $errors = [];

        // 1. Tiêu đề
        if (empty($data['title']) || trim($data['title']) === '') {
            $errors['title'] = 'Tiêu đề mốc tiến độ không được để trống.';
        } elseif (mb_strlen(trim($data['title'])) > 255) {
            $errors['title'] = 'Tiêu đề không được vượt quá 255 ký tự.';
        }

        // 2. Đợt thực tập
        if (empty($data['period_id']) || !filter_var($data['period_id'], FILTER_VALIDATE_INT)) {
            $errors['period_id'] = 'Đợt thực tập / đồ án không hợp lệ.';
        } else {
            $period = InternshipPeriod::findById((int)$data['period_id']);
            if (!$period) {
                $errors['period_id'] = 'Đợt thực tập / đồ án không tồn tại.';
            }
        }

        // 3. Ngày bắt đầu
        if (empty($data['start_date']) || !strtotime($data['start_date'])) {
            $errors['start_date'] = 'Ngày bắt đầu không hợp lệ.';
        }

        // 4. Hạn nộp (Due date)
        if (empty($data['due_date']) || !strtotime($data['due_date'])) {
            $errors['due_date'] = 'Hạn chót hoàn thành không hợp lệ.';
        }

        // 5. Kiểm tra logic start_date <= due_date
        if (empty($errors['start_date']) && empty($errors['due_date'])) {
            $startTs = strtotime($data['start_date']);
            $dueTs   = strtotime($data['due_date']);
            if ($startTs > $dueTs) {
                $errors['due_date'] = 'Hạn chót nộp bài phải sau hoặc bằng ngày bắt đầu.';
            }
        }

        // 6. Trạng thái
        if (isset($data['status']) && !in_array($data['status'], self::ALLOWED_STATUSES, true)) {
            $errors['status'] = 'Trạng thái mốc tiến độ không hợp lệ.';
        }

        // 7. Người tạo (khi tạo mới)
        if ($milestoneId === null && (empty($data['created_by']) || !filter_var($data['created_by'], FILTER_VALIDATE_INT))) {
            $errors['created_by'] = 'Người tạo mốc tiến độ không hợp lệ.';
        }

        return $errors;
    }
}
