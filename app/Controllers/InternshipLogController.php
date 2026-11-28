<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipLog;
use App\Models\InternshipPeriod;
use App\Models\TopicAssignment;
use InvalidArgumentException;
use Throwable;

class InternshipLogController extends Controller
{
    public function index(): void
    {
        AuthController::requireRole('student', 'lecturer', 'enterprise');

        $userId = (int)AuthController::id();
        $periodValue = $_GET['period_id'] ?? null;
        $periodId = $periodValue === null || $periodValue === ''
            ? null
            : filter_var($periodValue, FILTER_VALIDATE_INT);
        if ($periodValue !== null && $periodValue !== '' && (!$periodId || $periodId < 1)) {
            $this->fail('Mã đợt không hợp lệ.', '/internship-logs');
            return;
        }

        try {
            $periods = InternshipPeriod::all();
            $logsById = [];

            if (AuthController::hasRole('student')) {
                foreach (InternshipLog::getByStudent($userId, $periodId) as $log) {
                    $logsById[(int)$log['id']] = $log;
                }
            }

            if (AuthController::hasRole('lecturer')) {
                $selectedPeriods = $periodId === null
                    ? $periods
                    : array_values(array_filter($periods, static fn(array $period): bool => (int)$period['id'] === $periodId));
                foreach ($selectedPeriods as $period) {
                    foreach (InternshipLog::getByLecturer($userId, (int)$period['id']) as $log) {
                        $logsById[(int)$log['id']] = $log;
                    }
                }
            }

            if (AuthController::hasRole('enterprise')) {
                foreach (InternshipLog::getByEnterprise($userId, $periodId) as $log) {
                    $logsById[(int)$log['id']] = $log;
                }
            }

            $logs = array_values($logsById);
            usort($logs, static fn(array $a, array $b): int => (int)$b['week_number'] <=> (int)$a['week_number']);
            $assignment = AuthController::hasRole('student')
                ? TopicAssignment::getByStudent($userId, null, true)
                : null;
        } catch (Throwable $e) {
            error_log('InternshipLogController index error: ' . $e->getMessage());
            $this->fail('Không thể tải nhật ký thực tập. Vui lòng thử lại sau.', '/dashboard');
            return;
        }

        $this->render('internship_logs/index', [
            'logs' => $logs,
            'periods' => $periods,
            'periodId' => $periodId,
            'assignment' => $assignment,
            'isStudent' => AuthController::hasRole('student'),
            'canReview' => AuthController::hasRole('lecturer', 'enterprise'),
            'statusLabels' => InternshipLog::STATUS_LABELS,
            'statusBadges' => InternshipLog::STATUS_BADGES,
            'success' => AuthController::pullFlash('success'),
            'error' => AuthController::pullFlash('error'),
        ]);
    }

