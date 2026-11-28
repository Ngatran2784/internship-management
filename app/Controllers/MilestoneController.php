<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\Milestone;
use App\Models\TopicAssignment;
use InvalidArgumentException;
use Throwable;

/**
 * MilestoneController - Quản lý các mốc tiến độ thực tập / đồ án
 *
 * Phân quyền (RBAC):
 * - Quản lý (Tạo, Sửa, Xóa): admin, training_dept, department_head
 * - Giảng viên (lecturer): Xem danh sách mốc theo đợt
 * - Sinh viên (student): Chỉ được xem các mốc thuộc phạm vi đợt thực tập / đồ án của mình
 *
 * Kỹ thuật:
 * - Validate chặt chẽ phía server (bắt buộc các trường, kiểm tra start_date <= due_date).
 * - Phòng chống xóa mốc khi đã có báo cáo nộp liên quan.
 * - Cung cấp API / dữ liệu tiến độ cho giao diện theo dõi tiến độ.
 */
class MilestoneController extends Controller
{
    /** Các vai trò được phép quản lý (Thêm/Sửa/Xóa) milestone */
    public const MANAGING_ROLES = ['admin', 'training_dept', 'department_head'];

    /**
     * Kiểm tra quyền quản lý mốc tiến độ
     */
    public static function canManage(): bool
    {
        return AuthController::hasRole(...self::MANAGING_ROLES);
    }

