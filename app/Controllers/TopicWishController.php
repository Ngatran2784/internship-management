<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\TopicWish;
use InvalidArgumentException;
use Throwable;

/**
 * TopicWishController - Điều khiển quy trình đăng ký và sắp xếp nguyện vọng đề tài của sinh viên
 *
 * Chức năng:
 * - GET  /wishes         : Xem danh sách nguyện vọng đã đăng ký của sinh viên
 * - GET  /wishes/create  : Giao diện form chọn và sắp xếp tối đa 3 nguyện vọng (NV1, NV2, NV3)
 * - POST /wishes/save    : Lưu / cập nhật nguyện vọng trong đợt (kiểm tra CSRF, hạn, trùng lặp)
 * - POST /wishes/delete  : Hủy đăng ký nguyện vọng trong đợt còn mở
 */
class TopicWishController extends Controller
{
    /**
     * GET /wishes - Xem danh sách nguyện vọng đã đăng ký của sinh viên
     */
    public function index(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $studentId = (int)($_SESSION['user_id'] ?? 0);
        $roles = AuthController::roles();

        if (!TopicWish::canRegister($roles)) {
            $this->fail('Chức năng đăng ký nguyện vọng chỉ dành cho Sinh viên.', 403, '/dashboard');
            return;
        }

        try {
            // Lấy danh sách nguyện vọng của sinh viên
            $wishes = TopicWish::getByStudent($studentId);

            // Lấy danh sách các đợt đang mở tiếp nhận đăng ký
            $activePeriods = InternshipPeriod::all(['status' => InternshipPeriod::STATUS_ACTIVE]);
            $openPeriods = [];
            foreach ($activePeriods as $p) {
                if (TopicWish::isPeriodOpenForRegistration((int)$p['id'])) {
                    $openPeriods[] = $p;
                }
            }
        } catch (Throwable $e) {
            error_log('TopicWishController index error: ' . $e->getMessage());
            $wishes = [];
            $openPeriods = [];
            AuthController::setFlash('error', 'Không thể tải danh sách nguyện vọng. Vui lòng thử lại sau.');
        }

        $this->respond('topic_wishes/index', [
            'wishes'       => $wishes,
            'openPeriods'  => $openPeriods,
            'statusLabels' => TopicWish::STATUS_LABELS,
            'statusBadges' => TopicWish::STATUS_BADGES,
            'success'      => AuthController::pullFlash('success'),
            'error'        => AuthController::pullFlash('error'),
        ]);
    }

    /**
     * GET /wishes/create - Giao diện đăng ký / cập nhật nguyện vọng cho một đợt
     */
    public function create(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $studentId = (int)($_SESSION['user_id'] ?? 0);
        $roles = AuthController::roles();

        if (!TopicWish::canRegister($roles)) {
            $this->fail('Chức năng đăng ký nguyện vọng chỉ dành cho Sinh viên.', 403, '/dashboard');
            return;
        }
        $periodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

        // Nếu không truyền period_id, tự động lấy đợt active đầu tiên
        if ($periodId <= 0) {
            $activePeriods = InternshipPeriod::all(['status' => InternshipPeriod::STATUS_ACTIVE]);
            foreach ($activePeriods as $p) {
                if (TopicWish::isPeriodOpenForRegistration((int)$p['id'])) {
                    $periodId = (int)$p['id'];
                    break;
                }
            }
        }

        if ($periodId <= 0) {
            AuthController::setFlash('error', 'Hiện không có đợt thực tập/đồ án nào đang mở tiếp nhận đăng ký.');
            $this->redirect('/wishes');
            return;
        }

        if (!TopicWish::isPeriodOpenForRegistration($periodId)) {
            AuthController::setFlash('error', 'Đợt này hiện không mở hoặc đã kết thúc thời hạn đăng ký nguyện vọng.');
            $this->redirect('/wishes');
            return;
        }

        $period = InternshipPeriod::findById($periodId);
        if (!$period) {
            $this->fail('Đợt thực tập/đồ án không tồn tại.', 404, '/wishes');
            return;
        }

        // Lấy danh sách đề tài đã duyệt thuộc đợt này
        $approvedTopics = TopicWish::getApprovedTopicsForPeriod($periodId);

        // Lấy danh sách nguyện vọng hiện tại của sinh viên nếu đã từng đăng ký
        $currentWishes = TopicWish::getByStudentAndPeriod($studentId, $periodId);

        $this->respond('topic_wishes/create', [
            'period'         => $period,
            'approvedTopics' => $approvedTopics,
            'currentWishes'  => $currentWishes,
            'csrf_token'     => AuthController::csrfToken(),
            'error'          => AuthController::pullFlash('error'),
            'success'        => AuthController::pullFlash('success'),
        ]);
    }

