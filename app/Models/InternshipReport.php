<?php

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * InternshipReport Model - Quản lý bảng `internship_reports` và `report_submissions`
 *
 * Nghiệp vụ cốt lõi:
 * - Lưu trữ báo cáo của sinh viên theo từng mốc tiến độ (milestone).
 * - Lưu toàn bộ lịch sử các lần nộp và nộp lại (v1, v2, v3...) qua bảng `report_submissions`.
 * - Tuyệt đối KHÔNG ghi đè làm mất tệp tin hay dữ liệu của các phiên bản nộp trước.
 * - Kiểm tra chính xác hạn nộp (due_date) và đánh dấu cờ `is_late` khi sinh viên nộp sau thời hạn.
 */
class InternshipReport
{
    public const STATUS_DRAFT             = 'draft';
    public const STATUS_SUBMITTED         = 'submitted';
    public const STATUS_REVIEWING         = 'reviewing';
    public const STATUS_APPROVED          = 'approved';
    public const STATUS_REJECTED          = 'rejected';
    public const STATUS_RESUBMIT_REQUIRED = 'resubmit_required';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_REVIEWING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_RESUBMIT_REQUIRED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT             => 'Bản nháp',
        self::STATUS_SUBMITTED         => 'Đã nộp bài',
        self::STATUS_REVIEWING         => 'Đang chấm điểm',
        self::STATUS_APPROVED          => 'Đã duyệt (Đạt)',
        self::STATUS_REJECTED          => 'Không đạt',
        self::STATUS_RESUBMIT_REQUIRED => 'Cần nộp lại',
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT             => 'bg-secondary-lt text-secondary',
        self::STATUS_SUBMITTED         => 'bg-info-lt text-info',
        self::STATUS_REVIEWING         => 'bg-primary-lt text-primary',
        self::STATUS_APPROVED          => 'bg-success-lt text-success',
        self::STATUS_REJECTED          => 'bg-danger-lt text-danger',
        self::STATUS_RESUBMIT_REQUIRED => 'bg-warning-lt text-warning',
    ];

    /**
     * Tìm báo cáo theo ID
     */
    public static function findById(int $id, bool $withSubmissions = true): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   m.title AS milestone_title,
                   m.due_date AS milestone_due_date,
                   m.start_date AS milestone_start_date,
                   m.status AS milestone_status,
                   u_s.full_name AS student_name,
                   u_s.username AS student_code,
                   u_s.email AS student_email,
                   p.code AS period_code,
                   p.name AS period_name,
                   t.name AS topic_name,
                   a.lecturer_id
            FROM `internship_reports` r
            INNER JOIN `milestones` m ON r.milestone_id = m.id
            INNER JOIN `users` u_s ON r.student_id = u_s.id
            INNER JOIN `internship_periods` p ON r.period_id = p.id
            INNER JOIN `topic_assignments` a ON r.assignment_id = a.id
            INNER JOIN `topics` t ON a.topic_id = t.id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            return null;
        }

        if ($withSubmissions) {
            $report['submissions'] = self::getSubmissions($id);
            $report['reviews']     = self::getReviews($id);
        }

        return $report;
    }

    /**
     * Lấy báo cáo theo Sinh viên và Mốc tiến độ
     */
    public static function getByStudentAndMilestone(int $studentId, int $milestoneId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.* 
            FROM `internship_reports` r
            WHERE r.student_id = :student_id AND r.milestone_id = :milestone_id
            LIMIT 1
        ");
        $stmt->execute([
            ':student_id'   => $studentId,
            ':milestone_id' => $milestoneId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::findById((int)$row['id']) : null;
    }

    /**
     * Lấy toàn bộ lịch sử các lần nộp (submissions) của báo cáo
     */
    public static function getSubmissions(int $reportId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sub.*,
                   rev.decision AS review_decision,
                   rev.comments AS review_comments,
                   rev.score AS review_score,
                   rev.reviewed_at
            FROM `report_submissions` sub
            LEFT JOIN `report_reviews` rev ON rev.submission_id = sub.id
            WHERE sub.report_id = :report_id
            ORDER BY sub.version DESC
        ");
        $stmt->execute([':report_id' => $reportId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy toàn bộ danh sách nhận xét của báo cáo
     */
    public static function getReviews(int $reportId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT rev.*,
                   u.full_name AS reviewer_name,
                   u.username AS reviewer_username
            FROM `report_reviews` rev
            INNER JOIN `users` u ON rev.reviewer_id = u.id
            WHERE rev.report_id = :report_id
            ORDER BY rev.created_at DESC
        ");
        $stmt->execute([':report_id' => $reportId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Nộp báo cáo mới hoặc nộp lại (resubmit) phiên bản tiếp theo
     * Đảm bảo tính nguyên tử (Transaction) và không ghi đè dữ liệu cũ
     *
     * @param array $data Thông tin báo cáo: milestone_id, assignment_id, student_id, period_id, title, student_note
     * @param array $fileInfo Thông tin tệp tin đã upload: file_name, file_path, file_size, file_mime, file_ext
     * @return array Thông tin báo cáo và submission vừa tạo
     */
    public static function submit(array $data, array $fileInfo): array
    {
        $pdo = Database::getConnection();

        // 1. Kiểm tra tồn tại mốc tiến độ
        $milestone = Milestone::findById((int)$data['milestone_id']);
        if (!$milestone) {
            throw new InvalidArgumentException('Mốc tiến độ không tồn tại.');
        }

        // 2. Kiểm tra nộp trễ hạn (is_late)
        $now = new DateTimeImmutable();
        $dueDate = new DateTimeImmutable($milestone['due_date']);
        $isLate = ($now > $dueDate) ? 1 : 0;

        $isOwnTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $isOwnTransaction = true;
        }

        try {
            // 3. Tìm bản ghi báo cáo hiện có (nếu có)
            $stmtReport = $pdo->prepare("
                SELECT id, current_version, status 
                FROM `internship_reports` 
                WHERE student_id = :student_id AND milestone_id = :milestone_id
                FOR UPDATE
            ");
            $stmtReport->execute([
                ':student_id'   => (int)$data['student_id'],
                ':milestone_id' => (int)$data['milestone_id'],
            ]);
            $existingReport = $stmtReport->fetch(PDO::FETCH_ASSOC);

            if ($existingReport) {
                // Đã có báo cáo -> Nộp lại phiên bản mới (v+1)
                $reportId = (int)$existingReport['id'];
                $newVersion = (int)$existingReport['current_version'] + 1;

                $stmtUpdate = $pdo->prepare("
                    UPDATE `internship_reports`
                    SET `current_version` = :new_version,
                        `status`          = :status,
                        `title`           = :title,
                        `submitted_at`    = NOW(),
                        `updated_at`      = NOW()
                    WHERE `id` = :id
                ");
                $stmtUpdate->execute([
                    ':new_version' => $newVersion,
                    ':status'      => self::STATUS_SUBMITTED,
                    ':title'       => trim($data['title']),
                    ':id'          => $reportId,
                ]);
            } else {
                // Chưa có báo cáo -> Tạo mới bản ghi báo cáo với version = 1
                $newVersion = 1;
                $stmtInsert = $pdo->prepare("
                    INSERT INTO `internship_reports` (
                        `milestone_id`, `assignment_id`, `student_id`, `period_id`,
                        `title`, `current_version`, `status`, `submitted_at`, `created_at`
                    ) VALUES (
                        :milestone_id, :assignment_id, :student_id, :period_id,
                        :title, 1, :status, NOW(), NOW()
                    )
                ");
                $stmtInsert->execute([
                    ':milestone_id'  => (int)$data['milestone_id'],
                    ':assignment_id' => (int)$data['assignment_id'],
                    ':student_id'    => (int)$data['student_id'],
                    ':period_id'     => (int)$data['period_id'],
                    ':title'         => trim($data['title']),
                    ':status'        => self::STATUS_SUBMITTED,
                ]);
                $reportId = (int)$pdo->lastInsertId();
            }

            // 4. Lưu bản ghi lần nộp lịch sử vào `report_submissions`
            $stmtSub = $pdo->prepare("
                INSERT INTO `report_submissions` (
                    `report_id`, `version`, `file_name`, `file_path`, `file_size`,
                    `file_mime`, `file_ext`, `student_note`, `is_late`, `submitted_at`
                ) VALUES (
                    :report_id, :version, :file_name, :file_path, :file_size,
                    :file_mime, :file_ext, :student_note, :is_late, NOW()
                )
            ");
            $stmtSub->execute([
                ':report_id'    => $reportId,
                ':version'      => $newVersion,
                ':file_name'    => $fileInfo['file_name'],
                ':file_path'    => $fileInfo['file_path'],
                ':file_size'    => (int)$fileInfo['file_size'],
                ':file_mime'    => $fileInfo['file_mime'],
                ':file_ext'     => $fileInfo['file_ext'],
                ':student_note' => !empty($data['student_note']) ? trim($data['student_note']) : null,
                ':is_late'      => $isLate,
            ]);
            $submissionId = (int)$pdo->lastInsertId();

            if ($isOwnTransaction) {
                $pdo->commit();
            }

            return [
                'report_id'     => $reportId,
                'submission_id' => $submissionId,
                'version'       => $newVersion,
                'is_late'       => $isLate,
            ];
        } catch (\Throwable $e) {
            if ($isOwnTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Lỗi hệ thống khi lưu báo cáo: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật trạng thái và điểm của báo cáo
     */
    public static function updateStatus(int $reportId, string $status, ?float $grade = null): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Trạng thái báo cáo không hợp lệ.');
        }

        $pdo = Database::getConnection();
        $sql = "UPDATE `internship_reports` SET `status` = :status";
        $params = [':status' => $status, ':id' => $reportId];

        if ($grade !== null) {
            $sql .= ", `grade` = :grade";
            $params[':grade'] = $grade;
        }

        $sql .= ", `updated_at` = NOW() WHERE `id` = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }
}
