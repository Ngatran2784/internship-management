<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\InternshipReport;
use App\Models\Milestone;
use App\Models\TopicAssignment;
use App\Services\ReportUploadService;
use InvalidArgumentException;
use Throwable;

class InternshipReportController extends Controller
{
    public function index(): void
    {
        AuthController::requireRole('student', 'lecturer', 'enterprise');

        $userId = (int)AuthController::id();
        $reportsById = [];
        $assignment = null;
        $submissionMilestones = [];

        try {
            if (AuthController::hasRole('student')) {
                foreach (InternshipReport::getByStudent($userId) as $report) {
                    $reportsById[(int)$report['id']] = $report;
                }

                $assignment = TopicAssignment::getByStudent($userId, null, true);
                if ($assignment) {
                    foreach (Milestone::getStudentMilestones($userId, (int)$assignment['period_id']) as $milestone) {
                        if (($milestone['status'] ?? '') === Milestone::STATUS_CANCELLED) {
                            continue;
                        }
                        $milestone['report_id'] = !empty($milestone['report_id']) ? (int)$milestone['report_id'] : null;
                        $milestone['report_status'] = $milestone['report_status'] ?? null;
                        $milestone['can_submit'] = empty($milestone['report_id']) || in_array(
                            $milestone['report_status'],
                            [InternshipReport::STATUS_REJECTED, InternshipReport::STATUS_RESUBMIT_REQUIRED],
                            true
                        );
                        $submissionMilestones[] = $milestone;
                    }
                }
            }

            foreach (['lecturer', 'enterprise'] as $role) {
                if (!AuthController::hasRole($role)) {
                    continue;
                }
                foreach (InternshipReport::getByReviewer($userId, $role) as $report) {
                    $reportsById[(int)$report['id']] = $report;
                }
            }
        } catch (Throwable $e) {
            error_log('InternshipReportController index error: ' . $e->getMessage());
            $this->fail('Không thể tải danh sách báo cáo. Vui lòng thử lại sau.', '/dashboard');
            return;
        }

        $reports = array_values($reportsById);
        usort($reports, static fn(array $a, array $b): int => strcmp((string)$b['submitted_at'], (string)$a['submitted_at']));

        $this->render('internship_reports/index', [
            'reports' => $reports,
            'submissionMilestones' => $submissionMilestones,
            'assignment' => $assignment,
            'isStudent' => AuthController::hasRole('student'),
            'canReview' => AuthController::hasRole('lecturer', 'enterprise'),
            'statusLabels' => InternshipReport::STATUS_LABELS,
            'statusBadges' => InternshipReport::STATUS_BADGES,
            'success' => AuthController::pullFlash('success'),
            'error' => AuthController::pullFlash('error'),
        ]);
    }

    public function create(): void
    {
        AuthController::requireRole('student');
        $milestone = $this->studentMilestoneFromQuery();
        if (!$milestone) {
            return;
        }

        $studentId = (int)AuthController::id();
        $existing = InternshipReport::getByStudentAndMilestone($studentId, (int)$milestone['id']);
        if ($existing && !in_array(
            $existing['status'],
            [InternshipReport::STATUS_REJECTED, InternshipReport::STATUS_RESUBMIT_REQUIRED],
            true
        )) {
            $this->fail('Báo cáo này hiện không ở trạng thái cho phép nộp lại.', '/internship-reports/detail?id=' . (int)$existing['id']);
            return;
        }

        $this->render('internship_reports/form', [
            'milestone' => $milestone,
            'existingReport' => $existing,
            'oldInput' => $this->oldInput(),
            'error' => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
        ]);
    }

