<?php

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * TopicWish Model - Quản lý nguyện vọng đăng ký đề tài của sinh viên (Sprint 3)
 *
 * Nghiệp vụ theo Product Backlog:
 * - Sinh viên đăng ký tối đa 3 nguyện vọng (NV1, NV2, NV3) trong một đợt thực tập / đồ án đang mở.
 * - Chỉ được đăng ký các đề tài đã được duyệt (status = 'approved').
 * - Đợt thực tập / đồ án phải ở trạng thái đang diễn ra (status = 'active') và trong thời hạn đăng ký.
 * - Không được đăng ký trùng đề tài trong cùng một đợt.
 * - Không được trùng thứ tự ưu tiên (1, 2, 3) trong cùng một đợt.
 * - Cập nhật / lưu nguyện vọng được thực thi trong một giao dịch (transaction) nguyên tử.
 */
class TopicWish
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const MAX_WISHES = 3;

    public const ALLOWED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const ALLOWED_PRIORITIES = [1, 2, 3];

    public const STATUS_LABELS = [
        self::STATUS_PENDING  => 'Chờ xét duyệt',
        self::STATUS_APPROVED => 'Đã trúng tuyển',
        self::STATUS_REJECTED => 'Không trúng tuyển',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PENDING  => 'bg-warning-lt text-warning',
        self::STATUS_APPROVED => 'bg-success-lt text-success',
        self::STATUS_REJECTED => 'bg-danger-lt text-danger',
    ];

    /**
     * Kiểm tra quyền đăng ký nguyện vọng: Chỉ sinh viên được phép đăng ký
     */
    public static function canRegister(array $userRoles): bool
    {
        return in_array('student', $userRoles, true);
    }

    /**
     * Lấy danh sách nguyện vọng của sinh viên theo đợt cụ thể
     */
    public static function getByStudentAndPeriod(int $studentId, int $periodId): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                w.id,
                w.student_id,
                w.topic_id,
                w.period_id,
                w.priority,
                w.status,
                w.note,
                w.created_at,
                w.updated_at,
                t.code AS topic_code,
                t.name AS topic_name,
                t.description AS topic_description,
                t.type AS topic_type,
                t.max_students AS topic_max_students,
                t.status AS topic_status,
                u.full_name AS proposer_name,
                p.name AS period_name,
                p.code AS period_code,
                p.status AS period_status,
                p.start_date AS period_start_date,
                p.end_date AS period_end_date
            FROM `topic_wishes` w
            INNER JOIN `topics` t ON w.topic_id = t.id
            INNER JOIN `internship_periods` p ON w.period_id = p.id
            LEFT JOIN `users` u ON t.proposer_id = u.id
            WHERE w.student_id = :student_id
              AND w.period_id = :period_id
            ORDER BY w.priority ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':student_id' => $studentId,
            ':period_id'  => $periodId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy toàn bộ nguyện vọng của sinh viên qua các đợt
     */
    public static function getByStudent(int $studentId): array
    {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                w.id,
                w.student_id,
                w.topic_id,
                w.period_id,
                w.priority,
                w.status,
                w.note,
                w.created_at,
                w.updated_at,
                t.code AS topic_code,
                t.name AS topic_name,
                t.type AS topic_type,
                t.status AS topic_status,
                u.full_name AS proposer_name,
                p.name AS period_name,
                p.code AS period_code,
                p.status AS period_status,
                p.start_date AS period_start_date,
                p.end_date AS period_end_date
            FROM `topic_wishes` w
            INNER JOIN `topics` t ON w.topic_id = t.id
            INNER JOIN `internship_periods` p ON w.period_id = p.id
            LEFT JOIN `users` u ON t.proposer_id = u.id
            WHERE w.student_id = :student_id
            ORDER BY p.id DESC, w.priority ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra đợt thực tập có mở cho phép sinh viên đăng ký nguyện vọng không
     */
    public static function isPeriodOpenForRegistration(int $periodId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT status, start_date, end_date 
            FROM `internship_periods` 
            WHERE id = :id 
            LIMIT 1
        ");
        $stmt->execute([':id' => $periodId]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$period) {
            return false;
        }

        if ($period['status'] !== InternshipPeriod::STATUS_ACTIVE) {
            return false;
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        if ($today < $period['start_date'] || $today > $period['end_date']) {
            return false;
        }

        return true;
    }

    /**
     * Lấy danh sách các đề tài đã duyệt thuộc một đợt để sinh viên lựa chọn
     */
    public static function getApprovedTopicsForPeriod(int $periodId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.code,
                t.name,
                t.description,
                t.type,
                t.max_students,
                t.status,
                u.full_name AS proposer_name
            FROM `topics` t
            LEFT JOIN `users` u ON t.proposer_id = u.id
            WHERE t.period_id = :period_id
              AND t.status = :status
            ORDER BY t.code ASC
        ");
        $stmt->execute([
            ':period_id' => $periodId,
            ':status'    => Topic::STATUS_APPROVED,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lưu danh sách nguyện vọng của sinh viên cho một đợt (Thêm mới hoặc Cập nhật lại toàn bộ)
     *
     * @param int $studentId Mã sinh viên
     * @param int $periodId Mã đợt
     * @param array $wishes Mảng các nguyện vọng: [['topic_id' => int, 'priority' => int, 'note' => string], ...]
     * @return array Danh sách nguyện vọng sau khi lưu thành công
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function saveWishes(int $studentId, int $periodId, array $wishes): array
    {
        if (empty($wishes)) {
            throw new InvalidArgumentException('Danh sách nguyện vọng không được để trống.');
        }

        if (count($wishes) > self::MAX_WISHES) {
            throw new InvalidArgumentException('Sinh viên chỉ được đăng ký tối đa ' . self::MAX_WISHES . ' nguyện vọng.');
        }

        $pdo = Database::getConnection();

        // 1. Kiểm tra đợt thực tập hợp lệ và còn hạn
        $stmtPeriod = $pdo->prepare("SELECT id, status, start_date, end_date FROM `internship_periods` WHERE id = :id LIMIT 1");
        $stmtPeriod->execute([':id' => $periodId]);
        $period = $stmtPeriod->fetch(PDO::FETCH_ASSOC);

        if (!$period) {
            throw new InvalidArgumentException('Đợt thực tập/đồ án không tồn tại.');
        }

        if ($period['status'] !== InternshipPeriod::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Đợt này hiện không mở tiếp nhận đăng ký.');
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        if ($today < $period['start_date'] || $today > $period['end_date']) {
            throw new InvalidArgumentException('Thời hạn đăng ký nguyện vọng cho đợt này đã kết thúc hoặc chưa bắt đầu.');
        }

        // 2. Kiểm tra trùng lặp đề tài và thứ tự ưu tiên trong input
        $seenTopics = [];
        $seenPriorities = [];

        foreach ($wishes as $w) {
            $topicId = isset($w['topic_id']) ? (int)$w['topic_id'] : 0;
            $priority = isset($w['priority']) ? (int)$w['priority'] : 0;

            if ($topicId <= 0) {
                throw new InvalidArgumentException('Mã đề tài không hợp lệ.');
            }

            if (!in_array($priority, self::ALLOWED_PRIORITIES, true)) {
                throw new InvalidArgumentException('Thứ tự ưu tiên nguyện vọng phải từ 1 đến ' . self::MAX_WISHES . '.');
            }

            if (isset($seenTopics[$topicId])) {
                throw new InvalidArgumentException('Không được đăng ký trùng một đề tài cho nhiều nguyện vọng.');
            }
            $seenTopics[$topicId] = true;

            if (isset($seenPriorities[$priority])) {
                throw new InvalidArgumentException('Thứ tự ưu tiên các nguyện vọng không được trùng nhau.');
            }
            $seenPriorities[$priority] = true;
        }

        // 3. Kiểm tra tính hợp lệ của từng đề tài: phải tồn tại, thuộc đợt này và đã được duyệt (approved)
        $placeholders = implode(',', array_fill(0, count($seenTopics), '?'));
        $stmtTopicCheck = $pdo->prepare("
            SELECT id, code, name, status, period_id 
            FROM `topics` 
            WHERE id IN ($placeholders)
        ");
        $stmtTopicCheck->execute(array_keys($seenTopics));
        $fetchedTopics = $stmtTopicCheck->fetchAll(PDO::FETCH_ASSOC);

        if (count($fetchedTopics) !== count($seenTopics)) {
            throw new InvalidArgumentException('Một số đề tài lựa chọn không tồn tại trong hệ thống.');
        }

        foreach ($fetchedTopics as $ft) {
            if ((int)$ft['period_id'] !== $periodId) {
                throw new InvalidArgumentException("Đề tài [{$ft['code']}] không thuộc đợt đăng ký này.");
            }
            if ($ft['status'] !== Topic::STATUS_APPROVED) {
                throw new InvalidArgumentException("Đề tài [{$ft['code']}] chưa được phê duyệt, không thể đăng ký.");
            }
        }

        // 4. Thực thi lưu trữ trong Transaction
        $alreadyInTransaction = $pdo->inTransaction();
        if (!$alreadyInTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Xóa các nguyện vọng cũ của sinh viên trong đợt này
            $stmtDel = $pdo->prepare("DELETE FROM `topic_wishes` WHERE student_id = :student_id AND period_id = :period_id");
            $stmtDel->execute([
                ':student_id' => $studentId,
                ':period_id'  => $periodId,
            ]);

            // Thêm các nguyện vọng mới
            $stmtInsert = $pdo->prepare("
                INSERT INTO `topic_wishes` (`student_id`, `topic_id`, `period_id`, `priority`, `status`, `note`, `created_at`, `updated_at`)
                VALUES (:student_id, :topic_id, :period_id, :priority, :status, :note, NOW(), NOW())
            ");

            foreach ($wishes as $item) {
                $note = isset($item['note']) && is_string($item['note']) ? trim($item['note']) : null;
                $stmtInsert->execute([
                    ':student_id' => $studentId,
                    ':topic_id'   => (int)$item['topic_id'],
                    ':period_id'  => $periodId,
                    ':priority'   => (int)$item['priority'],
                    ':status'     => self::STATUS_PENDING,
                    ':note'       => !empty($note) ? mb_substr($note, 0, 255) : null,
                ]);
            }

            if (!$alreadyInTransaction) {
                $pdo->commit();
            }

            return self::getByStudentAndPeriod($studentId, $periodId);
        } catch (\Throwable $e) {
            if (!$alreadyInTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Lỗi khi lưu nguyện vọng: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Hủy toàn bộ nguyện vọng của sinh viên trong một đợt
     */
    public static function deleteByStudentAndPeriod(int $studentId, int $periodId): bool
    {
        if (!self::isPeriodOpenForRegistration($periodId)) {
            throw new InvalidArgumentException('Đợt này đã hết hạn hoặc không mở, không thể thay đổi nguyện vọng.');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM `topic_wishes` WHERE student_id = :student_id AND period_id = :period_id");
        return $stmt->execute([
            ':student_id' => $studentId,
            ':period_id'  => $periodId,
        ]);
    }
}
