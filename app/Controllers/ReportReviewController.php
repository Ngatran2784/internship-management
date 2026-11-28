<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipReport;
use App\Models\ReportReview;
use InvalidArgumentException;
use Throwable;

class ReportReviewController extends Controller
{
    public function index(): void
    {
        AuthController::requireRole('lecturer', 'enterprise');

        $reportsById = [];
        try {
            foreach (['lecturer', 'enterprise'] as $role) {
                if (!AuthController::hasRole($role)) {
                    continue;
                }
                foreach (InternshipReport::getByReviewer((int)AuthController::id(), $role) as $report) {
                    if (in_array($report['status'], [InternshipReport::STATUS_SUBMITTED, InternshipReport::STATUS_REVIEWING], true)) {
                        $reportsById[(int)$report['id']] = $report;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('ReportReviewController index error: ' . $e->getMessage());
            $this->fail('Không thể tải danh sách báo cáo cần nhận xét.', '/dashboard');
            return;
        }

        $this->render('report_reviews/index', [
            'reports' => array_values($reportsById),
            'statusLabels' => InternshipReport::STATUS_LABELS,
            'statusBadges' => InternshipReport::STATUS_BADGES,
            'success' => AuthController::pullFlash('success'),
            'error' => AuthController::pullFlash('error'),
        ]);
    }

    public function create(): void
    {
        AuthController::requireRole('lecturer', 'enterprise');
        $report = $this->findReportFromQuery();
        if (!$report) {
            return;
        }
        if (!$this->canReview($report) || !$this->isReviewable($report)) {
            $this->fail('Bạn không thể nhận xét báo cáo này.', '/report-reviews', 403);
            return;
        }

        $latestSubmission = $this->latestSubmission($report);
        if (!$latestSubmission) {
            $this->fail('Báo cáo chưa có phiên bản tệp để nhận xét.', '/report-reviews', 422);
            return;
        }

        $this->render('report_reviews/create', [
            'report' => $report,
            'submission' => $latestSubmission,
            'oldInput' => $this->oldInput(),
            'error' => AuthController::pullFlash('error'),
            'csrfToken' => AuthController::csrfToken(),
            'decisionLabels' => ReportReview::DECISION_LABELS,
        ]);
    }

    public function store(): void
    {
        AuthController::requireRole('lecturer', 'enterprise');
        if (!AuthController::verifyCsrf()) {
            $this->fail('Phiên làm việc không hợp lệ. Vui lòng thử lại.', '/report-reviews', 403);
            return;
        }

        $reportId = filter_var($_POST['report_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$reportId || $reportId < 1) {
            $this->fail('Mã báo cáo không hợp lệ.', '/report-reviews', 400);
            return;
        }
        $report = InternshipReport::findById((int)$reportId);
        if (!$report) {
            $this->fail('Báo cáo không tồn tại.', '/report-reviews', 404);
            return;
        }
        if (!$this->canReview($report) || !$this->isReviewable($report)) {
            $this->fail('Bạn không thể nhận xét báo cáo này.', '/report-reviews', 403);
            return;
        }

        $submission = $this->latestSubmission($report);
        $submissionId = filter_var($_POST['submission_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$submission || !$submissionId || (int)$submission['id'] !== (int)$submissionId) {
            $this->fail('Phiên bản báo cáo không hợp lệ hoặc không phải phiên bản mới nhất.', '/report-reviews/create?report_id=' . (int)$reportId, 422);
            return;
        }

        $decision = is_string($_POST['decision'] ?? null) ? trim($_POST['decision']) : '';
        $comments = trim((string)($_POST['comments'] ?? ''));
        $editRequirements = trim((string)($_POST['edit_requirements'] ?? ''));
        $scoreInput = trim((string)($_POST['score'] ?? ''));
        $errors = [];

        if (!in_array($decision, ReportReview::ALLOWED_DECISIONS, true)) {
            $errors[] = 'Vui lòng chọn quyết định nhận xét hợp lệ.';
        }
        if ($comments === '') {
            $errors[] = 'Vui lòng nhập nội dung nhận xét.';
        }
        if ($decision === ReportReview::DECISION_RESUBMIT_REQUIRED && $editRequirements === '') {
            $errors[] = 'Vui lòng nêu rõ nội dung cần chỉnh sửa trước khi yêu cầu nộp lại.';
        }
        $score = null;
        if ($scoreInput !== '') {
            if (!is_numeric($scoreInput) || (float)$scoreInput < 0 || (float)$scoreInput > 10) {
                $errors[] = 'Điểm phải nằm trong khoảng từ 0 đến 10.';
            } else {
                $score = (float)$scoreInput;
            }
        }

        if ($errors !== []) {
            AuthController::setFlashData('old_input', $_POST);
            $this->fail(implode(' ', $errors), '/report-reviews/create?report_id=' . (int)$reportId);
            return;
        }

        try {
            ReportReview::create([
                'report_id' => (int)$reportId,
                'submission_id' => (int)$submission['id'],
                'reviewer_id' => (int)AuthController::id(),
                'decision' => $decision,
                'comments' => $comments,
                'edit_requirements' => $editRequirements,
                'score' => $score,
            ]);
            AuthController::setFlash('success', 'Đã lưu nhận xét cho báo cáo.');
            AuthController::go('/internship-reports/detail?id=' . (int)$reportId);
        } catch (InvalidArgumentException $e) {
            AuthController::setFlashData('old_input', $_POST);
            $this->fail($e->getMessage(), '/report-reviews/create?report_id=' . (int)$reportId);
        } catch (Throwable $e) {
            error_log('ReportReviewController store error: ' . $e->getMessage());
            AuthController::setFlashData('old_input', $_POST);
            $this->fail('Không thể lưu nhận xét. Vui lòng thử lại sau.', '/report-reviews/create?report_id=' . (int)$reportId);
        }
    }

    private function findReportFromQuery(): ?array
    {
        $reportId = filter_var($_GET['report_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$reportId || $reportId < 1) {
            $this->fail('Mã báo cáo không hợp lệ.', '/report-reviews', 400);
            return null;
        }

        $report = InternshipReport::findById((int)$reportId);
        if (!$report) {
            $this->fail('Báo cáo không tồn tại.', '/report-reviews', 404);
            return null;
        }

        return $report;
    }

    private function latestSubmission(array $report): ?array
    {
        foreach (($report['submissions'] ?? []) as $submission) {
            if ((int)$submission['version'] === (int)$report['current_version']) {
                return $submission;
            }
        }

        return null;
    }

    private function canReview(array $report): bool
    {
        $userId = (int)AuthController::id();
        return (AuthController::hasRole('lecturer') && (int)($report['lecturer_id'] ?? 0) === $userId)
            || (AuthController::hasRole('enterprise') && (int)($report['enterprise_id'] ?? 0) === $userId);
    }

    private function isReviewable(array $report): bool
    {
        return in_array($report['status'], [InternshipReport::STATUS_SUBMITTED, InternshipReport::STATUS_REVIEWING], true);
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
