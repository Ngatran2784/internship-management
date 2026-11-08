<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\Topic;
use App\Models\TopicApproval;
use InvalidArgumentException;
use Throwable;

/**
 * TopicApprovalController - Gửi duyệt, xét duyệt (duyệt / từ chối) đề tài và xem lịch sử xét duyệt
 *
 * - Gửi duyệt: người đề xuất (hoặc admin, phòng đào tạo) chuyển đề tài draft/rejected sang pending.
 * - Xét duyệt: chỉ admin, phòng đào tạo, trưởng bộ môn. Từ chối bắt buộc có lý do.
 * - Mọi thao tác ghi dữ liệu đều kiểm tra CSRF. View chưa có thì tự trả JSON như TopicController.
 */
class TopicApprovalController extends Controller
{
    /**
     * GET /topics/pending - Danh sách đề tài chờ duyệt (chỉ vai trò được xét duyệt)
     */
    public function pending(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (!TopicApproval::canReview(AuthController::roles())) {
            $this->fail('Bạn không có quyền xét duyệt đề tài.', 403, '/topics');
            return;
        }

        $filters = [
            'period_id' => filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'type'      => is_string($_GET['type'] ?? null) ? trim($_GET['type']) : null,
            'keyword'   => is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : null,
            'status'    => Topic::STATUS_PENDING,
        ];

        try {
            $topics  = Topic::all($filters);
            $periods = InternshipPeriod::all();
        } catch (Throwable $e) {
            error_log('TopicApprovalController pending error: ' . $e->getMessage());
            $topics  = [];
            $periods = [];
            AuthController::setFlash('error', 'Không thể tải danh sách chờ duyệt. Vui lòng thử lại sau.');
        }

        $this->respond('topics/pending', [
            'topics'       => $topics,
            'periods'      => $periods,
            'filters'      => $filters,
            'statusLabels' => Topic::STATUS_LABELS,
            'typeLabels'   => Topic::TYPE_LABELS,
            'statusBadges' => Topic::STATUS_BADGES,
            'typeBadges'   => Topic::TYPE_BADGES,
            'success'      => AuthController::pullFlash('success'),
            'error'        => AuthController::pullFlash('error'),
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET /topics/history?id={id} - Lịch sử xét duyệt của một đề tài
     * Xem được: người đề xuất, admin, phòng đào tạo, trưởng bộ môn.
     */
    public function history(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $id    = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $topic = ($id !== false && $id !== null && $id > 0) ? Topic::findById($id) : null;

        if ($topic === null) {
            $this->fail('Đề tài không tồn tại trong hệ thống.', 404, '/topics');
            return;
        }

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();

        if ((int)$topic['proposer_id'] !== $userId && !TopicApproval::canReview($userRoles)) {
            $this->fail('Bạn không có quyền xem lịch sử xét duyệt của đề tài này.', 403, '/topics');
            return;
        }

        $this->respond('topics/history', [
            'topic'          => $topic,
            'approvals'      => TopicApproval::history((int)$topic['id']),
            'decisionLabels' => TopicApproval::DECISION_LABELS,
            'statusLabels'   => Topic::STATUS_LABELS,
            'statusBadges'   => Topic::STATUS_BADGES,
            'success'        => AuthController::pullFlash('success'),
            'error'          => AuthController::pullFlash('error'),
            'csrfToken'      => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /topics/submit - Gửi duyệt đề tài (draft/rejected -> pending)
     * Tham số: id
     */
    public function submit(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (!$this->checkPostAndCsrf('/topics')) {
            return;
        }

        $topic = $this->findTopicFromPost('/topics');
        if ($topic === null) {
            return;
        }

        $id   = (int)$topic['id'];
        $back = "/topics/detail?id={$id}";

        if (!TopicApproval::canSubmit($topic, (int)($_SESSION['user_id'] ?? 0), AuthController::roles())) {
            $this->fail('Bạn không thể gửi duyệt đề tài này (không đủ quyền hoặc sai trạng thái).', 403, $back);
            return;
        }

        try {
            TopicApproval::submit($id);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422, $back);
            return;
        } catch (Throwable $e) {
            error_log('TopicApprovalController submit error: ' . $e->getMessage());
            $this->fail('Lỗi hệ thống khi gửi duyệt đề tài.', 500, $back);
            return;
        }

        $this->succeed('Đã gửi đề tài đi xét duyệt.', $back, ['id' => $id, 'status' => Topic::STATUS_PENDING]);
    }

    /**
     * POST /topics/approve - Duyệt đề tài (pending -> approved)
     * Tham số: id, comments (tùy chọn)
     */
    public function approve(): void
    {
        $this->review(TopicApproval::DECISION_APPROVED);
    }

    /**
     * POST /topics/reject - Từ chối đề tài (pending -> rejected)
     * Tham số: id, comments (bắt buộc: lý do từ chối)
     */
    public function reject(): void
    {
        $this->review(TopicApproval::DECISION_REJECTED);
    }

    // ------------------------------------------------------------------
    // Hàm hỗ trợ nội bộ
    // ------------------------------------------------------------------

    private function review(string $decision): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (!$this->checkPostAndCsrf('/topics/pending')) {
            return;
        }

        if (!TopicApproval::canReview(AuthController::roles())) {
            $this->fail('Bạn không có quyền xét duyệt đề tài.', 403, '/topics');
            return;
        }

        $topic = $this->findTopicFromPost('/topics/pending');
        if ($topic === null) {
            return;
        }

        $id       = (int)$topic['id'];
        $comments = is_string($_POST['comments'] ?? null) ? $_POST['comments'] : null;

        try {
            TopicApproval::review($id, (int)($_SESSION['user_id'] ?? 0), $decision, $comments);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422, '/topics/pending');
            return;
        } catch (Throwable $e) {
            error_log('TopicApprovalController review error: ' . $e->getMessage());
            $this->fail('Lỗi hệ thống khi xét duyệt đề tài.', 500, '/topics/pending');
            return;
        }

        $message = $decision === TopicApproval::DECISION_APPROVED
            ? 'Đã duyệt đề tài.'
            : 'Đã từ chối đề tài.';

        $this->succeed($message, '/topics/pending', [
            'id'     => $id,
            'status' => $decision === TopicApproval::DECISION_APPROVED ? Topic::STATUS_APPROVED : Topic::STATUS_REJECTED,
        ]);
    }

    /** Yêu cầu POST và đúng CSRF; trả false nếu đã phản hồi lỗi */
    private function checkPostAndCsrf(string $backPath): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go($backPath);
        }

        if (!AuthController::verifyCsrf()) {
            $this->fail('Phiên làm việc hết hạn hoặc token CSRF không hợp lệ. Vui lòng thử lại.', 403, $backPath);
            return false;
        }

        return true;
    }

    /** Lấy đề tài theo `id` trong POST; trả null nếu đã phản hồi lỗi */
    private function findTopicFromPost(string $backPath): ?array
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            $this->fail('Mã định danh đề tài không hợp lệ.', 400, $backPath);
            return null;
        }

        $topic = Topic::findById($id);
        if ($topic === null) {
            $this->fail('Đề tài không tồn tại trong hệ thống.', 404, $backPath);
            return null;
        }

        return $topic;
    }

    /** Báo lỗi: JSON nếu client yêu cầu, ngược lại flash + chuyển hướng */
    private function fail(string $message, int $status, string $redirect): void
    {
        if ($this->wantsJson()) {
            $this->sendJson(['ok' => false, 'error' => $message], $status);
            return;
        }

        AuthController::setFlash('error', $message);
        AuthController::go($redirect);
    }

    /** Báo thành công: JSON nếu client yêu cầu, ngược lại flash + chuyển hướng */
    private function succeed(string $message, string $redirect, array $extra = []): void
    {
        if ($this->wantsJson()) {
            $this->sendJson(array_merge(['ok' => true, 'message' => $message], $extra));
            return;
        }

        AuthController::setFlash('success', $message);
        AuthController::go($redirect);
    }

    private function respond(string $view, array $data, int $statusCode = 200): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($viewPath)) {
            $this->render($view, $data);
            return;
        }

        unset($data['csrfToken']);
        $this->sendJson($data, $statusCode);
    }

    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }

    private function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
