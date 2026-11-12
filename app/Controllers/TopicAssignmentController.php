<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\TopicAssignment;
use App\Services\TopicMatchingService;
use InvalidArgumentException;
use Throwable;

/**
 * TopicAssignmentController - Điều khiển phân công đề tài và giảng viên
 *
 * Nhiệm vụ thuộc phạm vi:
 * - POST /assignments/auto-match : Chạy thuật toán đối sánh tự động theo nguyện vọng sinh viên (dự thảo)
 * - GET  /assignments/preview    : Xem trước kết quả mô phỏng (dry-run) mà không ghi CSDL
 * - GET  /assignments            : Danh sách phân công theo đợt (sẵn sàng tích hợp cho Quỳnh & Trúc)
 */
class TopicAssignmentController extends Controller
{
    /**
     * Kiểm tra quyền quản lý / phân công đề tài (Admin, Phòng đào tạo, Trưởng bộ môn, Cán bộ khoa)
     */
    public static function canManage(array $roles): bool
    {
        $allowed = ['admin', 'training_dept', 'department_head', 'faculty_staff'];
        foreach ($roles as $r) {
            if (in_array($r, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * POST /assignments/auto-match - Kích hoạt thuật toán đối sánh nguyện vọng tự động
     */
    public function autoMatch(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền thực hiện phân công đề tài.', 403, '/dashboard');
            return;
        }

        // Kiểm tra CSRF token
        $csrfToken = $_POST['_csrf'] ?? '';
        if (!AuthController::verifyCsrf($csrfToken)) {
            $this->fail('Mã bảo mật (CSRF) không hợp lệ hoặc phiên làm việc đã hết hạn.', 403, '/dashboard');
            return;
        }

        $periodId = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$periodId) {
            $this->fail('Vui lòng chọn đợt thực tập / đồ án cần phân công.', 422, '/dashboard');
            return;
        }

        $maxPerLecturer = filter_var($_POST['max_per_lecturer'] ?? 5, FILTER_VALIDATE_INT);
        $dryRun = !empty($_POST['dry_run']);
        $assignedBy = (int)($_SESSION['user_id'] ?? 0);

        try {
            $matchingService = new TopicMatchingService();
            $result = $matchingService->matchPeriod((int)$periodId, [
                'max_per_lecturer' => $maxPerLecturer ?: TopicMatchingService::DEFAULT_MAX_STUDENTS_PER_LECTURER,
                'dry_run'          => $dryRun,
                'assigned_by'      => $assignedBy,
            ]);

            $message = sprintf(
                'Đối sánh tự động thành công: %d sinh viên được phân công (Dự thảo), %d sinh viên chưa phân công.',
                $result['total_assigned'],
                $result['total_unassigned']
            );

            if ($this->wantsJson()) {
                $this->sendJson([
                    'ok'      => true,
                    'message' => $message,
                    'data'    => $result,
                ], 200);
                return;
            }

            AuthController::setFlash('success', $message);
            AuthController::go('/assignments?period_id=' . $periodId);
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422, '/dashboard');
        } catch (Throwable $e) {
            error_log('AutoMatch error: ' . $e->getMessage());
            $this->fail('Không thể thực hiện phân công tự động: ' . $e->getMessage(), 500, '/dashboard');
        }
    }

    /**
     * GET /assignments/preview - Mô phỏng thuật toán đối sánh tự động (Dry-Run, không ghi DB)
     */
    public function preview(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền xem mô phỏng phân công.', 403, '/dashboard');
            return;
        }

        $periodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$periodId) {
            $this->fail('Vui lòng chọn đợt thực tập / đồ án.', 422, '/dashboard');
            return;
        }

        $maxPerLecturer = filter_var($_GET['max_per_lecturer'] ?? 5, FILTER_VALIDATE_INT);

