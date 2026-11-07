<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\InternshipPeriod;
use Throwable;

/**
 * DashboardController - Bảng điều khiển trung tâm
 * Đồng bộ dữ liệu đợt thực tập và đồ án từ bảng `internship_periods`
 */
class DashboardController extends Controller
{
    /**
     * GET /dashboard - Hiển thị bảng điều khiển tổng quan
     */
    public function index(): void
    {
        AuthController::requireLogin();

        try {
            $allPeriods = InternshipPeriod::all();
        } catch (Throwable $e) {
            error_log('DashboardController::index error loading periods: ' . $e->getMessage());
            $allPeriods = [];
        }

        $totalPeriods  = count($allPeriods);
        $activePeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE));
        $draftPeriods  = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_DRAFT));
        $closedPeriods = array_values(array_filter($allPeriods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_CLOSED));

        $countActive   = count($activePeriods);
        $countDraft    = count($draftPeriods);
        $countClosed   = count($closedPeriods);

        $this->render('dashboard/index', [
            'allPeriods'    => $allPeriods,
            'activePeriods' => $activePeriods,
            'totalPeriods'  => $totalPeriods,
            'countActive'   => $countActive,
            'countDraft'    => $countDraft,
            'countClosed'   => $countClosed,
            'typeLabels'    => InternshipPeriod::TYPE_LABELS,
            'statusLabels'  => InternshipPeriod::STATUS_LABELS,
        ]);
    }
}