    /**
     * GET /milestones - Danh sách mốc tiến độ
     */
    public function index(): void
    {
        AuthController::requireRole('admin', 'training_dept', 'department_head', 'faculty_staff', 'lecturer', 'student', 'enterprise');
        $userRoles = AuthController::roles();
        $userId    = (int)AuthController::id();
        $isStudent = in_array('student', $userRoles, true) && !self::canManage();

        $allPeriods = InternshipPeriod::all();
        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $allowedPeriodIds = [];

        // Nếu là sinh viên, chỉ lấy đợt mà sinh viên được phân công hoặc đợt đang mở
        if ($isStudent) {
            $myAssignments = TopicAssignment::getByStudent($userId, null, false);
            $studentPeriodIds = array_unique(array_filter(array_map(fn($a) => (int)($a['period_id'] ?? 0), is_array($myAssignments) ? (isset($myAssignments['period_id']) ? [$myAssignments] : $myAssignments) : [])));

            // Nếu không có phân công cụ thể, fallback đợt active
            if (empty($studentPeriodIds) && !empty($allPeriods)) {
                foreach ($allPeriods as $p) {
                    if (($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE) {
                        $studentPeriodIds[] = (int)$p['id'];
                    }
                }
            }

            $allowedPeriodIds = array_map('intval', $studentPeriodIds);
            if ($selectedPeriodId && !in_array($selectedPeriodId, $allowedPeriodIds, true)) {
                $this->fail('Bạn không có quyền xem tiến độ của đợt này.', 403, '/milestones');
                return;
            }
            $allPeriods = array_values(array_filter(
                $allPeriods,
                static fn(array $period): bool => in_array((int)$period['id'], $allowedPeriodIds, true)
            ));

            if (!$selectedPeriodId && !empty($studentPeriodIds)) {
                $selectedPeriodId = reset($studentPeriodIds);
            }
        } elseif (!self::canManage() && (in_array('lecturer', $userRoles, true) || in_array('enterprise', $userRoles, true))) {
            foreach ($allPeriods as $period) {
                $periodId = (int)$period['id'];
                if (in_array('lecturer', $userRoles, true)
                    && TopicAssignment::getByLecturer($userId, $periodId, true) !== []) {
                    $allowedPeriodIds[] = $periodId;
                }
                if (in_array('enterprise', $userRoles, true)
                    && TopicAssignment::getByEnterprise($userId, $periodId, true) !== []) {
                    $allowedPeriodIds[] = $periodId;
                }
            }
            $allowedPeriodIds = array_values(array_unique($allowedPeriodIds));
            if ($selectedPeriodId && !in_array($selectedPeriodId, $allowedPeriodIds, true)) {
                $this->fail('Bạn không có phân công trong đợt này.', 403, '/milestones');
                return;
            }
            $allPeriods = array_values(array_filter(
                $allPeriods,
                static fn(array $period): bool => in_array((int)$period['id'], $allowedPeriodIds, true)
            ));
            if (!$selectedPeriodId && $allowedPeriodIds !== []) {
                $selectedPeriodId = $allowedPeriodIds[0];
            }
        } elseif (!$selectedPeriodId && !empty($allPeriods)) {
            // Mặc định lấy đợt active đầu tiên
            foreach ($allPeriods as $p) {
                if (($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE) {
                    $selectedPeriodId = (int)$p['id'];
                    break;
                }
            }
            if (!$selectedPeriodId) {
                $selectedPeriodId = (int)$allPeriods[0]['id'];
            }
        }

        // Tự động đồng bộ các mốc quá hạn
        Milestone::syncOverdueStatus($selectedPeriodId);

        $statusFilter  = is_string($_GET['status'] ?? null) ? trim($_GET['status']) : null;
        $keywordFilter = is_string($_GET['keyword'] ?? null) ? trim($_GET['keyword']) : null;

        if ($isStudent && $selectedPeriodId) {
            // Dành cho sinh viên: Lấy kèm trạng thái nộp bài cá nhân
            $milestones = Milestone::getStudentMilestones($userId, $selectedPeriodId);
        } else {
            // Dành cho quản lý & giảng viên
            $milestones = Milestone::all($selectedPeriodId, $statusFilter, $keywordFilter);
        }

        $progressStats = $selectedPeriodId ? Milestone::getProgressStats($selectedPeriodId, $isStudent ? $userId : null) : [];

        $data = [
            'milestones'       => $milestones,
            'allPeriods'       => $allPeriods,
            'selectedPeriodId' => $selectedPeriodId,
            'statusFilter'     => $statusFilter,
            'keywordFilter'    => $keywordFilter,
            'progressStats'    => $progressStats,
            'canManage'        => self::canManage(),
            'isStudent'        => $isStudent,
            'userRoles'        => $userRoles,
            'statusLabels'     => Milestone::STATUS_LABELS,
            'statusBadges'     => Milestone::STATUS_BADGES,
            'success'          => AuthController::pullFlash('success'),
            'error'            => AuthController::pullFlash('error'),
            'csrfToken'        => AuthController::csrfToken(),
        ];

        if ($this->wantsJson()) {
            $this->sendJson(['ok' => true, 'data' => $data]);
            return;
        }

        $this->render('milestones/index', $data);
    }

    /**
     * GET /milestones/detail?id={id} - Xem chi tiết một mốc tiến độ
     */
    public function show(): void
    {
        AuthController::requireRole('admin', 'training_dept', 'department_head', 'faculty_staff', 'lecturer', 'student', 'enterprise');
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $this->fail('Mã mốc tiến độ không hợp lệ.', 400, '/milestones');
            return;
        }

        $milestone = Milestone::findById($id);
        if (!$milestone) {
            $this->fail('Mốc tiến độ không tồn tại.', 404, '/milestones');
            return;
        }

        $userRoles = AuthController::roles();
        $userId    = (int)AuthController::id();
        $isStudent = in_array('student', $userRoles, true) && !self::canManage();

        // Kiểm tra phạm vi xem của sinh viên
        if ($isStudent) {
            // Sinh viên chỉ được xem mốc thuộc đợt mình tham gia
            $myAssignment = TopicAssignment::getByStudent($userId, (int)$milestone['period_id'], false);
            $period = InternshipPeriod::findById((int)$milestone['period_id']);
            if (!$myAssignment && ($period['status'] ?? '') !== InternshipPeriod::STATUS_ACTIVE) {
                $this->fail('Bạn không có quyền xem mốc tiến độ của đợt này.', 403, '/milestones');
                return;
            }
        } elseif (!self::canManage() && in_array('lecturer', $userRoles, true)
            && TopicAssignment::getByLecturer($userId, (int)$milestone['period_id'], true) === []) {
            $this->fail('Bạn không có phân công trong đợt của mốc này.', 403, '/milestones');
            return;
        } elseif (!self::canManage() && in_array('enterprise', $userRoles, true)
            && TopicAssignment::getByEnterprise($userId, (int)$milestone['period_id'], true) === []) {
            $this->fail('Bạn không có phân công trong đợt của mốc này.', 403, '/milestones');
            return;
        }

        $data = [
            'milestone'    => $milestone,
            'canManage'    => self::canManage(),
            'isStudent'    => $isStudent,
            'statusLabels' => Milestone::STATUS_LABELS,
            'statusBadges' => Milestone::STATUS_BADGES,
            'success'      => AuthController::pullFlash('success'),
            'error'        => AuthController::pullFlash('error'),
            'csrfToken'    => AuthController::csrfToken(),
        ];

        if ($this->wantsJson()) {
            $this->sendJson(['ok' => true, 'data' => $data]);
            return;
        }

        $this->render('milestones/show', $data);
    }

    /**
     * GET /milestones/create - Form tạo mới mốc tiến độ
     */
    public function create(): void
    {
        AuthController::requireRole(...self::MANAGING_ROLES);

        $allPeriods = InternshipPeriod::all();
        $selectedPeriodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

        $this->render('milestones/create', [
            'allPeriods'       => $allPeriods,
            'selectedPeriodId' => $selectedPeriodId,
            'statusLabels'     => Milestone::STATUS_LABELS,
            'error'            => AuthController::pullFlash('error'),
            'oldInput'         => AuthController::pullFlashData('old_input') ?? [],
            'csrfToken'        => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /milestones/create - Xử lý lưu mốc tiến độ mới
     */
    public function store(): void
    {
        AuthController::requireRole(...self::MANAGING_ROLES);

        if (!AuthController::verifyCsrf()) {
            $this->fail('Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.', 400, '/milestones/create');
            return;
        }

        $data = [
            'period_id'    => filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT),
            'title'        => trim((string)($_POST['title'] ?? '')),
            'description'  => trim((string)($_POST['description'] ?? '')),
            'start_date'   => trim((string)($_POST['start_date'] ?? '')),
            'due_date'     => trim((string)($_POST['due_date'] ?? '')),
            'order_index'  => filter_var($_POST['order_index'] ?? null, FILTER_VALIDATE_INT) ?: null,
            'status'       => trim((string)($_POST['status'] ?? Milestone::STATUS_PENDING)),
            'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
            'created_by'   => (int)AuthController::id(),
        ];

        try {
            $newId = Milestone::create($data);
            AuthController::setFlash('success', 'Tạo mốc tiến độ thành công!');
            
            if ($this->wantsJson()) {
                $this->sendJson(['ok' => true, 'id' => $newId, 'message' => 'Tạo mốc tiến độ thành công!']);
                return;
            }

            AuthController::go("/milestones?period_id={$data['period_id']}");
        } catch (InvalidArgumentException $e) {
            AuthController::setFlashData('old_input', $_POST);
            $this->fail($e->getMessage(), 422, '/milestones/create' . ($data['period_id'] ? "?period_id={$data['period_id']}" : ''));
        } catch (Throwable $e) {
            error_log('MilestoneController store error: ' . $e->getMessage());
            AuthController::setFlashData('old_input', $_POST);
            $this->fail('Lỗi hệ thống khi lưu mốc tiến độ. Vui lòng thử lại sau.', 500, '/milestones/create');
        }
    }

    /**
     * GET /milestones/edit?id={id} - Form chỉnh sửa mốc tiến độ
     */
    public function edit(): void
    {
        AuthController::requireRole(...self::MANAGING_ROLES);

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $this->fail('Mã mốc tiến độ không hợp lệ.', 400, '/milestones');
            return;
        }

        $milestone = Milestone::findById($id);
        if (!$milestone) {
            $this->fail('Mốc tiến độ không tồn tại.', 404, '/milestones');
            return;
        }

        $allPeriods = InternshipPeriod::all();

        $this->render('milestones/edit', [
            'milestone'    => $milestone,
            'allPeriods'   => $allPeriods,
            'statusLabels' => Milestone::STATUS_LABELS,
            'error'        => AuthController::pullFlash('error'),
            'oldInput'     => AuthController::pullFlashData('old_input') ?? [],
            'csrfToken'    => AuthController::csrfToken(),
        ]);
    }

    /**
     * POST /milestones/edit?id={id} - Xử lý cập nhật mốc tiến độ
     */
    public function update(): void
    {
        AuthController::requireRole(...self::MANAGING_ROLES);

        if (!AuthController::verifyCsrf()) {
            $this->fail('Mã xác thực CSRF không hợp lệ hoặc đã hết hạn.', 400, '/milestones');
            return;
        }

        $id = filter_var($_POST['id'] ?? ($_GET['id'] ?? null), FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $this->fail('Mã mốc tiến độ không hợp lệ.', 400, '/milestones');
            return;
        }

        $data = [
            'period_id'    => filter_var($_POST['period_id'] ?? null, FILTER_VALIDATE_INT),
            'title'        => trim((string)($_POST['title'] ?? '')),
            'description'  => trim((string)($_POST['description'] ?? '')),
            'start_date'   => trim((string)($_POST['start_date'] ?? '')),
            'due_date'     => trim((string)($_POST['due_date'] ?? '')),
            'order_index'  => filter_var($_POST['order_index'] ?? null, FILTER_VALIDATE_INT) ?: 1,
            'status'       => trim((string)($_POST['status'] ?? Milestone::STATUS_PENDING)),
            'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
        ];

        try {
            Milestone::update($id, $data);
            AuthController::setFlash('success', 'Cập nhật mốc tiến độ thành công!');

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => true, 'message' => 'Cập nhật mốc tiến độ thành công!']);
                return;
            }

            AuthController::go("/milestones?period_id={$data['period_id']}");
        } catch (InvalidArgumentException $e) {
            AuthController::setFlashData('old_input', $_POST);
            $this->fail($e->getMessage(), 422, "/milestones/edit?id={$id}");
        } catch (Throwable $e) {
            error_log('MilestoneController update error: ' . $e->getMessage());
            AuthController::setFlashData('old_input', $_POST);
            $this->fail('Lỗi hệ thống khi cập nhật mốc tiến độ.', 500, "/milestones/edit?id={$id}");
        }
    }

    /**
     * POST /milestones/delete - Xóa mốc tiến độ
     */
    public function delete(): void
    {
        AuthController::requireRole(...self::MANAGING_ROLES);

        if (!AuthController::verifyCsrf()) {
            $this->fail('Mã xác thực CSRF không hợp lệ.', 400, '/milestones');
            return;
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            $this->fail('Mã mốc tiến độ không hợp lệ.', 400, '/milestones');
            return;
        }

        $milestone = Milestone::findById($id);
        if (!$milestone) {
            $this->fail('Mốc tiến độ không tồn tại.', 404, '/milestones');
            return;
        }

        $periodId = (int)$milestone['period_id'];

        try {
            Milestone::delete($id);
            AuthController::setFlash('success', "Đã xóa mốc tiến độ \"{$milestone['title']}\" thành công.");

            if ($this->wantsJson()) {
                $this->sendJson(['ok' => true, 'message' => 'Xóa mốc tiến độ thành công.']);
                return;
            }

            AuthController::go("/milestones?period_id={$periodId}");
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 422, "/milestones?period_id={$periodId}");
        } catch (Throwable $e) {
            error_log('MilestoneController delete error: ' . $e->getMessage());
            $this->fail('Lỗi hệ thống khi xóa mốc tiến độ.', 500, "/milestones?period_id={$periodId}");
        }
    }

    /**
     * GET /api/milestones/progress?period_id={id}&student_id={id}
     * Cung cấp số liệu thống kê tiến độ tích hợp
     */
    public function progressApi(): void
    {
        AuthController::requireLogin();
        $periodId = filter_var($_GET['period_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$periodId) {
            $this->sendJson(['ok' => false, 'error' => 'Thiếu period_id.'], 400);
            return;
        }

        $period = InternshipPeriod::findById((int)$periodId);
        if (!$period) {
            $this->sendJson(['ok' => false, 'error' => 'Đợt thực tập không tồn tại.'], 404);
            return;
        }

        $studentValue = $_GET['student_id'] ?? null;
        $studentId = null;
        if ($studentValue !== null && $studentValue !== '') {
            $studentId = filter_var($studentValue, FILTER_VALIDATE_INT);
            if (!$studentId || $studentId < 1) {
                $this->sendJson(['ok' => false, 'error' => 'student_id không hợp lệ.'], 400);
                return;
            }
        }

        $userRoles = AuthController::roles();
        $userId = (int)AuthController::id();
        $canViewAll = self::canManage() || in_array('faculty_staff', $userRoles, true);

        if (!$canViewAll && in_array('student', $userRoles, true)) {
            $studentId = $userId;
            $assignment = TopicAssignment::getByStudent($userId, (int)$periodId, false);
            if (!$assignment && ($period['status'] ?? '') !== InternshipPeriod::STATUS_ACTIVE) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền xem tiến độ của đợt này.'], 403);
                return;
            }
        } elseif (!$canViewAll && in_array('lecturer', $userRoles, true)) {
            if (TopicAssignment::getByLecturer($userId, (int)$periodId, true) === []) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có phân công trong đợt này.'], 403);
                return;
            }
            if ($studentId !== null) {
                $assignment = TopicAssignment::getByStudent((int)$studentId, (int)$periodId, true);
                if (!$assignment || (int)$assignment['lecturer_id'] !== $userId) {
                    $this->sendJson(['ok' => false, 'error' => 'Sinh viên không thuộc phạm vi hướng dẫn của bạn.'], 403);
                    return;
                }
            }
        } elseif (!$canViewAll && in_array('enterprise', $userRoles, true)) {
            $assignments = TopicAssignment::getByEnterprise($userId, (int)$periodId, true);
            if ($assignments === []) {
                $this->sendJson(['ok' => false, 'error' => 'Bạn không có phân công trong đợt này.'], 403);
                return;
            }
            if ($studentId !== null) {
                $studentIsAssigned = false;
                foreach ($assignments as $assignment) {
                    if ((int)$assignment['student_id'] === (int)$studentId) {
                        $studentIsAssigned = true;
                        break;
                    }
                }
                if (!$studentIsAssigned) {
                    $this->sendJson(['ok' => false, 'error' => 'Sinh viên không thuộc phạm vi doanh nghiệp của bạn.'], 403);
                    return;
                }
            }
        } elseif (!$canViewAll) {
            $this->sendJson(['ok' => false, 'error' => 'Bạn không có quyền xem tiến độ.'], 403);
            return;
        }

        $stats = Milestone::getProgressStats($periodId, $studentId);
        $milestones = $studentId 
            ? Milestone::getStudentMilestones($studentId, $periodId)
            : Milestone::getByPeriod($periodId);

        $this->sendJson([
            'ok'         => true,
            'period_id'  => $periodId,
            'student_id' => $studentId,
            'statistics' => $stats,
            'milestones' => $milestones,
        ]);
    }

    /**
     * Báo lỗi chuẩn Flash + chuyển hướng hoặc JSON
     */
    protected function fail(string $message, int $status, string $redirect): void
    {
        http_response_code($status);

        if ($this->wantsJson()) {
            $this->sendJson(['ok' => false, 'error' => $message], $status);
            return;
        }

        AuthController::setFlash('error', $message);
        AuthController::go($redirect);
    }

    /**
     * Kiểm tra client yêu cầu JSON
     */
    protected function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xReq   = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return str_contains($accept, 'application/json') || strtolower($xReq) === 'xmlhttprequest';
    }

    /**
     * Trả về phản hồi JSON
     */
    protected function sendJson(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
