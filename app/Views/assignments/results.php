<?php
$assignments = is_array($assignments ?? null) ? $assignments : [];
$unassigned = is_array($unassigned ?? null) ? $unassigned : [];
$statusLabels = is_array($statusLabels ?? null) ? $statusLabels : [];
$statusBadges = is_array($statusBadges ?? null) ? $statusBadges : [];
$methodLabels = is_array($methodLabels ?? null) ? $methodLabels : [];
$methodBadges = is_array($methodBadges ?? null) ? $methodBadges : [];
$selectedPeriod = is_array($selectedPeriod ?? null) ? $selectedPeriod : [];
$escape = $escape ?? static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);
?>
<section id="assignment-results" aria-labelledby="assignment-results-heading">
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0" id="assignment-results-heading">Kết quả đối sánh nguyện vọng NV1–NV3</h3>
            <span class="badge bg-blue-lt text-blue"><?= count($assignments) ?> phân công</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table assignment-table" data-assignment-table="assigned">
                <thead>
                    <tr>
                        <th>Mã sinh viên</th>
                        <th>Sinh viên</th>
                        <th>Đợt thực tập / đồ án</th>
                        <th>Nguyện vọng được đáp ứng</th>
                        <th>Đề tài được phân công</th>
                        <th>Giảng viên hướng dẫn</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <?php
                        $priority = (int)($assignment['matched_wish_priority'] ?? 0);
                        $status = (string)($assignment['status'] ?? '');
                        $method = (string)($assignment['assignment_method'] ?? '');
                        $searchText = implode(' ', [
                            $assignment['student_code'] ?? '',
                            $assignment['student_name'] ?? '',
                        ]);
                        ?>
                        <tr class="assignment-filter-row"
                            data-search="<?= $escape(mb_strtolower($searchText, 'UTF-8')) ?>"
                            data-status="<?= $escape($status) ?>">
                            <td class="text-nowrap fw-semibold"><?= $escape($assignment['student_code'] ?? '') ?></td>
                            <td>
                                <div class="fw-semibold"><?= $escape($assignment['student_name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($assignment['student_email'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= $escape($selectedPeriod['name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($selectedPeriod['code'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if ($priority >= 1 && $priority <= 3): ?>
                                    <span class="badge bg-<?= $priority === 1 ? 'success' : ($priority === 2 ? 'blue' : 'orange') ?>-lt text-<?= $priority === 1 ? 'success' : ($priority === 2 ? 'blue' : 'orange') ?>">
                                        NV<?= $priority ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary">Không ghi nhận</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= $escape($assignment['topic_name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($assignment['topic_code'] ?? '') ?></div>
                            </td>
                            <td><?= $escape($assignment['lecturer_name'] ?? '') ?></td>
                            <td>
                                <span class="<?= $escape($methodBadges[$method] ?? 'badge bg-secondary-lt text-secondary') ?>">
                                    <?= $escape($methodLabels[$method] ?? $method) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $escape($statusBadges[$status] ?? 'bg-secondary-lt text-secondary') ?>">
                                    <?= $escape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="assignment-empty-filter" <?= $assignments ? 'hidden' : '' ?>>
                        <td colspan="8" class="text-center text-secondary py-4">
                            <?= $assignments ? 'Không có kết quả phù hợp bộ lọc.' : 'Đợt này chưa có sinh viên được phân công.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"
             data-assignment-pagination="assigned" hidden>
            <span class="text-secondary small" data-page-summary aria-live="polite"></span>
            <nav aria-label="Phân trang kết quả phân công" class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-page-previous>
                    <i class="ti ti-chevron-left me-1" aria-hidden="true"></i>Trước
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-page-next>
                    Sau<i class="ti ti-chevron-right ms-1" aria-hidden="true"></i>
                </button>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0">Sinh viên chưa được phân công</h3>
            <span class="badge bg-warning-lt text-warning"><?= count($unassigned) ?> sinh viên</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table assignment-table" data-assignment-table="unassigned">
                <thead>
                    <tr>
                        <th>Mã sinh viên</th>
                        <th>Họ và tên</th>
                        <th>Đợt thực tập / đồ án</th>
                        <th>Số nguyện vọng</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassigned as $student): ?>
                        <?php
                        $studentSearch = mb_strtolower(
                            (string)($student['student_code'] ?? '') . ' ' . (string)($student['student_name'] ?? ''),
                            'UTF-8'
                        );
                        ?>
                        <tr class="assignment-filter-row" data-search="<?= $escape($studentSearch) ?>" data-status="unassigned">
                            <td class="fw-semibold"><?= $escape($student['student_code'] ?? '') ?></td>
                            <td><?= $escape($student['student_name'] ?? '') ?></td>
                            <td>
                                <div><?= $escape($selectedPeriod['name'] ?? '') ?></div>
                                <div class="text-secondary small"><?= $escape($selectedPeriod['code'] ?? '') ?></div>
                            </td>
                            <td><?= (int)($student['wishes_count'] ?? 0) ?></td>
                            <td><span class="badge bg-warning-lt text-warning">Chưa phân công</span></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="unassigned-empty-filter" <?= $unassigned ? 'hidden' : '' ?>>
                        <td colspan="5" class="text-center text-secondary py-4">
                            <?= $unassigned ? 'Không có sinh viên phù hợp bộ lọc.' : 'Không có sinh viên chưa được phân công.' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between gap-2"
             data-assignment-pagination="unassigned" hidden>
            <span class="text-secondary small" data-page-summary aria-live="polite"></span>
            <nav aria-label="Phân trang danh sách chưa phân công" class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-page-previous>
                    <i class="ti ti-chevron-left me-1" aria-hidden="true"></i>Trước
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-page-next>
                    Sau<i class="ti ti-chevron-right ms-1" aria-hidden="true"></i>
                </button>
            </nav>
        </div>
    </div>
</section>
