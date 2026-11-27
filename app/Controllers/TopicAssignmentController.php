<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\Topic;
use App\Models\TopicAssignment;
use App\Models\TopicWish;
use App\Models\User;
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

    /**
     * GET /assignments/create - Màn hình tạo phân công thủ công
     */
    public function create(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền quản lý phân công đề tài.', 403, '/dashboard');
            return;
        }

        $periods = InternshipPeriod::all();
        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$selectedPeriodId && !empty($periods)) {
            foreach ($periods as $p) {
                if (($p['status'] ?? '') === 'active') {
                    $selectedPeriodId = (int)$p['id'];
                    break;
                }
            }
            if (!$selectedPeriodId) {
                $selectedPeriodId = (int)$periods[0]['id'];
            }
        }

        $students = $selectedPeriodId ? TopicAssignment::getUnassignedStudents($selectedPeriodId) : [];
        if (empty($students)) {
            $students = User::all('student');
        }

        $topics = $selectedPeriodId ? Topic::all(['period_id' => $selectedPeriodId, 'status' => 'approved']) : [];
        $lecturers = User::all('lecturer');

        $baseUrl = rtrim((string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        // Trích xuất base URL chuẩn
        $reqUri = $_SERVER['REQUEST_URI'] ?? '';
        $base = '/CNPM/internship-management/public';

        $this->render('assignments/create', [
            'periods'   => $periods,
            'students'  => $students,
            'topics'    => $topics,
            'lecturers' => $lecturers,
            'old'       => ['period_id' => $selectedPeriodId],
            'errors'    => [],
            'csrfToken' => AuthController::csrfToken(),
            'submitUrl' => $base . '/assignments/create',
            'canSubmit' => true,
            'success'   => AuthController::pullFlash('success'),
            'error'     => AuthController::pullFlash('error'),
        ]);
    }

    /**
     * POST /assignments/create & POST /assignments/manual - Lưu phân công thủ công
     */
    public function store(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền phân công đề tài.', 403, '/dashboard');
            return;
        }

        $csrfToken = $_POST['_csrf'] ?? '';
        if (!AuthController::verifyCsrf($csrfToken)) {
            $this->fail('Mã bảo mật (CSRF) không hợp lệ.', 403, '/assignments');
            return;
        }

        $periodId   = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT);
        $studentId  = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
        $topicId    = filter_var($_POST['topic_id'] ?? null, FILTER_VALIDATE_INT);
        $lecturerId = filter_var($_POST['lecturer_id'] ?? null, FILTER_VALIDATE_INT);
        $notes      = trim((string)($_POST['notes'] ?? ''));

        if (!$periodId || !$studentId || !$topicId || !$lecturerId) {
            $this->fail('Vui lòng điền đầy đủ thông tin phân công.', 422, '/assignments' . ($periodId ? '?period_id=' . $periodId : ''));
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            TopicAssignment::assignManual($periodId, $studentId, $topicId, $lecturerId, $userId, $notes ?: null);
            AuthController::setFlash('success', 'Phân công đề tài thủ công thành công.');
            AuthController::go('/assignments?period_id=' . $periodId);
        } catch (Throwable $e) {
            $this->fail('Lỗi phân công: ' . $e->getMessage(), 422, '/assignments?period_id=' . $periodId);
        }
    }

    /**
     * POST /assignments/publish - Công bố kết quả phân công cho toàn đợt
     */
    public function publish(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền công bố kết quả phân công.', 403, '/dashboard');
            return;
        }

        $csrfToken = $_POST['_csrf'] ?? '';
        if (!AuthController::verifyCsrf($csrfToken)) {
            $this->fail('Mã bảo mật (CSRF) không hợp lệ.', 403, '/assignments');
            return;
        }

        $periodId = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$periodId) {
            $this->fail('Vui lòng chọn đợt thực tập / đồ án.', 422, '/assignments');
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        try {
            $count = TopicAssignment::publishPeriodAssignments((int)$periodId, $userId);
            AuthController::setFlash('success', "Công bố kết quả phân công thành công cho {$count} sinh viên.");
            AuthController::go('/assignments?period_id=' . $periodId);
        } catch (Throwable $e) {
            $this->fail('Lỗi công bố kết quả: ' . $e->getMessage(), 500, '/assignments?period_id=' . $periodId);
        }
    }

    /**
     * POST /assignments/unpublish - Rút lại công bố kết quả phân công về trạng thái dự thảo
     */
    public function unpublish(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền rút lại công bố kết quả.', 403, '/dashboard');
            return;
        }

        $csrfToken = $_POST['_csrf'] ?? '';
        if (!AuthController::verifyCsrf($csrfToken)) {
            $this->fail('Mã bảo mật (CSRF) không hợp lệ.', 403, '/assignments');
            return;
        }

        $periodId = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$periodId) {
            $this->fail('Vui lòng chọn đợt thực tập / đồ án.', 422, '/assignments');
            return;
        }

        try {
            $count = TopicAssignment::unpublishPeriodAssignments((int)$periodId);
            AuthController::setFlash('success', "Đã rút lại công bố kết quả phân công về bản dự thảo cho {$count} sinh viên.");
            AuthController::go('/assignments?period_id=' . $periodId);
        } catch (Throwable $e) {
            $this->fail('Lỗi rút lại công bố: ' . $e->getMessage(), 500, '/assignments?period_id=' . $periodId);
        }
    }

    /**
     * POST /assignments/cancel - Hủy một bản ghi phân công
     */
    public function cancel(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!self::canManage($roles)) {
            $this->fail('Bạn không có quyền hủy phân công.', 403, '/dashboard');
            return;
        }

        $csrfToken = $_POST['_csrf'] ?? '';
        if (!AuthController::verifyCsrf($csrfToken)) {
            $this->fail('Mã bảo mật (CSRF) không hợp lệ.', 403, '/assignments');
            return;
        }

        $assignmentId = filter_var($_POST['assignment_id'] ?? null, FILTER_VALIDATE_INT);
        $periodId     = filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT);
        $reason       = trim((string)($_POST['reason'] ?? ''));

        if (!$assignmentId) {
            $this->fail('Mã phân công không hợp lệ.', 422, '/assignments' . ($periodId ? '?period_id=' . $periodId : ''));
            return;
        }

        try {
            TopicAssignment::cancelAssignment($assignmentId, $reason ?: 'Hủy bởi người quản lý');
            AuthController::setFlash('success', 'Đã hủy phân công thành công.');
            AuthController::go('/assignments?period_id=' . ($periodId ?: ''));
        } catch (Throwable $e) {
            $this->fail('Lỗi hủy phân công: ' . $e->getMessage(), 500, '/assignments?period_id=' . ($periodId ?: ''));
        }
    }

    /**
     * GET /assignments/my-students & GET /assignments/supervisor-students
     * Danh sách sinh viên được phân công cho Giảng viên
     */
    public function supervisorStudents(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!in_array('lecturer', $roles, true) && !in_array('admin', $roles, true) && !in_array('department_head', $roles, true)) {
            $this->fail('Chức năng này chỉ dành cho Giảng viên hướng dẫn.', 403, '/dashboard');
            return;
        }

        $lecturerId = (int)($_SESSION['user_id'] ?? 0);
        $periods    = InternshipPeriod::all();
        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$selectedPeriodId && !empty($periods)) {
            foreach ($periods as $p) {
                if (($p['status'] ?? '') === 'active') {
                    $selectedPeriodId = (int)$p['id'];
                    break;
                }
            }
            if (!$selectedPeriodId) {
                $selectedPeriodId = (int)$periods[0]['id'];
            }
        }

        $students = [];
        if ($selectedPeriodId) {
            $students = TopicAssignment::getByLecturer($lecturerId, $selectedPeriodId, false);
        }

        $this->render('assignments/supervisor_students', [
            'periods'        => $periods,
            'selectedPeriod' => $selectedPeriodId,
            'students'       => $students,
            'error'          => AuthController::pullFlash('error'),
            'success'        => AuthController::pullFlash('success'),
        ]);
    }

    /**
     * GET /assignments/enterprise-students
     * Danh sách sinh viên thực tập được giao cho Doanh nghiệp
     */
    public function enterpriseStudents(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!in_array('enterprise', $roles, true) && !in_array('admin', $roles, true)) {
            $this->fail('Chức năng này chỉ dành cho Doanh nghiệp.', 403, '/dashboard');
            return;
        }

        $enterpriseId = (int)($_SESSION['user_id'] ?? 0);
        $periods      = InternshipPeriod::all();
        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$selectedPeriodId && !empty($periods)) {
            foreach ($periods as $p) {
                if (($p['status'] ?? '') === 'active' && ($p['type'] ?? '') === 'internship') {
                    $selectedPeriodId = (int)$p['id'];
                    break;
                }
            }
            if (!$selectedPeriodId) {
                $selectedPeriodId = (int)$periods[0]['id'];
            }
        }

        $students = TopicAssignment::getByEnterprise($enterpriseId, $selectedPeriodId);

        $this->render('assignments/enterprise_students', [
            'periods'        => $periods,
            'selectedPeriod' => $selectedPeriodId,
            'students'       => $students,
            'error'          => AuthController::pullFlash('error'),
            'success'        => AuthController::pullFlash('success'),
        ]);
    }

    /**
     * GET /assignments/my-result (và /assignments/result)
     * Kết quả phân công đề tài & Giảng viên hướng dẫn dành cho Sinh viên
     */
    public function myResult(): void
    {
        AuthController::startSession();
        AuthController::requireLogin();

        $roles = AuthController::roles();
        if (!in_array('student', $roles, true) && !in_array('admin', $roles, true)) {
            $this->fail('Chức năng này chỉ dành cho tài khoản Sinh viên.', 403, '/dashboard');
            return;
        }

        $studentId = (int)AuthController::id();
        $allPeriods = InternshipPeriod::all();
        $isStudentOnly = in_array('student', $roles, true) && !in_array('admin', $roles, true) && !in_array('training_dept', $roles, true);

        if ($isStudentOnly) {
            $allPeriods = array_values(array_filter($allPeriods, static fn(array $p): bool => ($p['status'] ?? '') !== InternshipPeriod::STATUS_DRAFT));
        }

        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);
        $selectedPeriod = null;

        if ($selectedPeriodId) {
            foreach ($allPeriods as $p) {
                if ((int)$p['id'] === $selectedPeriodId) {
                    $selectedPeriod = $p;
                    break;
                }
            }
        }

        if (!$selectedPeriod && !empty($allPeriods)) {
            foreach ($allPeriods as $p) {
                if (($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE) {
                    $selectedPeriod = $p;
                    $selectedPeriodId = (int)$p['id'];
                    break;
                }
            }
            if (!$selectedPeriod) {
                $selectedPeriod = $allPeriods[0];
                $selectedPeriodId = (int)$allPeriods[0]['id'];
            }
        }

        $isPublished = false;
        $assignment = null;
        $wishes = [];

        if ($selectedPeriodId) {
            $isPublished = TopicAssignment::hasPublishedAssignments($selectedPeriodId);
            $assignment = TopicAssignment::getByStudent($studentId, $selectedPeriodId, true);
            $wishes = TopicWish::getByStudentAndPeriod($studentId, $selectedPeriodId);
        }

        $this->render('assignments/my_result', [
            'allPeriods'       => $allPeriods,
            'selectedPeriod'   => $selectedPeriod,
            'selectedPeriodId' => $selectedPeriodId,
            'isPublished'      => $isPublished,
            'assignment'       => $assignment,
            'wishes'           => $wishes,
            'statusLabels'     => InternshipPeriod::STATUS_LABELS,
            'typeLabels'       => InternshipPeriod::TYPE_LABELS,
            'error'            => AuthController::pullFlash('error'),
            'success'          => AuthController::pullFlash('success'),
        ]);
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
