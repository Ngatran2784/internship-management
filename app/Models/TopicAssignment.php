<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * TopicAssignment Model - Quản lý kết quả phân công đề tài và giảng viên hướng dẫn
 *
 * Nghiệp vụ theo Product Backlog:
 * - Lưu trữ kết quả phân công giữa sinh viên, đề tài và giảng viên theo đợt.
 * - Bảo đảm toàn vẹn: 1 sinh viên chỉ được phân công tối đa 1 đề tài trong cùng 1 đợt (uk_period_student).
 * - Trạng thái: draft (dự thảo), published (đã công bố), cancelled (hủy).
 * - Phương thức: auto (thuật toán tự động đối sánh), manual (phân công thủ công).
 * - Sẵn sàng tích hợp cho phân công thủ công (Trúc) và giao diện hiển thị (Quỳnh).
 */
class TopicAssignment
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';

    public const METHOD_AUTO   = 'auto';
    public const METHOD_MANUAL = 'manual';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELLED,
    ];

    public const ALLOWED_METHODS = [
        self::METHOD_AUTO,
        self::METHOD_MANUAL,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT     => 'Bản dự thảo',
        self::STATUS_PUBLISHED => 'Đã công bố',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'bg-warning-lt text-warning',
        self::STATUS_PUBLISHED => 'bg-success-lt text-success',
        self::STATUS_CANCELLED => 'bg-secondary-lt text-secondary',
    ];

    public const METHOD_LABELS = [
        self::METHOD_AUTO   => 'Tự động đối sánh',
        self::METHOD_MANUAL => 'Phân công thủ công',
    ];

    public const METHOD_BADGES = [
        self::METHOD_AUTO   => 'badge bg-blue-lt',
        self::METHOD_MANUAL => 'badge bg-purple-lt',
    ];

    // =========================================================================
    // CÁC PHƯƠNG THỨC TRUY VẤN (QUERY METHODS - SẴN SÀNG CHO GIAO DIỆN)
    // =========================================================================

    /**
     * Lấy danh sách kết quả phân công theo đợt kèm thông tin chi tiết
     */
    public static function getByPeriod(int $periodId, array $filters = []): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                a.id,
                a.period_id,
                a.student_id,
                a.topic_id,
                a.lecturer_id,
                a.matched_wish_priority,
                a.assignment_method,
                a.status,
                a.assigned_by,
                a.notes,
                a.assigned_at,
                a.updated_at,
                -- Sinh viên
                u_s.username AS student_code,
                u_s.full_name AS student_name,
                u_s.email AS student_email,
                u_s.phone AS student_phone,
                -- Đề tài
                t.code AS topic_code,
                t.name AS topic_name,
                t.type AS topic_type,
                t.max_students AS topic_max_students,
                -- Giảng viên hướng dẫn
                u_l.full_name AS lecturer_name,
                u_l.email AS lecturer_email,
                -- Người phân công / duyệt
                u_b.full_name AS assigner_name
            FROM topic_assignments a
            JOIN users u_s ON a.student_id = u_s.id
            JOIN topics t ON a.topic_id = t.id
            JOIN users u_l ON a.lecturer_id = u_l.id
            LEFT JOIN users u_b ON a.assigned_by = u_b.id
            WHERE a.period_id = :period_id
        ";

        $params = [':period_id' => $periodId];

        if (!empty($filters['status'])) {
            $sql .= " AND a.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $sql .= " AND a.assignment_method = :method";
            $params[':method'] = $filters['method'];
        }

        if (!empty($filters['lecturer_id'])) {
            $sql .= " AND a.lecturer_id = :lecturer_id";
            $params[':lecturer_id'] = (int)$filters['lecturer_id'];
        }

        if (!empty($filters['topic_id'])) {
            $sql .= " AND a.topic_id = :topic_id";
            $params[':topic_id'] = (int)$filters['topic_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND (u_s.full_name LIKE :kw1 OR u_s.username LIKE :kw2 OR t.name LIKE :kw3 OR t.code LIKE :kw4)";
            $kw = '%' . trim($filters['keyword']) . '%';
            $params[':kw1'] = $kw;
            $params[':kw2'] = $kw;
            $params[':kw3'] = $kw;
            $params[':kw4'] = $kw;
        }

        $sql .= " ORDER BY a.status ASC, a.matched_wish_priority ASC, u_s.username ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm bản ghi phân công của một sinh viên trong đợt cụ thể
     *
     * @param int $studentId ID người dùng sinh viên
     * @param int|null $periodId ID đợt thực tập / đồ án (tùy chọn)
     * @param bool $onlyPublished Nếu true, chỉ trả về khi kết quả đã công bố (bảo mật cho giao diện Sinh viên)
     * @return array|null Thông tin phân công hoặc null nếu chưa có/chưa công bố
     */
    public static function getByStudent(int $studentId, ?int $periodId = null, bool $onlyPublished = false): ?array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                a.*,
                t.code AS topic_code,
                t.name AS topic_name,
                t.description AS topic_description,
                t.max_students AS topic_max_students,
                u_l.full_name AS lecturer_name,
                u_l.email AS lecturer_email,
                u_l.phone AS lecturer_phone,
                p.code AS period_code,
                p.name AS period_name
            FROM topic_assignments a
            JOIN topics t ON a.topic_id = t.id
            JOIN users u_l ON a.lecturer_id = u_l.id
            JOIN internship_periods p ON a.period_id = p.id
            WHERE a.student_id = :student_id
        ";

        $params = [':student_id' => $studentId];

        if ($periodId !== null) {
            $sql .= " AND a.period_id = :period_id";
            $params[':period_id'] = $periodId;
        }

        if ($onlyPublished) {
            $sql .= " AND a.status = :pub_status";
            $params[':pub_status'] = self::STATUS_PUBLISHED;
        } else {
            $sql .= " AND a.status != :cancelled_status";
            $params[':cancelled_status'] = self::STATUS_CANCELLED;
        }

        $sql .= " ORDER BY a.id DESC LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lấy danh sách sinh viên được phân công cho một giảng viên trong đợt
     */
    public static function getByLecturer(int $lecturerId, int $periodId, bool $onlyPublished = false): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                a.*,
                u_s.username AS student_code,
                u_s.full_name AS student_name,
                u_s.email AS student_email,
                u_s.phone AS student_phone,
                t.code AS topic_code,
                t.name AS topic_name
            FROM topic_assignments a
            JOIN users u_s ON a.student_id = u_s.id
            JOIN topics t ON a.topic_id = t.id
            WHERE a.lecturer_id = :lecturer_id
              AND a.period_id = :period_id
              AND a.status != :cancelled_status
        ";

        $params = [
            ':lecturer_id' => $lecturerId,
            ':period_id'   => $periodId,
            ':cancelled_status' => self::STATUS_CANCELLED,
        ];

        if ($onlyPublished) {
            $sql .= " AND a.status = :pub_status";
            $params[':pub_status'] = self::STATUS_PUBLISHED;
        }

        $sql .= " ORDER BY t.id ASC, u_s.username ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm số lượng sinh viên đang được gán cho một đề tài (chưa bị hủy)
     */
    public static function countByTopic(int $topicId, int $periodId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM topic_assignments 
            WHERE topic_id = :topic_id 
              AND period_id = :period_id 
              AND status != :cancelled_status
        ");
        $stmt->execute([
            ':topic_id'  => $topicId,
            ':period_id' => $periodId,
            ':cancelled_status' => self::STATUS_CANCELLED,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Đếm số lượng sinh viên đang được gán cho một giảng viên trong đợt
     */
    public static function countByLecturer(int $lecturerId, int $periodId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM topic_assignments 
            WHERE lecturer_id = :lecturer_id 
              AND period_id = :period_id 
              AND status != :cancelled_status
        ");
        $stmt->execute([
            ':lecturer_id' => $lecturerId,
            ':period_id'   => $periodId,
            ':cancelled_status' => self::STATUS_CANCELLED,
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Lấy danh sách sinh viên đã nộp nguyện vọng nhưng CHƯA ĐƯỢC PHÂN CÔNG trong đợt
     * (Sẵn sàng cho chức năng phân công thủ công của Trúc và giao diện của Quỳnh)
     */
    public static function getUnassignedStudents(int $periodId): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT DISTINCT
                u.id AS student_id,
                u.username AS student_code,
                u.full_name AS student_name,
                u.email AS student_email,
                u.phone AS student_phone,
                (
                    SELECT COUNT(*) 
                    FROM topic_wishes tw2 
                    WHERE tw2.student_id = u.id AND tw2.period_id = :period_id1
                ) AS wishes_count
            FROM topic_wishes tw
            JOIN users u ON tw.student_id = u.id
            WHERE tw.period_id = :period_id2
              AND u.id NOT IN (
                  SELECT a.student_id 
                  FROM topic_assignments a 
                  WHERE a.period_id = :period_id3 
                    AND a.status != :cancelled_status
              )
            ORDER BY u.username ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':period_id1'       => $periodId,
            ':period_id2'       => $periodId,
            ':period_id3'       => $periodId,
            ':cancelled_status' => self::STATUS_CANCELLED,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê tổng quan phân công của đợt
     */
    public static function getStatistics(int $periodId): array
    {
        $pdo = Database::getConnection();

        // 1. Tổng sinh viên nộp nguyện vọng
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM topic_wishes WHERE period_id = :p");
        $stmt->execute([':p' => $periodId]);
        $totalRegistered = (int)$stmt->fetchColumn();

        // 2. Thống kê theo phân công
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_assignments,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) AS active_assignments,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published_count,
                SUM(CASE WHEN assignment_method = 'auto' AND status != 'cancelled' THEN 1 ELSE 0 END) AS auto_count,
                SUM(CASE WHEN assignment_method = 'manual' AND status != 'cancelled' THEN 1 ELSE 0 END) AS manual_count,
                SUM(CASE WHEN matched_wish_priority = 1 AND status != 'cancelled' THEN 1 ELSE 0 END) AS nv1_count,
                SUM(CASE WHEN matched_wish_priority = 2 AND status != 'cancelled' THEN 1 ELSE 0 END) AS nv2_count,
                SUM(CASE WHEN matched_wish_priority = 3 AND status != 'cancelled' THEN 1 ELSE 0 END) AS nv3_count
            FROM topic_assignments
            WHERE period_id = :p
        ");
        $stmt->execute([':p' => $periodId]);
        $statRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $activeAssignments = (int)($statRow['active_assignments'] ?? 0);
        $unassignedCount = max(0, $totalRegistered - $activeAssignments);

        // 3. Thống kê đề tài
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_approved_topics,
                SUM(max_students) AS total_capacity
            FROM topics
            WHERE period_id = :p AND status = 'approved'
        ");
        $stmt->execute([':p' => $periodId]);
        $topicRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'period_id'            => $periodId,
            'total_registered'     => $totalRegistered,
            'active_assignments'   => $activeAssignments,
            'unassigned_count'     => $unassignedCount,
            'draft_count'          => (int)($statRow['draft_count'] ?? 0),
            'published_count'      => (int)($statRow['published_count'] ?? 0),
            'auto_count'           => (int)($statRow['auto_count'] ?? 0),
            'manual_count'         => (int)($statRow['manual_count'] ?? 0),
            'nv1_count'            => (int)($statRow['nv1_count'] ?? 0),
            'nv2_count'            => (int)($statRow['nv2_count'] ?? 0),
            'nv3_count'            => (int)($statRow['nv3_count'] ?? 0),
            'total_approved_topics'=> (int)($topicRow['total_approved_topics'] ?? 0),
            'total_capacity'       => (int)($topicRow['total_capacity'] ?? 0),
            'is_published'         => ((int)($statRow['published_count'] ?? 0)) > 0,
        ];
    }

    /**
     * Kiểm tra xem đợt thực tập đã có kết quả phân công nào được công bố (published) hay chưa
     */
    public static function hasPublishedAssignments(int $periodId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM topic_assignments 
            WHERE period_id = :period_id AND status = :pub_status
        ");
        $stmt->execute([
            ':period_id'  => $periodId,
            ':pub_status' => self::STATUS_PUBLISHED,
        ]);

        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Lấy danh sách đề tài đã duyệt trong một đợt kèm thông tin giảng viên hướng dẫn và số chỗ còn lại
     *
     * Chuẩn hóa dữ liệu theo Data Contract Sprint 4:
     * - Bảng topics dùng `proposer_id` (người đề xuất đề tài).
     * - Bảng topic_assignments dùng `lecturer_id` (giảng viên hướng dẫn chính thức).
     * - Trả về các trường chuẩn: `topic_id`, `topic_name`, `topic_code`, `max_students`, `remaining_slots`, `lecturer_id`, `lecturer_name`.
     * - Không áp đặt mặc định người đề xuất là người hướng dẫn:
     *   + Nếu đề tài đã có phân công: lấy `lecturer_id` từ `topic_assignments`.
     *   + Nếu chưa có phân công: tạm lấy `proposer_id` làm giảng viên đề xuất ban đầu và gắn cờ `is_proposer_default = true`.
     *
     * @param int $periodId ID đợt thực tập / đồ án
     * @return array Danh sách đề tài đã chuẩn hóa
     */
    public static function getApprovedTopicsWithSlots(int $periodId): array
    {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                t.id AS topic_id,
                t.code AS topic_code,
                t.name AS topic_name,
                t.type AS topic_type,
                t.max_students,
                t.proposer_id,
                u_p.full_name AS proposer_name,
                u_p.email AS proposer_email,
                -- Giảng viên hướng dẫn: ưu tiên lấy từ topic_assignments nếu đã phân công
                COALESCE(
                    (SELECT a_sub.lecturer_id 
                     FROM topic_assignments a_sub 
                     WHERE a_sub.topic_id = t.id 
                       AND a_sub.period_id = t.period_id 
                       AND a_sub.status != :c1 
                     LIMIT 1),
                    t.proposer_id
                ) AS lecturer_id,
                COALESCE(
                    (SELECT u_l.full_name 
                     FROM topic_assignments a_sub 
                     JOIN users u_l ON a_sub.lecturer_id = u_l.id 
                     WHERE a_sub.topic_id = t.id 
                       AND a_sub.period_id = t.period_id 
                       AND a_sub.status != :c2 
                     LIMIT 1),
                    u_p.full_name
                ) AS lecturer_name,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM topic_assignments a_sub 
                        WHERE a_sub.topic_id = t.id 
                          AND a_sub.period_id = t.period_id 
                          AND a_sub.status != :c3
                    ) THEN 0
                    ELSE 1
                END AS is_proposer_default,
                COUNT(CASE WHEN a.status IS NOT NULL AND a.status != :c4 THEN a.id END) AS assigned_count,
                GREATEST(0, CAST(t.max_students AS SIGNED) - COUNT(CASE WHEN a.status IS NOT NULL AND a.status != :c5 THEN a.id END)) AS remaining_slots
            FROM topics t
            JOIN users u_p ON t.proposer_id = u_p.id
            LEFT JOIN topic_assignments a ON t.id = a.topic_id AND a.period_id = t.period_id AND a.status != :c6
            WHERE t.period_id = :period_id 
              AND t.status = 'approved'
            GROUP BY t.id, t.code, t.name, t.type, t.max_students, t.proposer_id, u_p.full_name, u_p.email
            ORDER BY t.code ASC, t.id ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':period_id' => $periodId,
            ':c1'        => self::STATUS_CANCELLED,
            ':c2'        => self::STATUS_CANCELLED,
            ':c3'        => self::STATUS_CANCELLED,
            ':c4'        => self::STATUS_CANCELLED,
            ':c5'        => self::STATUS_CANCELLED,
            ':c6'        => self::STATUS_CANCELLED,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['topic_id']            = (int)$r['topic_id'];
            $r['max_students']        = (int)$r['max_students'];
            $r['assigned_count']      = (int)$r['assigned_count'];
            $r['remaining_slots']     = (int)$r['remaining_slots'];
            $r['proposer_id']         = (int)$r['proposer_id'];
            $r['lecturer_id']         = (int)$r['lecturer_id'];
            $r['is_proposer_default'] = (bool)$r['is_proposer_default'];
        }
        unset($r);

        return $rows;
    }

    // =========================================================================
    // CÁC PHƯƠNG THỨC THAO TÁC DỮ LIỆU (MUTATION METHODS - SẴN SÀNG CHO TRÚC)
    // =========================================================================

    /**
     * Xóa sạch các phân công tự động dạng DỰ THẢO của đợt để chuẩn bị chạy lại thuật toán
     * (Tuyệt đối KHÔNG xóa các phân công thủ công hoặc đã công bố)
     */
    public static function deleteDraftAutoAssignments(int $periodId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            DELETE FROM topic_assignments 
            WHERE period_id = :period_id 
              AND status = :draft_status 
              AND assignment_method = :auto_method
        ");
        $stmt->execute([
            ':period_id'    => $periodId,
            ':draft_status' => self::STATUS_DRAFT,
            ':auto_method'  => self::METHOD_AUTO,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Phân công thủ công một sinh viên vào đề tài (Sẵn sàng tích hợp cho Trúc - S4-02)
     * Bảo đảm an toàn đồng thời (Concurrency-safe) bằng Transaction và Khóa dòng InnoDB (Pessimistic Row-Locking)
     */
    public static function assignManual(
        int $periodId,
        int $studentId,
        int $topicId,
        int $lecturerId,
        ?int $assignedBy = null,
        ?string $notes = null,
        int $maxStudentsPerLecturer = 5
    ): int {
        $pdo = Database::getConnection();
        $manageTransaction = !$pdo->inTransaction();
        if ($manageTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // 1. KHÓA DÒNG ĐỀ TÀI ĐỘC QUYỀN (SELECT ... FOR UPDATE)
            // Khóa dòng cha của đề tài để serialize tuần tự mọi thao tác phân công vào đề tài này
            $stmtTopic = $pdo->prepare("
                SELECT id, period_id, status, max_students 
                FROM topics 
                WHERE id = :id 
                FOR UPDATE
            ");
            $stmtTopic->execute([':id' => $topicId]);
            $topic = $stmtTopic->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                throw new InvalidArgumentException("Đề tài không tồn tại.");
            }
            if ((int)$topic['period_id'] !== $periodId) {
                throw new InvalidArgumentException("Đề tài không thuộc đợt thực tập này.");
            }
            if ($topic['status'] !== 'approved') {
                throw new InvalidArgumentException("Chỉ được phân công vào đề tài đã được phê duyệt.");
            }

            // 2. KHÓA DÒNG GIẢNG VIÊN ĐỘC QUYỀN (SELECT ... FOR UPDATE)
            $stmtLecturer = $pdo->prepare("SELECT id FROM users WHERE id = :id FOR UPDATE");
            $stmtLecturer->execute([':id' => $lecturerId]);

            // 3. KIỂM TRA LẠI CHỈ TIÊU BÊN TRONG TRANSACTION SAU KHI ĐÃ CÓ KHÓA DÒNG
            // Đếm số lượng sinh viên đã gán cho đề tài (loại trừ chính SV nếu đang cập nhật)
            $stmtCountTopic = $pdo->prepare("
                SELECT COUNT(*) 
                FROM topic_assignments 
                WHERE topic_id = :topic_id 
                  AND period_id = :period_id 
                  AND status != 'cancelled'
                  AND student_id != :student_id
            ");
            $stmtCountTopic->execute([
                ':topic_id'   => $topicId,
                ':period_id'  => $periodId,
                ':student_id' => $studentId,
            ]);
            $currentTopicCount = (int)$stmtCountTopic->fetchColumn();

            if ($currentTopicCount >= (int)$topic['max_students']) {
                throw new RuntimeException("Đề tài đã đủ chỉ tiêu sinh viên ({$currentTopicCount}/{$topic['max_students']}).");
            }

            // Đếm số lượng sinh viên đã gán cho giảng viên trong đợt
            $stmtCountLecturer = $pdo->prepare("
                SELECT COUNT(*) 
                FROM topic_assignments 
                WHERE lecturer_id = :lecturer_id 
                  AND period_id = :period_id 
                  AND status != 'cancelled'
                  AND student_id != :student_id
            ");
            $stmtCountLecturer->execute([
                ':lecturer_id' => $lecturerId,
                ':period_id'   => $periodId,
                ':student_id'  => $studentId,
            ]);
            $currentLecturerCount = (int)$stmtCountLecturer->fetchColumn();

            if ($currentLecturerCount >= $maxStudentsPerLecturer) {
                throw new RuntimeException("Giảng viên hướng dẫn đã đạt giới hạn chỉ tiêu ({$currentLecturerCount}/{$maxStudentsPerLecturer}).");
            }

            // 4. THỰC THI GHI CSDL (Bảo đảm UNIQUE period_id, student_id)
            $sql = "
                INSERT INTO topic_assignments 
                    (period_id, student_id, topic_id, lecturer_id, matched_wish_priority, assignment_method, status, assigned_by, notes, assigned_at)
                VALUES 
                    (:period_id, :student_id, :topic_id, :lecturer_id, NULL, :method, :status, :assigned_by, :notes, NOW())
                ON DUPLICATE KEY UPDATE
                    topic_id = VALUES(topic_id),
                    lecturer_id = VALUES(lecturer_id),
                    matched_wish_priority = NULL,
                    assignment_method = VALUES(assignment_method),
                    status = VALUES(status),
                    assigned_by = VALUES(assigned_by),
                    notes = VALUES(notes),
                    updated_at = NOW()
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':period_id'   => $periodId,
                ':student_id'  => $studentId,
                ':topic_id'    => $topicId,
                ':lecturer_id' => $lecturerId,
                ':method'      => self::METHOD_MANUAL,
                ':status'      => self::STATUS_DRAFT,
                ':assigned_by' => $assignedBy,
                ':notes'       => $notes ?: 'Phân công thủ công bởi cán bộ quản lý',
            ]);

            $lastId = (int)$pdo->lastInsertId();

            if ($manageTransaction) {
                $pdo->commit();
            }

            return $lastId;
        } catch (Throwable $e) {
            if ($manageTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof \PDOException && ($e->getCode() === '40001' || str_contains($e->getMessage(), '1213'))) {
                throw new RuntimeException("Xung đột truy cập đồng thời (Deadlock). Vui lòng thử lại.", 0, $e);
            }
            throw $e;
        }
    }

    /**
     * Hủy kết quả phân công của một sinh viên (Sẵn sàng tích hợp cho Trúc - S4-02)
     */
    public static function cancelAssignment(int $assignmentId, ?string $reason = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE topic_assignments 
            SET status = :cancelled_status, 
                notes = CONCAT(COALESCE(notes, ''), ' [Hủy: ', :reason, ']'),
                updated_at = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':cancelled_status' => self::STATUS_CANCELLED,
            ':reason'           => $reason ?: 'Hủy bởi người quản lý',
            ':id'               => $assignmentId,
        ]);
    }

    /**
     * Công bố kết quả phân công của đợt (Sẵn sàng tích hợp cho Trúc - S4-04)
     */
    public static function publishPeriodAssignments(int $periodId, ?int $confirmedBy = null): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE topic_assignments 
            SET status = :pub_status,
                assigned_by = COALESCE(:confirmed_by, assigned_by),
                updated_at = NOW()
            WHERE period_id = :period_id 
              AND status = :draft_status
        ");

        $stmt->execute([
            ':pub_status'    => self::STATUS_PUBLISHED,
            ':confirmed_by'  => $confirmedBy,
            ':period_id'     => $periodId,
            ':draft_status'  => self::STATUS_DRAFT,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Rút lại công bố kết quả phân công về trạng thái dự thảo
     */
    public static function unpublishPeriodAssignments(int $periodId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE topic_assignments 
            SET status = :draft_status,
                updated_at = NOW()
            WHERE period_id = :period_id 
              AND status = :pub_status
        ");

        $stmt->execute([
            ':draft_status' => self::STATUS_DRAFT,
            ':pub_status'   => self::STATUS_PUBLISHED,
            ':period_id'    => $periodId,
        ]);

        return $stmt->rowCount();
    }
}
