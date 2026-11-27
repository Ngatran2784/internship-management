<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use RuntimeException;

/**
 * ReportReview Model - Quản lý bảng `report_reviews` (Nhận xét, kết quả duyệt và yêu cầu chỉnh sửa)
 *
 * Nghiệp vụ:
 * - Lưu trữ nhận xét, đánh giá của Giảng viên hướng dẫn hoặc Cán bộ Doanh nghiệp.
 * - Ghi nhận yêu cầu chỉnh sửa (edit_requirements) khi ra quyết định `resubmit_required`.
 * - Tự động đồng bộ trạng thái tổng thể của báo cáo (`internship_reports`).
 */
class ReportReview
{
    public const DECISION_APPROVED          = 'approved';
    public const DECISION_REJECTED          = 'rejected';
    public const DECISION_RESUBMIT_REQUIRED = 'resubmit_required';
    public const DECISION_COMMENT_ONLY      = 'comment_only';

    public const ALLOWED_DECISIONS = [
        self::DECISION_APPROVED,
        self::DECISION_REJECTED,
        self::DECISION_RESUBMIT_REQUIRED,
        self::DECISION_COMMENT_ONLY,
    ];

    public const DECISION_LABELS = [
        self::DECISION_APPROVED          => 'Phê duyệt (Đạt)',
        self::DECISION_REJECTED          => 'Từ chối (Không đạt)',
        self::DECISION_RESUBMIT_REQUIRED => 'Yêu cầu nộp lại',
        self::DECISION_COMMENT_ONLY      => 'Góp ý / Nhận xét',
    ];

    public const DECISION_BADGES = [
        self::DECISION_APPROVED          => 'bg-success-lt text-success',
        self::DECISION_REJECTED          => 'bg-danger-lt text-danger',
        self::DECISION_RESUBMIT_REQUIRED => 'bg-warning-lt text-warning',
        self::DECISION_COMMENT_ONLY      => 'bg-info-lt text-info',
    ];

    /**
     * Tạo nhận xét / kết quả đánh giá báo cáo
     */
    public static function create(array $data): int
    {
        if (empty($data['report_id']) || empty($data['reviewer_id']) || empty($data['comments'])) {
            throw new InvalidArgumentException('Vui lòng cung cấp đầy đủ ID báo cáo, người nhận xét và nội dung nhận xét.');
        }

        $decision = $data['decision'] ?? self::DECISION_COMMENT_ONLY;
        if (!in_array($decision, self::ALLOWED_DECISIONS, true)) {
            throw new InvalidArgumentException('Quyết định đánh giá không hợp lệ.');
        }

        if ($decision === self::DECISION_RESUBMIT_REQUIRED && empty($data['edit_requirements']) && empty($data['comments'])) {
            throw new InvalidArgumentException('Vui lòng nêu rõ các yêu cầu cần chỉnh sửa khi yêu cầu sinh viên nộp lại.');
        }

        $pdo = Database::getConnection();
        $isOwnTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $isOwnTransaction = true;
        }

        try {
            $sql = "
                INSERT INTO `report_reviews` (
                    `report_id`, `submission_id`, `reviewer_id`, `decision`,
                    `comments`, `edit_requirements`, `score`, `reviewed_at`, `created_at`
                ) VALUES (
                    :report_id, :submission_id, :reviewer_id, :decision,
                    :comments, :edit_requirements, :score, NOW(), NOW()
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':report_id'         => (int)$data['report_id'],
                ':submission_id'     => !empty($data['submission_id']) ? (int)$data['submission_id'] : null,
                ':reviewer_id'       => (int)$data['reviewer_id'],
                ':decision'          => $decision,
                ':comments'          => trim($data['comments']),
                ':edit_requirements' => !empty($data['edit_requirements']) ? trim($data['edit_requirements']) : null,
                ':score'             => isset($data['score']) && is_numeric($data['score']) ? (float)$data['score'] : null,
            ]);

            $reviewId = (int)$pdo->lastInsertId();

            // Đồng bộ trạng thái báo cáo chính
            $newReportStatus = match ($decision) {
                self::DECISION_APPROVED          => InternshipReport::STATUS_APPROVED,
                self::DECISION_REJECTED          => InternshipReport::STATUS_REJECTED,
                self::DECISION_RESUBMIT_REQUIRED => InternshipReport::STATUS_RESUBMIT_REQUIRED,
                default                          => InternshipReport::STATUS_REVIEWING,
            };

            $score = isset($data['score']) && is_numeric($data['score']) ? (float)$data['score'] : null;
            InternshipReport::updateStatus((int)$data['report_id'], $newReportStatus, $score);

            if ($isOwnTransaction) {
                $pdo->commit();
            }
            return $reviewId;
        } catch (\Throwable $e) {
            if ($isOwnTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException('Lỗi hệ thống khi lưu nhận xét báo cáo: ' . $e->getMessage());
        }
    }

    /**
     * Lấy danh sách nhận xét của một báo cáo
     */
    public static function getByReport(int $reportId): array
    {
        return InternshipReport::getReviews($reportId);
    }
}
