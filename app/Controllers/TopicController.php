<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Topic;
use App\Models\InternshipPeriod;
use App\Models\TopicApproval;
use InvalidArgumentException;
use Throwable;

/**
 * TopicController - Phụ trách nghiệp vụ Quản lý đề tài thực tập & đồ án tốt nghiệp
 *
 * Chức năng:
 * - Quản lý danh sách, chi tiết, tạo mới, lưu trữ, hiển thị form sửa, cập nhật đề tài.
 * - Kiểm tra phiên đăng nhập, phân quyền người dùng và bảo mật CSRF.
 * - Kiến trúc View-agnostic: nếu View tương ứng chưa được Frontend tạo xong,
 *   tự động phản hồi JSON UTF-8 đầy đủ dữ liệu để phục vụ kiểm thử Backend độc lập.
 *
 * Lưu ý: Nghiệp vụ phê duyệt đề tài (Hội đồng/Trưởng bộ môn/PĐT duyệt hoặc từ chối)
 * sẽ do phân hệ Phê duyệt đề tài phụ trách triển khai.
 */
class TopicController extends Controller
{
    /**
     * GET /topics - Danh sách đề tài có hỗ trợ bộ lọc và tìm kiếm
     */
    public function index(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();

        $filters = [
            'period_id'   => filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'status'      => is_string($_GET['status'] ?? null) ? trim($_GET['status']) : null,
            'type'        => is_string($_GET['type'] ?? null) ? trim($_GET['type']) : null,
            'keyword'     => is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : null,
            'proposer_id' => null,
        ];

        // Phân quyền dữ liệu theo vai trò
        $isAdminOrTraining = in_array('admin', $userRoles, true) || in_array('training_dept', $userRoles, true);
        $isStudent         = in_array('student', $userRoles, true) && !$isAdminOrTraining;
        $isProposerRole     = (bool)array_intersect(['department_head', 'lecturer', 'enterprise'], $userRoles);

        if ($isStudent && !$isProposerRole) {
            $filters['status'] = Topic::STATUS_APPROVED;
            $filters['student_visible'] = true;
        } elseif (!$isAdminOrTraining) {
            if ($isProposerRole) {
                $filters['visible_to'] = $userId;
            } else {
                $filters['deny_visibility'] = true;
            }
        }

        try {
            $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
            $pagination = Topic::paginate($filters, $page && $page > 0 ? $page : 1);
            $topics = $pagination['items'];
            $periods = $isStudent && !$isProposerRole
                ? InternshipPeriod::all(['status' => InternshipPeriod::STATUS_ACTIVE])
                : InternshipPeriod::all();
        } catch (Throwable $e) {
            error_log('TopicController index error: ' . $e->getMessage());
            $topics  = [];
            $periods = [];
            $pagination = ['total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => 15];
            AuthController::setFlash('error', 'Không thể tải danh sách đề tài. Vui lòng thử lại sau.');
        }

        $this->respond('topics/index', [
            'topics'             => $topics,
            'periods'            => $periods,
            'filters'            => $filters,
            'pagination'         => $pagination,
            'statusLabels'       => Topic::STATUS_LABELS,
            'typeLabels'         => Topic::TYPE_LABELS,
            'statusBadges'       => Topic::STATUS_BADGES,
            'typeBadges'         => Topic::TYPE_BADGES,
            'isAdminOrTraining'  => $isAdminOrTraining,
            'currentUserId'      => $userId,
            'currentUserRoles'   => $userRoles,
            'success'            => AuthController::pullFlash('success'),
            'error'              => AuthController::pullFlash('error'),
            'csrfToken'          => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET /topics/detail?id={id} - Xem chi tiết đề tài
     */
    public function show(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đề tài không hợp lệ.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Mã định danh đề tài không hợp lệ.'], 400);
                return;
            }
            AuthController::go('/topics');
        }

        $topic = Topic::findById($id);
        if ($topic === null) {
            AuthController::setFlash('error', 'Đề tài không tồn tại trong hệ thống.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Đề tài không tồn tại trong hệ thống.'], 404);
                return;
            }
            AuthController::go('/topics');
        }

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();
        if (!Topic::canView($topic, $userId, $userRoles)) {
            AuthController::setFlash('error', 'Bạn không có quyền xem đề tài này.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền xem đề tài này.'], 403);
                return;
            }
            AuthController::go('/topics');
        }
        $canEdit   = Topic::canEdit($topic, $userId, $userRoles);
        $canSubmit = TopicApproval::canSubmit($topic, $userId, $userRoles)
            && ($topic['period_status'] ?? null) !== InternshipPeriod::STATUS_CLOSED;
        $this->respond('topics/show', [
            'topic'        => $topic,
            'canEdit'      => $canEdit,
            'canSubmit'    => $canSubmit,
            'canReview'    => $canReview,
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
     * GET /topics/create - Hiển thị form đề xuất/thêm đề tài mới
     */
    public function create(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        // Kiểm tra quyền: admin, phòng đào tạo, trưởng bộ môn, giảng viên, doanh nghiệp, sinh viên
        if (!AuthController::hasRole('admin', 'training_dept', 'department_head', 'lecturer', 'enterprise', 'student')) {
            AuthController::setFlash('error', 'Bạn không có quyền tạo hoặc đề xuất đề tài.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền tạo hoặc đề xuất đề tài.'], 403);
                return;
            }
            AuthController::go('/topics');
        }

        $old    = $_SESSION['topic_old'] ?? [];
        $errors = $_SESSION['topic_errors'] ?? [];
        unset($_SESSION['topic_old'], $_SESSION['topic_errors']);

        // Lấy danh sách các đợt đang mở (active) hoặc bản nháp (nếu admin)
        $periods = InternshipPeriod::all(['status' => InternshipPeriod::STATUS_ACTIVE]);
        if (AuthController::hasRole('admin', 'training_dept')) {
            $periods = InternshipPeriod::all();
        }

        $this->respond('topics/create', [
            'periods'      => $periods,
            'old'          => $old,
            'errors'       => $errors,
            'statusLabels' => Topic::STATUS_LABELS,
            'typeLabels'   => Topic::TYPE_LABELS,
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /topics/create hoặc POST /topics - Xử lý lưu đề tài mới
     */
    public function store(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/topics/create');
        }

        if (!AuthController::hasRole('admin', 'training_dept', 'department_head', 'lecturer', 'enterprise', 'student')) {
            AuthController::setFlash('error', 'Bạn không có quyền tạo hoặc đề xuất đề tài.');
            AuthController::go('/topics');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc hết hạn hoặc token CSRF không hợp lệ. Vui lòng thử lại.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Token CSRF không hợp lệ.'], 403);
                return;
            }
            AuthController::go('/topics/create');
        }

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();

        // Lấy và chuẩn hóa dữ liệu gửi lên
        $code        = is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '';
        $name        = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : null;
        $type        = is_string($_POST['type'] ?? null) ? trim($_POST['type']) : '';
        $periodId    = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $maxStudents = filter_var($_POST['max_students'] ?? null, FILTER_VALIDATE_INT) ?: 1;

        // Người đề xuất mặc định là tài khoản đang đăng nhập
        $proposerId = $userId;
        if (in_array('admin', $userRoles, true) && !empty($_POST['proposer_id'])) {
            $customProposer = filter_var($_POST['proposer_id'], FILTER_VALIDATE_INT);
            if ($customProposer && $customProposer > 0) {
                $proposerId = $customProposer;
            }
        }

        $data = [
            'code'         => $code,
            'name'         => $name,
            'description'  => $description,
            'type'         => $type,
            'period_id'    => $periodId,
            'proposer_id'  => $proposerId,
            'max_students' => $maxStudents,
            'status'       => Topic::STATUS_DRAFT,
        ];

        // Kiểm tra hợp lệ dữ liệu
        $errors = Topic::validate($data, false);
        if (!empty($errors)) {
            $_SESSION['topic_old']    = $_POST;
            $_SESSION['topic_errors'] = $errors;
            AuthController::setFlash('error', reset($errors));

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => $errors], 422);
                return;
            }

            AuthController::go('/topics/create');
        }

        try {
            $newId = Topic::create($data);
            AuthController::setFlash('success', "Thêm đề tài '{$name}' (Mã: {$code}) thành công.");

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => "Thêm đề tài '{$name}' thành công.",
                    'id'      => $newId,
                ], 201);
                return;
            }

            AuthController::go('/topics');
        } catch (InvalidArgumentException $e) {
            $_SESSION['topic_old']    = $_POST;
            $_SESSION['topic_errors'] = ['general' => $e->getMessage()];
            AuthController::setFlash('error', $e->getMessage());

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => $e->getMessage()], 422);
                return;
            }

            AuthController::go('/topics/create');
        } catch (Throwable $e) {
            error_log('TopicController store error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Lỗi hệ thống khi lưu đề tài. Vui lòng thử lại sau.');

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Lỗi hệ thống khi lưu đề tài.'], 500);
                return;
            }

            AuthController::go('/topics/create');
        }
    }

    /**
     * GET /topics/edit?id={id} - Hiển thị form chỉnh sửa đề tài
     */
    public function edit(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đề tài không hợp lệ.');
            AuthController::go('/topics');
        }

        $topic = Topic::findById($id);
        if ($topic === null) {
            AuthController::setFlash('error', 'Đề tài không tồn tại trong hệ thống.');
            AuthController::go('/topics');
        }

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();

        // Kiểm tra quyền chỉnh sửa
        if (!Topic::canEdit($topic, $userId, $userRoles)) {
            AuthController::setFlash('error', 'Bạn không có quyền chỉnh sửa đề tài này hoặc đề tài đã được duyệt.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền chỉnh sửa đề tài này.'], 403);
                return;
            }
            AuthController::go('/topics');
        }

        $old    = $_SESSION['topic_old'] ?? [];
        $errors = $_SESSION['topic_errors'] ?? [];
        unset($_SESSION['topic_old'], $_SESSION['topic_errors']);

        $periods = InternshipPeriod::all();

        $this->respond('topics/edit', [
            'topic'        => $topic,
            'periods'      => $periods,
            'old'          => $old,
            'errors'       => $errors,
            'statusLabels' => Topic::STATUS_LABELS,
            'typeLabels'   => Topic::TYPE_LABELS,
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /topics/update hoặc POST /topics/edit - Cập nhật đề tài
     */
    public function update(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/topics');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc hết hạn hoặc token CSRF không hợp lệ. Vui lòng thử lại.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Token CSRF không hợp lệ.'], 403);
                return;
            }
            AuthController::go('/topics');
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đề tài không hợp lệ.');
            AuthController::go('/topics');
        }

        $topic = Topic::findById($id);
        if ($topic === null) {
            AuthController::setFlash('error', 'Đề tài không tồn tại trong hệ thống.');
            AuthController::go('/topics');
        }

        $userId    = (int)($_SESSION['user_id'] ?? 0);
        $userRoles = AuthController::roles();

        // Kiểm tra quyền chỉnh sửa
        if (!Topic::canEdit($topic, $userId, $userRoles)) {
            AuthController::setFlash('error', 'Bạn không có quyền chỉnh sửa đề tài này hoặc đề tài đã được duyệt.');
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền chỉnh sửa đề tài này.'], 403);
                return;
            }
            AuthController::go('/topics');
        }

        $code        = is_string($_POST['code'] ?? null) ? trim($_POST['code']) : $topic['code'];
        $name        = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : $topic['name'];
        $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : $topic['description'];
        $type        = is_string($_POST['type'] ?? null) ? trim($_POST['type']) : $topic['type'];
        $periodId    = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT) ?: (int)$topic['period_id'];
        $maxStudents = filter_var($_POST['max_students'] ?? null, FILTER_VALIDATE_INT) ?: (int)$topic['max_students'];
        
        $data = [
            'code'         => $code,
            'name'         => $name,
            'description'  => $description,
            'type'         => $type,
            'period_id'    => $periodId,
            'max_students' => $maxStudents,
            'status'       => $topic['status'],
        ];

        $errors = Topic::validate($data, true, $id);
        if (!empty($errors)) {
            $_SESSION['topic_old']    = $_POST;
            $_SESSION['topic_errors'] = $errors;
            AuthController::setFlash('error', reset($errors));

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => $errors], 422);
                return;
            }

            AuthController::go("/topics/edit?id={$id}");
        }

        try {
            Topic::update($id, $data);
            AuthController::setFlash('success', "Cập nhật đề tài '{$name}' thành công.");

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => "Cập nhật đề tài '{$name}' thành công.",
                    'id'      => $id,
                ]);
                return;
            }

            AuthController::go('/topics');
        } catch (InvalidArgumentException $e) {
            $_SESSION['topic_old']    = $_POST;
            $_SESSION['topic_errors'] = ['general' => $e->getMessage()];
            AuthController::setFlash('error', $e->getMessage());

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => $e->getMessage()], 422);
                return;
            }

            AuthController::go("/topics/edit?id={$id}");
        } catch (Throwable $e) {
            error_log('TopicController update error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Lỗi hệ thống khi cập nhật đề tài. Vui lòng thử lại sau.');

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => 'Lỗi hệ thống khi cập nhật đề tài.'], 500);
                return;
            }

            AuthController::go("/topics/edit?id={$id}");
        }
    }

    /**
     * Render View nếu tệp view đã tồn tại; nếu View chưa được tạo (Frontend đang làm song song),
     * tự động trả về phản hồi JSON để Backend có thể kiểm thử độc lập 100%.
     */
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

    /**
     * Kiểm tra client có yêu cầu JSON (API/AJAX) không
     */
    private function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }

    /**
     * Xuất dữ liệu JSON có định dạng UTF-8
     */
    private function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
