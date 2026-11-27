<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use App\Models\Topic;
use App\Models\TopicAssignment;
use App\Models\TopicWish;
use App\Models\User;
use Throwable;

/**
 * DashboardController - Bảng điều khiển trung tâm đa vai trò
 * Hỗ trợ 6 vai trò: Admin, Phòng Đào tạo, Trưởng bộ môn, Giảng viên, Sinh viên, Doanh nghiệp
 */
class DashboardController extends Controller
{
    /**
     * GET /dashboard - Hiển thị bảng điều khiển tổng quan cá nhân hóa theo vai trò
     */
    public function index(): void
    {
        AuthController::requireLogin();
        $userId    = (int)AuthController::id();
        $userRoles = AuthController::roles();
        $primary   = AuthController::primaryRole();

        // 1. Quản lý danh sách đợt thực tập và xác định period_id an toàn
        try {
            $allPeriods = InternshipPeriod::all();
        } catch (Throwable $e) {
            error_log('DashboardController::index error loading periods: ' . $e->getMessage());
            $allPeriods = [];
        }

        $activePeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE));
        $draftPeriods  = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_DRAFT));
        $closedPeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_CLOSED));

        // Xác thực $_GET['period_id']
        $rawPeriodId = $_GET['period_id'] ?? null;
        $periodId = filter_var($rawPeriodId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($periodId === false) {
            $periodId = null;
        }

        // Kiểm tra đợt có tồn tại trong hệ thống không
        $selectedPeriod = null;
        if ($periodId !== null) {
            foreach ($allPeriods as $p) {
                if ((int)$p['id'] === $periodId) {
                    $selectedPeriod = $p;
                    break;
                }
            }
            if ($selectedPeriod === null) {
                $periodId = null; // ID không tồn tại
            }
        }

        // Sinh viên không được xem nội dung đợt nháp (draft)
        $isStudentOnly = in_array('student', $userRoles, true) && !in_array('admin', $userRoles, true) && !in_array('training_dept', $userRoles, true);
        if ($isStudentOnly && $selectedPeriod !== null && ($selectedPeriod['status'] ?? '') === InternshipPeriod::STATUS_DRAFT) {
            $selectedPeriod = null;
            $periodId = null;
        }

        // Doanh nghiệp chỉ tham gia các đợt thực tập doanh nghiệp (type = internship)
        $isEnterpriseOnly = in_array('enterprise', $userRoles, true) && !in_array('admin', $userRoles, true) && !in_array('training_dept', $userRoles, true);

        // Nếu chưa chọn hoặc không hợp lệ, giải quyết theo quy tắc mặc định:
        if ($periodId === null || ($isEnterpriseOnly && ($selectedPeriod['type'] ?? '') !== InternshipPeriod::TYPE_INTERNSHIP)) {
            if ($isEnterpriseOnly) {
                $entActive = array_values(array_filter($activePeriods, fn($p) => ($p['type'] ?? '') === InternshipPeriod::TYPE_INTERNSHIP));
                $entAll    = array_values(array_filter($allPeriods, fn($p) => ($p['type'] ?? '') === InternshipPeriod::TYPE_INTERNSHIP));
                if (!empty($entActive)) {
                    $selectedPeriod = $entActive[0];
                    $periodId = (int)$selectedPeriod['id'];
                } elseif (!empty($entAll)) {
                    $selectedPeriod = $entAll[0];
                    $periodId = (int)$selectedPeriod['id'];
                }
            } elseif (!empty($activePeriods)) {
                $selectedPeriod = $activePeriods[0];
                $periodId = (int)$selectedPeriod['id'];
            } elseif (!$isStudentOnly && !empty($allPeriods)) {
                $selectedPeriod = $allPeriods[0];
                $periodId = (int)$selectedPeriod['id'];
            }
        }

        // 2. Thu thập dữ liệu theo vai trò
        $roleData = [];

        // --- A. Dữ liệu cho Admin ---
        if (in_array('admin', $userRoles, true)) {
            try {
                $roleData['admin'] = [
                    'total_users'       => count(User::all()),
                    'count_students'    => User::countByRole('student'),
                    'count_lecturers'   => User::countByRole('lecturer'),
                    'count_enterprises' => User::countByRole('enterprise'),
                    'count_dept_heads'  => User::countByRole('department_head'),
                    'count_training'    => User::countByRole('training_dept'),
                    'approved_topics'   => Topic::approvedCountsByType(),
                ];
            } catch (Throwable $e) {
                error_log('DashboardController admin stats error: ' . $e->getMessage());
                $roleData['admin'] = [];
            }
        }

        // --- B. Dữ liệu cho Phòng Đào tạo (training_dept) ---
        if (in_array('training_dept', $userRoles, true) || in_array('admin', $userRoles, true)) {
            try {
                $pdtStats = $periodId ? TopicAssignment::getStatistics($periodId) : [];
                $pendingPdtTopics = $periodId 
                    ? Topic::all(['period_id' => $periodId, 'status' => Topic::STATUS_PENDING]) 
                    : Topic::all(['status' => Topic::STATUS_PENDING]);
                $roleData['training_dept'] = [
                    'statistics'         => $pdtStats,
                    'pending_pdt_count'  => count($pendingPdtTopics),
                    'pending_pdt_topics' => array_slice($pendingPdtTopics, 0, 5),
                ];
            } catch (Throwable $e) {
                error_log('DashboardController training_dept stats error: ' . $e->getMessage());
                $roleData['training_dept'] = [];
            }
        }

        // --- C. Dữ liệu cho Trưởng bộ môn (department_head) ---
        if (in_array('department_head', $userRoles, true)) {
            try {
                $pendingCbnTopics = $periodId 
                    ? Topic::all(['period_id' => $periodId, 'status' => Topic::STATUS_PENDING]) 
                    : Topic::all(['status' => Topic::STATUS_PENDING]);
                $approvedTopicsWithSlots = $periodId ? TopicAssignment::getApprovedTopicsWithSlots($periodId) : [];
                $roleData['department_head'] = [
                    'pending_cbn_count'  => count($pendingCbnTopics),
                    'pending_cbn_topics' => array_slice($pendingCbnTopics, 0, 5),
                    'topics_with_slots'  => array_slice($approvedTopicsWithSlots, 0, 5),
                    'total_approved'     => count($approvedTopicsWithSlots),
                ];
            } catch (Throwable $e) {
                error_log('DashboardController department_head stats error: ' . $e->getMessage());
                $roleData['department_head'] = [];
            }
        }

        // --- D. Dữ liệu cho Giảng viên (lecturer) ---
        if (in_array('lecturer', $userRoles, true)) {
            try {
                $myTopics = $periodId 
                    ? Topic::all(['proposer_id' => $userId, 'period_id' => $periodId]) 
                    : Topic::all(['proposer_id' => $userId]);
                $myStudents = $periodId 
                    ? TopicAssignment::getByLecturer($userId, $periodId) 
                    : [];
                
                $myApproved = 0; $myPending = 0; $myDraft = 0;
                foreach ($myTopics as $t) {
                    if ($t['status'] === Topic::STATUS_APPROVED) $myApproved++;
                    elseif (str_starts_with($t['status'], 'pending')) $myPending++;
                    elseif ($t['status'] === Topic::STATUS_DRAFT) $myDraft++;
                }

                $roleData['lecturer'] = [
                    'topics'         => array_slice($myTopics, 0, 5),
                    'total_topics'   => count($myTopics),
                    'approved_count' => $myApproved,
                    'pending_count'  => $myPending,
                    'draft_count'    => $myDraft,
                    'students'       => $myStudents,
                    'total_students' => count($myStudents),
                ];
            } catch (Throwable $e) {
                error_log('DashboardController lecturer stats error: ' . $e->getMessage());
                $roleData['lecturer'] = [];
            }
        }

        // --- E. Dữ liệu cho Sinh viên (student) ---
        if (in_array('student', $userRoles, true)) {
            try {
                $myWishes = $periodId ? TopicWish::getByStudentAndPeriod($userId, $periodId) : [];
                $isPeriodPublished = $periodId ? TopicAssignment::hasPublishedAssignments($periodId) : false;
                $myAssignment = $periodId ? TopicAssignment::getByStudent($userId, $periodId, true) : null;
                $roleData['student'] = [
                    'wishes'             => $myWishes,
                    'assignment'         => $myAssignment,
                    'has_assignment'     => $myAssignment !== null,
                    'is_published'       => $isPeriodPublished,
                    'can_register'       => $periodId ? TopicWish::isPeriodOpenForRegistration($periodId) : false,
                ];
            } catch (Throwable $e) {
                error_log('DashboardController student stats error: ' . $e->getMessage());
                $roleData['student'] = [];
            }
        }

        // --- F. Dữ liệu cho Doanh nghiệp (enterprise) ---
        if (in_array('enterprise', $userRoles, true)) {
            try {
                $myEnterpriseTopics = $periodId 
                    ? Topic::all(['proposer_id' => $userId, 'period_id' => $periodId]) 
                    : Topic::all(['proposer_id' => $userId]);
                
                $entApproved = 0; $entPending = 0; $totalSlots = 0;
                foreach ($myEnterpriseTopics as $t) {
                    if ($t['status'] === Topic::STATUS_APPROVED) {
                        $entApproved++;
                        $totalSlots += (int)($t['max_students'] ?? 1);
                    } elseif (str_starts_with($t['status'], 'pending')) {
                        $entPending++;
                    }
                }

                $roleData['enterprise'] = [
                    'topics'         => array_slice($myEnterpriseTopics, 0, 5),
                    'total_topics'   => count($myEnterpriseTopics),
                    'approved_count' => $entApproved,
                    'pending_count'  => $entPending,
                    'total_capacity' => $totalSlots,
                ];
            } catch (Throwable $e) {
                error_log('DashboardController enterprise stats error: ' . $e->getMessage());
                $roleData['enterprise'] = [];
            }
        }

        $approvedTopicCounts = Topic::approvedCountsByType();

        $this->render('dashboard/index', [
            'allPeriods'          => $allPeriods,
            'activePeriods'       => $activePeriods,
            'draftPeriods'        => $draftPeriods,
            'closedPeriods'       => $closedPeriods,
            'selectedPeriod'      => $selectedPeriod,
            'selectedPeriodId'    => $periodId,
            'totalPeriods'        => count($allPeriods),
            'countActive'         => count($activePeriods),
            'countDraft'          => count($draftPeriods),
            'countClosed'         => count($closedPeriods),
            'typeLabels'          => InternshipPeriod::TYPE_LABELS,
            'statusLabels'        => InternshipPeriod::STATUS_LABELS,
            'approvedTopicCounts' => $approvedTopicCounts,
            'topicStatsAvailable' => true,
            'primaryRole'         => $primary,
            'userRoles'           => $userRoles,
            'roleData'            => $roleData,
        ]);
    }
}