    public function submit(): void
    {
        AuthController::requireRole('student');
        if (!$this->checkCsrf('/internship-reports')) {
            return;
        }

        $milestoneId = filter_var($_POST['milestone_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$milestoneId || $milestoneId < 1) {
            $this->fail('Mã mốc tiến độ không hợp lệ.', '/internship-reports', 400);
            return;
        }
        $milestone = $this->studentMilestone((int)$milestoneId);
        if (!$milestone) {
            return;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $studentNote = trim((string)($_POST['student_note'] ?? ''));
        $errors = [];
        if ($title === '' || mb_strlen($title) > 255) {
            $errors[] = 'Tiêu đề báo cáo là bắt buộc và không được vượt quá 255 ký tự.';
        }
        $file = $_FILES['report_file'] ?? [];
        $fileErrors = ReportUploadService::validateFile($file);
        if ($fileErrors !== []) {
            $errors[] = reset($fileErrors);
        }
        if ($errors !== []) {
            $this->preserveInput();
            $this->fail(implode(' ', $errors), '/internship-reports/create?milestone_id=' . (int)$milestone['id']);
            return;
        }

        $studentId = (int)AuthController::id();
        $existing = InternshipReport::getByStudentAndMilestone($studentId, (int)$milestone['id']);
        if ($existing && !in_array(
            $existing['status'],
            [InternshipReport::STATUS_REJECTED, InternshipReport::STATUS_RESUBMIT_REQUIRED],
            true
        )) {
            $this->fail('Báo cáo này hiện không ở trạng thái cho phép nộp lại.', '/internship-reports/detail?id=' . (int)$existing['id']);
            return;
        }

        $version = $existing ? (int)$existing['current_version'] + 1 : 1;
        $fileInfo = null;
        try {
            $fileInfo = ReportUploadService::processAndStore(
                $file,
                (int)$milestone['period_id'],
                $studentId,
                (int)$milestone['id'],
                $version
            );
            $result = InternshipReport::submit([
                'milestone_id' => (int)$milestone['id'],
                'assignment_id' => (int)$milestone['assignment_id'],
                'student_id' => $studentId,
                'period_id' => (int)$milestone['period_id'],
                'title' => $title,
                'student_note' => $studentNote,
            ], $fileInfo);

            AuthController::setFlash('success', 'Đã nộp báo cáo phiên bản ' . (int)$result['version'] . '.');
            AuthController::go('/internship-reports/detail?id=' . (int)$result['report_id']);
        } catch (InvalidArgumentException $e) {
            $this->removeStoredFile($fileInfo);
            $this->preserveInput();
            $this->fail($e->getMessage(), '/internship-reports/create?milestone_id=' . (int)$milestone['id']);
        } catch (Throwable $e) {
            $this->removeStoredFile($fileInfo);
            error_log('InternshipReportController submit error: ' . $e->getMessage());
            $this->preserveInput();
            $this->fail('Không thể nộp báo cáo. Vui lòng thử lại sau.', '/internship-reports/create?milestone_id=' . (int)$milestone['id']);
        }
    }

    public function show(): void
    {
        AuthController::requireRole('student', 'lecturer', 'enterprise');
        $reportId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$reportId || $reportId < 1) {
            $this->fail('Mã báo cáo không hợp lệ.', '/internship-reports', 400);
            return;
        }

        $report = InternshipReport::findById((int)$reportId);
        if (!$report) {
            $this->fail('Báo cáo không tồn tại.', '/internship-reports', 404);
            return;
        }
        if (!$this->canView($report)) {
            $this->fail('Bạn không có quyền xem báo cáo này.', '/internship-reports', 403);
            return;
        }

        $this->render('internship_reports/show', [
            'report' => $report,
            'canResubmit' => AuthController::hasRole('student')
                && (int)$report['student_id'] === (int)AuthController::id()
                && TopicAssignment::getByStudent((int)AuthController::id(), (int)$report['period_id'], true) !== null
                && in_array($report['status'], [InternshipReport::STATUS_REJECTED, InternshipReport::STATUS_RESUBMIT_REQUIRED], true),
            'canReview' => $this->canReview($report)
                && in_array($report['status'], [InternshipReport::STATUS_SUBMITTED, InternshipReport::STATUS_REVIEWING], true),
            'statusLabels' => InternshipReport::STATUS_LABELS,
            'statusBadges' => InternshipReport::STATUS_BADGES,
            'decisionLabels' => \App\Models\ReportReview::DECISION_LABELS,
            'decisionBadges' => \App\Models\ReportReview::DECISION_BADGES,
            'success' => AuthController::pullFlash('success'),
            'error' => AuthController::pullFlash('error'),
        ]);
    }

    public function download(): void
    {
        AuthController::requireRole('student', 'lecturer', 'enterprise');
        $submissionId = filter_var($_GET['submission_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$submissionId || $submissionId < 1) {
            $this->fail('Mã phiên bản báo cáo không hợp lệ.', '/internship-reports', 400);
            return;
        }

        $submission = InternshipReport::findSubmissionById((int)$submissionId);
        if (!$submission) {
            $this->fail('Tệp báo cáo không tồn tại.', '/internship-reports', 404);
            return;
        }
        if (!$this->canView($submission)) {
            $this->fail('Bạn không có quyền tải tệp báo cáo này.', '/internship-reports', 403);
            return;
        }

        $storageRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/reports');
        $relativePath = str_replace(['\\', "\0"], ['/', ''], (string)$submission['file_path']);
        if ($storageRoot === false || !str_starts_with($relativePath, 'uploads/reports/')) {
            $this->fail('Đường dẫn tệp báo cáo không hợp lệ.', '/internship-reports', 404);
            return;
        }
        $filePath = realpath(dirname(__DIR__, 2) . '/public/' . $relativePath);
        if ($filePath === false || !str_starts_with($filePath, $storageRoot . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
            $this->fail('Không tìm thấy tệp báo cáo trên máy chủ.', '/internship-reports', 404);
            return;
        }

        $originalName = (string)$submission['file_name'];
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($originalName)) ?: 'report';
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string)filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode(basename($originalName)));
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    private function studentMilestoneFromQuery(): ?array
    {
        $milestoneId = filter_var($_GET['milestone_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$milestoneId || $milestoneId < 1) {
            $this->fail('Vui lòng chọn mốc cần nộp báo cáo.', '/internship-reports', 400);
            return null;
        }

        return $this->studentMilestone((int)$milestoneId);
    }

    private function studentMilestone(int $milestoneId): ?array
    {
        $milestone = Milestone::findById($milestoneId);
        if (!$milestone || ($milestone['status'] ?? '') === Milestone::STATUS_CANCELLED) {
            $this->fail('Mốc không tồn tại trong đợt phân công đã công bố của bạn.', '/internship-reports', 404);
            return null;
        }

        $assignment = TopicAssignment::getByStudent(
            (int)AuthController::id(),
            (int)$milestone['period_id'],
            true
        );
        if (!$assignment) {
            $this->fail('Bạn không có phân công đã công bố trong đợt của mốc này.', '/internship-reports', 403);
            return null;
        }

        $milestone['assignment_id'] = (int)$assignment['id'];
        return $milestone;
    }

    private function canView(array $record): bool
    {
        return (AuthController::hasRole('student') && (int)($record['student_id'] ?? 0) === (int)AuthController::id())
            || $this->canReview($record);
    }

    private function canReview(array $record): bool
    {
        $userId = (int)AuthController::id();
        return (AuthController::hasRole('lecturer') && (int)($record['lecturer_id'] ?? 0) === $userId)
            || (AuthController::hasRole('enterprise') && (int)($record['enterprise_id'] ?? 0) === $userId);
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

    private function removeStoredFile(?array $fileInfo): void
    {
        if (!$fileInfo || empty($fileInfo['file_path'])) {
            return;
        }
        $path = realpath(dirname(__DIR__, 2) . '/public/' . $fileInfo['file_path']);
        $root = realpath(dirname(__DIR__, 2) . '/public/uploads/reports');
        if ($path !== false && $root !== false && str_starts_with($path, $root . DIRECTORY_SEPARATOR) && is_file($path)) {
            unlink($path);
        }
    }

    private function fail(string $message, string $redirect, int $status = 422): void
    {
        http_response_code($status);
        AuthController::setFlash('error', $message);
        AuthController::go($redirect);
    }
}