    /**
     * POST /wishes/save - Lưu hoặc cập nhật danh sách nguyện vọng của sinh viên
     */
    public function save(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (isset($_POST['csrf_token'])) {
            $_POST['_csrf'] = $_POST['csrf_token'];
        }
        if (!AuthController::verifyCsrf()) {
            $this->fail('Phiên làm việc hết hạn hoặc token CSRF không hợp lệ.', 403, '/wishes');
            return;
        }

        $studentId = (int)($_SESSION['user_id'] ?? 0);
        $roles = AuthController::roles();

        if (!TopicWish::canRegister($roles)) {
            $this->fail('Bạn không có quyền thực hiện thao tác này.', 403, '/dashboard');
            return;
        }
        $periodId = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

        if ($periodId <= 0) {
            AuthController::setFlash('error', 'Vui lòng chọn đợt thực tập / đồ án hợp lệ.');
            $this->redirect('/wishes');
            return;
        }

        // Thu thập danh sách nguyện vọng từ form
        // Hỗ trợ cả 2 dạng: wish_1, wish_2, wish_3 hoặc mảng wishes[]
        $wishes = [];

        for ($p = 1; $p <= TopicWish::MAX_WISHES; $p++) {
            $topicId = filter_var($_POST["topic_id_{$p}"] ?? null, FILTER_VALIDATE_INT) ?: 0;
            $note = is_string($_POST["note_{$p}"] ?? null) ? trim($_POST["note_{$p}"]) : '';

            if ($topicId > 0) {
                $wishes[] = [
                    'topic_id' => $topicId,
                    'priority' => $p,
                    'note'     => $note,
                ];
            }
        }

        // Nếu người dùng gửi qua dạng mảng wishes
        if (empty($wishes) && isset($_POST['wishes']) && is_array($_POST['wishes'])) {
            foreach ($_POST['wishes'] as $item) {
                $topicId = filter_var($item['topic_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                $priority = filter_var($item['priority'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                $note = is_string($item['note'] ?? null) ? trim($item['note']) : '';

                if ($topicId > 0 && in_array($priority, TopicWish::ALLOWED_PRIORITIES, true)) {
                    $wishes[] = [
                        'topic_id' => $topicId,
                        'priority' => $priority,
                        'note'     => $note,
                    ];
                }
            }
        }

        if (empty($wishes)) {
            AuthController::setFlash('error', 'Vui lòng chọn ít nhất 1 nguyện vọng đề tài (Nguyện vọng 1).');
            $this->redirect('/wishes/create?period_id=' . $periodId);
            return;
        }

        try {
            TopicWish::saveWishes($studentId, $periodId, $wishes);
            AuthController::setFlash('success', 'Đăng ký và lưu danh sách nguyện vọng thành công!');
            $this->redirect('/wishes');
        } catch (InvalidArgumentException $e) {
            AuthController::setFlash('error', $e->getMessage());
            $this->redirect('/wishes/create?period_id=' . $periodId);
        } catch (Throwable $e) {
            error_log('TopicWishController save error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Không thể lưu nguyện vọng do lỗi hệ thống. Vui lòng thử lại sau.');
            $this->redirect('/wishes/create?period_id=' . $periodId);
        }
    }

    /**
     * POST /wishes/delete - Hủy toàn bộ nguyện vọng trong một đợt
     */
    public function delete(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        if (isset($_POST['csrf_token'])) {
            $_POST['_csrf'] = $_POST['csrf_token'];
        }
        if (!AuthController::verifyCsrf()) {
            $this->fail('Phiên làm việc hết hạn hoặc token CSRF không hợp lệ.', 403, '/wishes');
            return;
        }

        $studentId = (int)($_SESSION['user_id'] ?? 0);
        $roles = AuthController::roles();

        if (!TopicWish::canRegister($roles)) {
            $this->fail('Bạn không có quyền thực hiện thao tác này.', 403, '/dashboard');
            return;
        }
        $periodId = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;

        if ($periodId <= 0) {
            AuthController::setFlash('error', 'Đợt thực tập / đồ án không hợp lệ.');
            $this->redirect('/wishes');
            return;
        }

        try {
            TopicWish::deleteByStudentAndPeriod($studentId, $periodId);
            AuthController::setFlash('success', 'Đã hủy toàn bộ nguyện vọng đăng ký cho đợt này.');
        } catch (InvalidArgumentException $e) {
            AuthController::setFlash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('TopicWishController delete error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Không thể hủy nguyện vọng do lỗi hệ thống. Vui lòng thử lại sau.');
        }

        $this->redirect('/wishes');
    }

    /**
     * Phản hồi chuẩn: View nếu có, ngược lại trả JSON
     */
    protected function respond(string $view, array $data, int $status = 200): void
    {
        http_response_code($status);
        $viewFile = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (file_exists($viewFile)) {
            extract($data, EXTR_SKIP);
            require $viewFile;
            return;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => $status,
            'view'   => $view,
            'data'   => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Xử lý lỗi chuẩn
     */
    protected function fail(string $message, int $status, string $redirect): void
    {
        AuthController::startSession();
        AuthController::setFlash('error', $message);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (str_contains($accept, 'application/json')) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->redirect($redirect);
    }
}