        try {
            $matchingService = new TopicMatchingService();
            $result = $matchingService->matchPeriod((int)$periodId, [
                'max_per_lecturer' => $maxPerLecturer ?: TopicMatchingService::DEFAULT_MAX_STUDENTS_PER_LECTURER,
                'dry_run'          => true,
            ]);

            $this->sendJson([
                'ok'   => true,
                'data' => $result,
            ], 200);
        } catch (Throwable $e) {
            $this->fail('Lỗi mô phỏng: ' . $e->getMessage(), 500, '/dashboard');
        }
    }

    /**
     * GET /assignments - Danh sách phân công đề tài theo đợt
     * (Hỗ trợ truy vấn và dữ liệu nền tảng cho giao diện của Quỳnh - S4-05/S4-06)
     */
    public function index(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        $isManager = self::canManage($roles);

        $periodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);
        $periods  = InternshipPeriod::all();

        if (!$periodId && !empty($periods)) {
            // Mặc định chọn đợt active đầu tiên hoặc đợt mới nhất
            foreach ($periods as $p) {
                if ($p['status'] === 'active') {
                    $periodId = (int)$p['id'];
                    break;
                }
            }
            if (!$periodId) {
                $periodId = (int)$periods[0]['id'];
            }
        }

        // Thu nhận và kiểm tra tính hợp lệ của các bộ lọc đầu vào
        $rawStatus     = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
        $rawKeyword    = isset($_GET['keyword']) && is_string($_GET['keyword']) ? trim($_GET['keyword']) : '';
        $rawLecturerId = filter_var($_GET['lecturer_id'] ?? null, FILTER_VALIDATE_INT);

        $filters = [];
        $validatedStatus = null;
        if ($rawStatus !== '' && in_array($rawStatus, TopicAssignment::ALLOWED_STATUSES, true)) {
            $filters['status'] = $rawStatus;
            $validatedStatus = $rawStatus;
        }

        $validatedKeyword = null;
        if ($rawKeyword !== '') {
            $validatedKeyword = mb_substr($rawKeyword, 0, 100);
            $filters['keyword'] = $validatedKeyword;
        }

        $validatedLecturerId = null;
        if ($rawLecturerId && $rawLecturerId > 0) {
            $filters['lecturer_id'] = (int)$rawLecturerId;
            $validatedLecturerId = (int)$rawLecturerId;
        }

        $assignments    = [];
        $unassigned     = [];
        $statistics     = [];
        $approvedTopics = [];

        if ($periodId) {
            $assignments    = TopicAssignment::getByPeriod($periodId, $filters);
            $unassigned     = TopicAssignment::getUnassignedStudents($periodId);
            $statistics     = TopicAssignment::getStatistics($periodId);
            $approvedTopics = TopicAssignment::getApprovedTopicsWithSlots($periodId);
        }

        $viewData = [
            'periods'        => $periods,
            'currentPeriod'  => $periodId,
            'assignments'    => $assignments,
            'unassigned'     => $unassigned,
            'statistics'     => $statistics,
            'approvedTopics' => $approvedTopics,
            'filters'        => [
                'status'      => $validatedStatus,
                'keyword'     => $validatedKeyword,
                'lecturer_id' => $validatedLecturerId,
            ],
            'isManager'      => $isManager,
            'statusLabels'   => TopicAssignment::STATUS_LABELS,
            'statusBadges'   => TopicAssignment::STATUS_BADGES,
            'methodLabels'   => TopicAssignment::METHOD_LABELS,
            'methodBadges'   => TopicAssignment::METHOD_BADGES,
            'csrf_token'     => AuthController::csrfToken(),
            'success'        => AuthController::pullFlash('success'),
            'error'          => AuthController::pullFlash('error'),
        ];

        // Nếu client yêu cầu JSON (API/Fetch/AJAX):
        if ($this->wantsJson()) {
            $this->sendJson(array_merge(['ok' => true], $viewData), 200);
            return;
        }

        // Nếu client duyệt Web thông thường: Phản hồi View hoặc trang thông báo chuẩn
        $this->respond('assignments/index', $viewData);
    }

    // =========================================================================
    // CÁC HÀM HELPER ĐIỀU HƯỚNG VÀ PHẢN HỒI (CHUẨN HÓA THEO KIẾN TRÚC MVC DỰ ÁN)
    // =========================================================================

    /**
     * Phản hồi chuẩn: Nếu View tồn tại thì render, ngược lại hiển thị trang chờ tích hợp chuẩn Tabler
     * (Tuyệt đối không xuất JSON thô ra màn hình duyệt Web thông thường)
     */
    protected function respond(string $view, array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($viewPath)) {
            $this->render($view, $data);
            return;
        }

        // Xử lý trường hợp View chưa được triển khai cho trang HTML thông thường:
        $this->renderPendingView($view, $data);
    }

    /**
     * Báo lỗi chuẩn: Đặt mã HTTP thực sự, trả JSON nếu client yêu cầu hoặc Flash + Chuyển hướng
     */
    protected function fail(string $message, int $status, string $redirect): void
    {
        http_response_code($status);

        if ($this->wantsJson()) {
            $this->sendJson(['ok' => false, 'error' => $message], $status);
            return;
        }

        AuthController::startSession();
        AuthController::setFlash('error', $message);
        AuthController::go($redirect);
    }

    /**
     * Kiểm tra xem client có yêu cầu phản hồi dạng JSON hay không (API / AJAX / Fetch)
     */
    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }

    /**
     * Gửi phản hồi JSON chuẩn hóa UTF-8 kèm mã trạng thái HTTP
     */
    protected function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Render trang HTML thông báo trạng thái chờ tích hợp giao diện (thay vì in JSON thô)
     */
    private function renderPendingView(string $view, array $data): void
    {
        $title = 'Phân công đề tài & Giảng viên';
        $currentPeriod = $data['currentPeriod'] ?? 0;
        $stats = $data['statistics'] ?? [];
        $totalAssigned = $stats['active_assignments'] ?? 0;
        $totalRegistered = $stats['total_registered'] ?? 0;

        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . self::escape($title) . ' - Trường Đại học Vinh</title>
    <link rel="stylesheet" href="/CNPM/internship-management/public/assets/css/tabler.min.css">
    <link rel="stylesheet" href="/CNPM/internship-management/public/assets/css/vinhuni-theme.css">
</head>
<body class="border-top-wide border-primary d-flex flex-column">
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="text-center mb-4">
                <a href="/CNPM/internship-management/public/dashboard" class="navbar-brand navbar-brand-autodark">
                    <h2 class="text-primary m-0">HỆ THỐNG QUẢN LÝ THỰC TẬP</h2>
                </a>
            </div>
            <div class="card card-md shadow-sm">
                <div class="card-status-top bg-warning"></div>
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <span class="badge bg-warning-lt fs-2 p-2">
                            <i class="ti ti-clock-pause"></i> Đang chờ tích hợp giao diện
                        </span>
                    </div>
                    <h3 class="card-title text-dark">Chức năng Phân công đề tài & Giảng viên</h3>
                    <p class="text-secondary">
                        Tầng xử lý dữ liệu, CSDL và Thuật toán đối sánh tự động (Backend) đã sẵn sàng.<br>
                        Giao diện trực quan (View: <code>' . self::escape($view) . '</code>) đang trong quá trình hoàn thiện.
                    </p>
                    <div class="bg-light p-3 rounded text-start mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Đợt thực tập ID:</span>
                            <span class="fw-bold">' . (int)$currentPeriod . '</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Tổng sinh viên nộp NV:</span>
                            <span class="fw-bold">' . (int)$totalRegistered . '</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Số SV đã phân công:</span>
                            <span class="fw-bold text-success">' . (int)$totalAssigned . '</span>
                        </div>
                    </div>
                    <a href="/CNPM/internship-management/public/dashboard" class="btn btn-primary w-100">
                        <i class="ti ti-arrow-left me-1"></i> Quay về Bảng điều khiển
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }
}
