<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * TopicApproval Model - Quy trình gửi duyệt / duyệt / từ chối đề tài và lịch sử xét duyệt
 *
 * Luồng trạng thái của đề tài (bảng `topics`):
 *   draft ──gửi duyệt──► pending ──duyệt──► approved
 *   rejected ──gửi duyệt lại──► pending     pending ──từ chối──► rejected
 *
 * Mỗi lần duyệt/từ chối được ghi vào bảng `topic_approvals` (người xử lý, thời điểm, lý do).
 * Việc đổi trạng thái dùng câu UPDATE có điều kiện trạng thái hiện tại nên không thể duyệt lặp.
 */
class TopicApproval
{
    public const DECISION_APPROVED = 'approved';
    public const DECISION_REJECTED = 'rejected';

    public const DECISION_LABELS = [
        self::DECISION_APPROVED => 'Đã duyệt',
        self::DECISION_REJECTED => 'Từ chối',
    ];

    /** Các vai trò được xét duyệt đề tài */
    public const REVIEWER_ROLES = ['admin', 'training_dept', 'department_head'];

    public const MAX_COMMENT_LENGTH = 1000;

    /**
     * Người dùng có quyền duyệt/từ chối đề tài không
     */
    public static function canReview(array $userRoles): bool
    {
        return count(array_intersect(self::REVIEWER_ROLES, $userRoles)) > 0;
    }

    /**
     * Người dùng có được gửi duyệt đề tài này không:
     * đề tài phải ở trạng thái draft/rejected; người đề xuất, admin hoặc phòng đào tạo được gửi.
     */
    public static function canSubmit(array $topic, int $userId, array $userRoles): bool
    {
        if (!in_array($topic['status'], [Topic::STATUS_DRAFT, Topic::STATUS_REJECTED], true)) {
            return false;
        }

        if (in_array('admin', $userRoles, true) || in_array('training_dept', $userRoles, true)) {
            return true;
        }

        return (int)$topic['proposer_id'] === $userId;
    }

    /**
     * Gửi duyệt: draft/rejected -> pending
     *
     * @throws InvalidArgumentException khi đề tài không tồn tại, sai trạng thái hoặc đợt đã kết thúc
     */
    public static function submit(int $topicId): void
    {
        $topic = Topic::findById($topicId);
        if ($topic === null) {
            throw new InvalidArgumentException('Đề tài không tồn tại.');
        }

        if (!in_array($topic['status'], [Topic::STATUS_DRAFT, Topic::STATUS_REJECTED], true)) {
            throw new InvalidArgumentException("Chỉ gửi duyệt được đề tài ở trạng thái 'Bản nháp' hoặc 'Từ chối'.");
        }

        self::assertPeriodNotClosed($topic);

        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE `topics`
                SET `status` = :new_status, `updated_at` = NOW()
              WHERE `id` = :id AND `status` IN (:old_draft, :old_rejected)'
        );
        $stmt->execute([
            'new_status'   => Topic::STATUS_PENDING,
            'id'           => $topicId,
            'old_draft'    => Topic::STATUS_DRAFT,
            'old_rejected' => Topic::STATUS_REJECTED,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new InvalidArgumentException('Đề tài vừa được thay đổi trạng thái ở nơi khác, vui lòng tải lại trang.');
        }
    }

    /**
     * Duyệt hoặc từ chối đề tài đang chờ duyệt, ghi lịch sử xét duyệt trong cùng một transaction.
     *
     * @throws InvalidArgumentException khi dữ liệu sai, đề tài không ở trạng thái pending, hoặc đã có người xử lý
     */
    public static function review(int $topicId, int $reviewerId, string $decision, ?string $comments = null): void
    {
        if (!in_array($decision, [self::DECISION_APPROVED, self::DECISION_REJECTED], true)) {
            throw new InvalidArgumentException('Quyết định xét duyệt không hợp lệ.');
        }

        $comments = $comments === null ? null : trim($comments);
        if ($comments === '') {
            $comments = null;
        }

        if ($decision === self::DECISION_REJECTED && $comments === null) {
            throw new InvalidArgumentException('Vui lòng nhập lý do từ chối.');
        }

        if ($comments !== null && mb_strlen($comments) > self::MAX_COMMENT_LENGTH) {
            throw new InvalidArgumentException('Nhận xét tối đa ' . self::MAX_COMMENT_LENGTH . ' ký tự.');
        }

        $topic = Topic::findById($topicId);
        if ($topic === null) {
            throw new InvalidArgumentException('Đề tài không tồn tại.');
        }

        if ($topic['status'] !== Topic::STATUS_PENDING) {
            throw new InvalidArgumentException("Đề tài không ở trạng thái 'Chờ duyệt' (có thể đã được xử lý).");
        }

        self::assertPeriodNotClosed($topic);

        $newStatus = $decision === self::DECISION_APPROVED ? Topic::STATUS_APPROVED : Topic::STATUS_REJECTED;

        $pdo            = Database::getConnection();
        $ownTransaction = !$pdo->inTransaction();

        if ($ownTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Điều kiện status = 'pending' đảm bảo chỉ một người xử lý thành công
            $update = $pdo->prepare(
                'UPDATE `topics`
                    SET `status` = :new_status, `updated_at` = NOW()
                  WHERE `id` = :id AND `status` = :old_status'
            );
            $update->execute([
                'new_status' => $newStatus,
                'id'         => $topicId,
                'old_status' => Topic::STATUS_PENDING,
            ]);

            if ($update->rowCount() === 0) {
                throw new InvalidArgumentException('Đề tài vừa được người khác xử lý, vui lòng tải lại trang.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO `topic_approvals` (`topic_id`, `reviewer_id`, `decision`, `comments`)
                 VALUES (:topic_id, :reviewer_id, :decision, :comments)'
            );
            $insert->execute([
                'topic_id'    => $topicId,
                'reviewer_id' => $reviewerId,
                'decision'    => $decision,
                'comments'    => $comments,
            ]);

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
     * Lịch sử xét duyệt của một đề tài (mới nhất trước)
     */
    public static function history(int $topicId): array
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT a.`id`, a.`topic_id`, a.`reviewer_id`, a.`decision`, a.`comments`, a.`created_at`,
                    u.`full_name` AS reviewer_name, u.`username` AS reviewer_username
               FROM `topic_approvals` a
         INNER JOIN `users` u ON u.`id` = a.`reviewer_id`
              WHERE a.`topic_id` = :topic_id
           ORDER BY a.`created_at` DESC, a.`id` DESC'
        );
        $stmt->execute(['topic_id' => $topicId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đề tài đã có lịch sử xét duyệt chưa (dùng để từ chối xóa cứng đề tài có liên kết nghiệp vụ)
     */
    public static function hasHistory(int $topicId): bool
    {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM `topic_approvals` WHERE `topic_id` = :topic_id LIMIT 1');
        $stmt->execute(['topic_id' => $topicId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Không cho gửi duyệt hoặc xét duyệt khi đợt đã kết thúc
     */
    private static function assertPeriodNotClosed(array $topic): void
    {
        if (($topic['period_status'] ?? null) === InternshipPeriod::STATUS_CLOSED) {
            throw new InvalidArgumentException('Đợt của đề tài đã kết thúc, không thể thực hiện thao tác này.');
        }
    }
}
