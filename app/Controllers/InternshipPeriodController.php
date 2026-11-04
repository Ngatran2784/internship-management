<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use InvalidArgumentException;
use Throwable;

/**
 * InternshipPeriodController - Quản lý các đợt thực tập và đồ án tốt nghiệp
 *
 * Quyền hạn truy cập:
 * - Xem danh sách, chi tiết, tạo mới, chỉnh sửa, đổi trạng thái: admin, training_dept
 * - Sinh viên và các vai trò khác không có quyền quản lý đợt (bị từ chối HTTP 403)
 */
class InternshipPeriodController extends Controller
{
    /**
     * GET /periods - Danh sách các đợt thực tập và đồ án
     */
    public function index(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        $filters = [
            'type'    => is_string($_GET['type'] ?? null) ? trim($_GET['type']) : '',
            'status'  => is_string($_GET['status'] ?? null) ? trim($_GET['status']) : '',
            'keyword' => is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : '',
        ];

        try {
            $periods = InternshipPeriod::all($filters);
        } catch (Throwable $e) {
            error_log('InternshipPeriodController index error: ' . $e->getMessage());
            $periods = [];
            AuthController::setFlash('error', 'Không thể tải danh sách đợt. Vui lòng thử lại sau.');
        }

        $this->respond('periods/index', [
            'periods'      => $periods,
            'filters'      => $filters,
            'typeLabels'   => InternshipPeriod::TYPE_LABELS,
            'statusLabels' => InternshipPeriod::STATUS_LABELS,
            'success'      => AuthController::pullFlash('success'),
            'error'        => AuthController::pullFlash('error'),
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET /periods/detail?id={id} - Xem chi tiết một đợt
     */
    public function show(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đợt không hợp lệ.');
            AuthController::go('/periods');
        }

        $period = InternshipPeriod::findById($id);
        if ($period === null) {
            AuthController::setFlash('error', 'Đợt thực tập/đồ án không tồn tại.');
            AuthController::go('/periods');
        }

        $this->respond('periods/show', [
            'period'       => $period,
            'typeLabels'   => InternshipPeriod::TYPE_LABELS,
            'statusLabels' => InternshipPeriod::STATUS_LABELS,
            'success'      => AuthController::pullFlash('success'),
            'error'        => AuthController::pullFlash('error'),
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * GET /periods/create - Hiển thị giao diện tạo đợt mới
     */
    public function create(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        AuthController::startSession();
        $old = $_SESSION['period_old'] ?? [];
        $errors = $_SESSION['period_errors'] ?? [];
        unset($_SESSION['period_old'], $_SESSION['period_errors']);

        $this->respond('periods/create', [
            'old'          => $old,
            'errors'       => $errors,
            'typeLabels'   => InternshipPeriod::TYPE_LABELS,
            'statusLabels' => InternshipPeriod::STATUS_LABELS,
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /periods hoặc POST /periods/create - Lưu đợt mới vào cơ sở dữ liệu
     */
    public function store(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/periods/create');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc không hợp lệ (lỗi CSRF). Vui lòng thử lại.');
            AuthController::go('/periods/create');
        }

        $data = [
            'code'        => is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '',
            'name'        => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
            'type'        => is_string($_POST['type'] ?? null) ? trim($_POST['type']) : '',
            'start_date'  => is_string($_POST['start_date'] ?? null) ? trim($_POST['start_date']) : '',
            'end_date'    => is_string($_POST['end_date'] ?? null) ? trim($_POST['end_date']) : '',
            'status'      => is_string($_POST['status'] ?? null) ? trim($_POST['status']) : InternshipPeriod::STATUS_DRAFT,
            'description' => is_string($_POST['description'] ?? null) ? trim($_POST['description']) : null,
        ];

        $errors = InternshipPeriod::validate($data);

        if (!empty($errors)) {
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => $errors], 422);
                return;
            }

            AuthController::startSession();
            $_SESSION['period_old'] = $data;
            $_SESSION['period_errors'] = $errors;
            AuthController::setFlash('error', $errors[0]);
            AuthController::go('/periods/create');
        }

        try {
            $newId = InternshipPeriod::create($data);
            AuthController::setFlash('success', "Tạo mới đợt '{$data['name']}' thành công.");

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => "Tạo mới đợt thành công.",
                    'id'      => $newId,
                ], 201);
                return;
            }

            AuthController::go('/periods');
        } catch (Throwable $e) {
            error_log('InternshipPeriodController store error: ' . $e->getMessage());
            $errorMsg = 'Lỗi hệ thống khi lưu đợt: ' . $e->getMessage();

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => [$errorMsg]], 500);
                return;
            }

            AuthController::startSession();
            $_SESSION['period_old'] = $data;
            $_SESSION['period_errors'] = [$errorMsg];
            AuthController::setFlash('error', $errorMsg);
            AuthController::go('/periods/create');
        }
    }

    /**
     * GET /periods/edit?id={id} - Hiển thị form chỉnh sửa đợt
     */
    public function edit(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đợt không hợp lệ.');
            AuthController::go('/periods');
        }

        $period = InternshipPeriod::findById($id);
        if ($period === null) {
            AuthController::setFlash('error', 'Đợt thực tập/đồ án không tồn tại.');
            AuthController::go('/periods');
        }

        AuthController::startSession();
        $old = $_SESSION['period_old'] ?? $period;
        $errors = $_SESSION['period_errors'] ?? [];
        unset($_SESSION['period_old'], $_SESSION['period_errors']);

        $this->respond('periods/edit', [
            'period'       => $period,
            'old'          => $old,
            'errors'       => $errors,
            'typeLabels'   => InternshipPeriod::TYPE_LABELS,
            'statusLabels' => InternshipPeriod::STATUS_LABELS,
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /periods/update hoặc POST /periods/edit - Lưu thông tin cập nhật của đợt
     */
    public function update(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/periods');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc không hợp lệ (lỗi CSRF). Vui lòng thử lại.');
            AuthController::go('/periods');
        }

        $id = filter_var($_POST['id'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã định danh đợt không hợp lệ.');
            AuthController::go('/periods');
        }

        $existing = InternshipPeriod::findById($id);
        if ($existing === null) {
            AuthController::setFlash('error', 'Đợt thực tập/đồ án không tồn tại.');
            AuthController::go('/periods');
        }

        $data = [
            'code'        => is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '',
            'name'        => is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '',
            'type'        => is_string($_POST['type'] ?? null) ? trim($_POST['type']) : '',
            'start_date'  => is_string($_POST['start_date'] ?? null) ? trim($_POST['start_date']) : '',
            'end_date'    => is_string($_POST['end_date'] ?? null) ? trim($_POST['end_date']) : '',
            'status'      => is_string($_POST['status'] ?? null) ? trim($_POST['status']) : $existing['status'],
            'description' => is_string($_POST['description'] ?? null) ? trim($_POST['description']) : null,
        ];

        $errors = InternshipPeriod::validate($data, $id);

        if (!empty($errors)) {
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => $errors], 422);
                return;
            }

            AuthController::startSession();
            $_SESSION['period_old'] = array_merge($existing, $data);
            $_SESSION['period_errors'] = $errors;
            AuthController::setFlash('error', $errors[0]);
            AuthController::go("/periods/edit?id={$id}");
        }

        try {
            InternshipPeriod::update($id, $data);
            AuthController::setFlash('success', "Cập nhật đợt '{$data['name']}' thành công.");

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => 'Cập nhật đợt thành công.',
                    'period'  => InternshipPeriod::findById($id),
                ]);
                return;
            }

            AuthController::go("/periods/detail?id={$id}");
        } catch (Throwable $e) {
            error_log('InternshipPeriodController update error: ' . $e->getMessage());
            $errorMsg = 'Lỗi hệ thống khi cập nhật: ' . $e->getMessage();

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'errors' => [$errorMsg]], 500);
                return;
            }

            AuthController::startSession();
            $_SESSION['period_old'] = array_merge($existing, $data);
            $_SESSION['period_errors'] = [$errorMsg];
            AuthController::setFlash('error', $errorMsg);
            AuthController::go("/periods/edit?id={$id}");
        }
    }

    /**
     * POST /periods/status - Chuyển đổi trạng thái đợt (draft <-> active <-> closed)
     * Tuyệt đối KHÔNG hỗ trợ GET để thay đổi trạng thái
     */
    public function updateStatus(): void
    {
        AuthController::requireRole('admin', 'training_dept');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            AuthController::go('/periods');
        }

        if (!AuthController::verifyCsrf()) {
            AuthController::setFlash('error', 'Phiên làm việc không hợp lệ (lỗi CSRF). Vui lòng thử lại.');
            AuthController::go('/periods');
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = is_string($_POST['status'] ?? null) ? trim($_POST['status']) : '';

        if ($id === false || $id === null || $id <= 0) {
            AuthController::setFlash('error', 'Mã đợt không hợp lệ.');
            AuthController::go('/periods');
        }

        $period = InternshipPeriod::findById($id);
        if ($period === null) {
            AuthController::setFlash('error', 'Đợt thực tập/đồ án không tồn tại.');
            AuthController::go('/periods');
        }

        try {
            InternshipPeriod::updateStatus($id, $status);
            $statusName = InternshipPeriod::STATUS_LABELS[$status] ?? $status;
            AuthController::setFlash('success', "Đã chuyển trạng thái đợt sang '{$statusName}'.");

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => "Đã chuyển trạng thái sang '{$statusName}'.",
                    'period'  => InternshipPeriod::findById($id),
                ]);
                return;
            }

            // Chuyển hướng về trang chi tiết nếu có tham chiếu, hoặc về danh sách
            $redirectUrl = isset($_POST['redirect']) && $_POST['redirect'] === 'detail'
                ? "/periods/detail?id={$id}"
                : '/periods';

            AuthController::go($redirectUrl);
        } catch (InvalidArgumentException $e) {
            AuthController::setFlash('error', $e->getMessage());

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => $e->getMessage()], 422);
                return;
            }

            AuthController::go("/periods/detail?id={$id}");
        } catch (Throwable $e) {
            error_log('InternshipPeriodController updateStatus error: ' . $e->getMessage());
            $errorMsg = 'Lỗi hệ thống khi cập nhật trạng thái.';
            AuthController::setFlash('error', $errorMsg);

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => false, 'error' => $errorMsg], 500);
                return;
            }

            AuthController::go('/periods');
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