    public function create(): void
    {
        AuthController::requireRole('student');

        $assignment = TopicAssignment::getByStudent((int)AuthController::id(), null, true);
        if (!$assignment) {
            $this->fail('Bạn chưa có phân công đã công bố để ghi nhật ký.', '/assignments/my-result');
            return;
        }

        $this->render('internship_logs/form', [
            'log' => null,
            'assignment' => $assignment,
            'oldInput' => $this->oldInput(),
            'error' => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    public function store(): void
    {
        AuthController::requireRole('student');
        if (!$this->checkCsrf('/internship-logs/create')) {
            return;
        }

        $assignment = TopicAssignment::getByStudent((int)AuthController::id(), null, true);
        if (!$assignment) {
            $this->fail('Bạn chưa có phân công đã công bố để ghi nhật ký.', '/internship-logs');
            return;
        }

        $data = $this->validatedInput();
        $errors = $this->validateInput($data);
        if ($errors !== []) {
            $this->preserveInput();
            $this->fail(reset($errors), '/internship-logs/create');
            return;
        }

        try {
            $id = InternshipLog::create([
                'assignment_id' => (int)$assignment['id'],
                'student_id' => (int)AuthController::id(),
                'period_id' => (int)$assignment['period_id'],
                'week_number' => $data['week_number'],
                'log_date' => $data['log_date'],
                'work_done' => $data['work_done'],
                'difficulties' => $data['difficulties'],
                'plan_next_week' => $data['plan_next_week'],
                'status' => $data['status'],
            ]);
            AuthController::setFlash('success', 'Đã lưu nhật ký tuần.');
            AuthController::go('/internship-logs/detail?id=' . $id);
        } catch (InvalidArgumentException $e) {
            $this->preserveInput();
            $this->fail($e->getMessage(), '/internship-logs/create');
        } catch (Throwable $e) {
            error_log('InternshipLogController store error: ' . $e->getMessage());
            $this->preserveInput();
            $this->fail('Không thể lưu nhật ký. Vui lòng thử lại sau.', '/internship-logs/create');
        }
    }

    public function show(): void
    {
        AuthController::requireRole('student', 'lecturer', 'enterprise');
        $log = $this->findLogFromQuery();
        if (!$log) {
            return;
        }
        if (!$this->canView($log)) {
            $this->fail('Bạn không có quyền xem nhật ký này.', '/internship-logs', 403);
            return;
        }

        $this->render('internship_logs/show', [
            'log' => $log,
            'canEdit' => AuthController::hasRole('student')
                && (int)$log['student_id'] === (int)AuthController::id()
                && in_array($log['status'], [InternshipLog::STATUS_DRAFT, InternshipLog::STATUS_REJECTED], true),
            'canReview' => $this->canReview($log) && $log['status'] === InternshipLog::STATUS_SUBMITTED,
            'statusLabels' => InternshipLog::STATUS_LABELS,
            'statusBadges' => InternshipLog::STATUS_BADGES,
            'oldInput' => $this->oldInput(),
            'success' => AuthController::pullFlash('success'),
            'error' => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    public function edit(): void
    {
        AuthController::requireRole('student');
        $log = $this->findLogFromQuery();
        if (!$log) {
            return;
        }
        if ((int)$log['student_id'] !== (int)AuthController::id()
            || !in_array($log['status'], [InternshipLog::STATUS_DRAFT, InternshipLog::STATUS_REJECTED], true)) {
            $this->fail('Bạn không thể chỉnh sửa nhật ký này.', '/internship-logs', 403);
            return;
        }

        $this->render('internship_logs/form', [
            'log' => $log,
            'assignment' => null,
            'oldInput' => $this->oldInput(),
            'error' => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    public function update(): void
    {
        AuthController::requireRole('student');
        $id = $this->postId();
        if (!$id || !$this->checkCsrf('/internship-logs')) {
            return;
        }

        $log = InternshipLog::findById($id);
        if (!$log) {
            $this->fail('Nhật ký không tồn tại.', '/internship-logs', 404);
            return;
        }
        if ((int)$log['student_id'] !== (int)AuthController::id()
            || !in_array($log['status'], [InternshipLog::STATUS_DRAFT, InternshipLog::STATUS_REJECTED], true)) {
            $this->fail('Bạn không thể chỉnh sửa nhật ký này.', '/internship-logs', 403);
            return;
        }

        $data = $this->validatedInput();
        $errors = $this->validateInput($data);
        if ($errors !== []) {
            $this->preserveInput();
            $this->fail(reset($errors), '/internship-logs/edit?id=' . $id);
            return;
        }

        try {
            InternshipLog::update($id, [
                'log_date' => $data['log_date'],
                'work_done' => $data['work_done'],
                'difficulties' => $data['difficulties'],
                'plan_next_week' => $data['plan_next_week'],
                'status' => $data['status'],
            ]);
            AuthController::setFlash('success', 'Đã cập nhật nhật ký tuần.');
            AuthController::go('/internship-logs/detail?id=' . $id);
        } catch (InvalidArgumentException $e) {
            $this->preserveInput();
            $this->fail($e->getMessage(), '/internship-logs/edit?id=' . $id);
        } catch (Throwable $e) {
            error_log('InternshipLogController update error: ' . $e->getMessage());
            $this->preserveInput();
            $this->fail('Không thể cập nhật nhật ký. Vui lòng thử lại sau.', '/internship-logs/edit?id=' . $id);
        }
    }

    public function delete(): void
    {
        AuthController::requireRole('student');
        $id = $this->postId();
        if (!$id || !$this->checkCsrf('/internship-logs')) {
            return;
        }

        $log = InternshipLog::findById($id);
        if (!$log) {
            $this->fail('Nhật ký không tồn tại.', '/internship-logs', 404);
            return;
        }
        if ((int)$log['student_id'] !== (int)AuthController::id()
            || $log['status'] !== InternshipLog::STATUS_DRAFT) {
            $this->fail('Chỉ có thể xóa nhật ký nháp của chính bạn.', '/internship-logs', 403);
            return;
        }

        try {
            InternshipLog::delete($id);
            AuthController::setFlash('success', 'Đã xóa nhật ký nháp.');
        } catch (Throwable $e) {
            error_log('InternshipLogController delete error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Không thể xóa nhật ký. Vui lòng thử lại sau.');
        }
        AuthController::go('/internship-logs');
    }

    public function confirm(): void
    {
        $this->review(true);
    }

    public function reject(): void
    {
        $this->review(false);
    }

    private function review(bool $confirm): void
    {
        AuthController::requireRole('lecturer', 'enterprise');
        $id = $this->postId();
        if (!$id || !$this->checkCsrf('/internship-logs')) {
            return;
        }

        $log = InternshipLog::findById($id);
        if (!$log) {
            $this->fail('Nhật ký không tồn tại.', '/internship-logs', 404);
            return;
        }
        if (!$this->canReview($log) || $log['status'] !== InternshipLog::STATUS_SUBMITTED) {
            $this->fail('Bạn không thể xử lý nhật ký này.', '/internship-logs', 403);
            return;
        }

        $comment = trim((string)($_POST['supervisor_comment'] ?? ''));
        if (!$confirm && $comment === '') {
            $this->preserveInput();
            $this->fail('Vui lòng nhập lý do yêu cầu viết lại.', '/internship-logs/detail?id=' . $id);
            return;
        }

        try {
            if ($confirm) {
                InternshipLog::confirm($id, (int)AuthController::id(), $comment);
                AuthController::setFlash('success', 'Đã xác nhận nhật ký.');
            } else {
                InternshipLog::reject($id, (int)AuthController::id(), $comment);
                AuthController::setFlash('success', 'Đã yêu cầu sinh viên viết lại nhật ký.');
            }
        } catch (Throwable $e) {
            error_log('InternshipLogController review error: ' . $e->getMessage());
            AuthController::setFlash('error', 'Không thể xử lý nhật ký. Vui lòng thử lại sau.');
        }
        AuthController::go('/internship-logs/detail?id=' . $id);
    }

    private function findLogFromQuery(): ?array
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->fail('Mã nhật ký không hợp lệ.', '/internship-logs', 400);
            return null;
        }

        $log = InternshipLog::findById((int)$id);
        if (!$log) {
            $this->fail('Nhật ký không tồn tại.', '/internship-logs', 404);
            return null;
        }

        return $log;
    }

    private function canView(array $log): bool
    {
        return (AuthController::hasRole('student') && (int)$log['student_id'] === (int)AuthController::id())
            || $this->canReview($log);
    }

    private function canReview(array $log): bool
    {
        $userId = (int)AuthController::id();
        return (AuthController::hasRole('lecturer') && (int)($log['lecturer_id'] ?? 0) === $userId)
            || (AuthController::hasRole('enterprise') && (int)($log['enterprise_id'] ?? 0) === $userId);
    }

    private function validatedInput(): array
    {
        $mode = $_POST['save_mode'] ?? 'draft';

        return [
            'week_number' => filter_var($_POST['week_number'] ?? null, FILTER_VALIDATE_INT),
            'log_date' => trim((string)($_POST['log_date'] ?? '')),
            'work_done' => trim((string)($_POST['work_done'] ?? '')),
            'difficulties' => trim((string)($_POST['difficulties'] ?? '')),
            'plan_next_week' => trim((string)($_POST['plan_next_week'] ?? '')),
            'status' => $mode === 'submit' ? InternshipLog::STATUS_SUBMITTED : InternshipLog::STATUS_DRAFT,
        ];
    }

    private function validateInput(array $data): array
    {
        $errors = [];
        if (!$data['week_number'] || $data['week_number'] < 1) {
            $errors['week_number'] = 'Số tuần phải là số nguyên dương.';
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['log_date']);
        if (!$date || $date->format('Y-m-d') !== $data['log_date']) {
            $errors['log_date'] = 'Ngày nhật ký không hợp lệ.';
        }
        if ($data['work_done'] === '') {
            $errors['work_done'] = 'Vui lòng mô tả công việc đã thực hiện.';
        }

        return $errors;
    }

    private function postId(): ?int
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $this->fail('Mã nhật ký không hợp lệ.', '/internship-logs', 400);
            return null;
        }

        return (int)$id;
    }

    private function checkCsrf(string $redirect): bool
    {
        if (AuthController::verifyCsrf()) {
            return true;
        }
        $this->fail('Phiên làm việc không hợp lệ. Vui lòng thử lại.', $redirect, 403);
        return false;
    }

    private function preserveInput(): void
    {
        AuthController::setFlashData('old_input', $_POST);
    }

    private function oldInput(): array
    {
        $value = AuthController::pullFlashData('old_input');
        return is_array($value) ? $value : [];
    }

    private function fail(string $message, string $redirect, int $status = 422): void
    {
        http_response_code($status);
        AuthController::setFlash('error', $message);
        AuthController::go($redirect);
    }
}
