<?php
$pageTitle = 'Phân công thủ công';
$pageHeading = 'Phân công thủ công';
$pageDescription = 'PHÂN CÔNG SINH VIÊN VÀO ĐỀ TÀI';
$activePage = 'assignments';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars(
    is_scalar($value) ? (string)$value : '',
    ENT_QUOTES,
    'UTF-8'
);
$periods = is_array($periods ?? null) ? $periods : [];
$students = is_array($students ?? null) ? $students : [];
$topics = is_array($topics ?? null) ? $topics : [];
$lecturers = is_array($lecturers ?? null) ? $lecturers : [];
$errors = is_array($errors ?? null) ? $errors : [];
$old = is_array($old ?? null) ? $old : [];
$csrfToken = $csrfToken ?? ($csrf_token ?? '');
$submitUrl = is_string($submitUrl ?? null) ? trim($submitUrl) : '';
$canSubmit = !empty($canSubmit) && $submitUrl !== '' && $csrfToken !== '';
$roles = is_array($_SESSION['roles'] ?? null) ? $_SESSION['roles'] : [];
$canManage = !empty(array_intersect($roles, ['admin', 'training_dept', 'department_head', 'faculty_staff']));
?>
<div class="container-xl assignment-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h2 class="h3 mb-1">Phân công thủ công</h2>
            <p class="text-secondary mb-0">Chọn đợt, sinh viên, đề tài và giảng viên hướng dẫn.</p>
        </div>
        <a href="<?= $escape($baseUrl) ?>/assignments" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách
        </a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status"><?= $escape($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= $escape($error) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-1">Vui lòng kiểm tra lại thông tin:</div>
            <ul class="mb-0">
                <?php foreach ($errors as $validationError): ?>
                    <li><?= $escape($validationError) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$canManage): ?>
        <div class="alert alert-warning" role="alert">Tài khoản hiện tại không có quyền quản lý phân công.</div>
    <?php elseif (!$canSubmit): ?>
        <div class="alert alert-info" role="note">
            Giao diện đã sẵn sàng, nhưng Backend chưa có route tạo phân công thủ công.
            Biểu mẫu đang ở chế độ xem; chưa thể gửi dữ liệu hoặc ghi nhận phân công.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Thông tin phân công</h3>
        </div>
        <div class="card-body">
            <form method="post"<?= $canSubmit ? ' action="' . $escape($submitUrl) . '"' : ' onsubmit="return false;"' ?>>
                <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="assignment-create-period">Đợt thực tập / đồ án</label>
                        <select class="form-select" id="assignment-create-period" name="period_id" required>
                            <option value="">Chọn đợt</option>
                            <?php foreach ($periods as $period): ?>
                                <?php $periodId = (int)($period['id'] ?? 0); ?>
                                <option value="<?= $periodId ?>"
                                    <?= (string)($old['period_id'] ?? '') === (string)$periodId ? 'selected' : '' ?>>
                                    <?= $escape($period['code'] ?? '') ?> — <?= $escape($period['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="assignment-create-student">Sinh viên</label>
                        <select class="form-select" id="assignment-create-student" name="student_id" required>
                            <option value="">Chọn sinh viên</option>
                            <?php foreach ($students as $student): ?>
                                <?php $studentId = (int)($student['id'] ?? $student['student_id'] ?? 0); ?>
                                <option value="<?= $studentId ?>"
                                    <?= (string)($old['student_id'] ?? '') === (string)$studentId ? 'selected' : '' ?>>
                                    <?= $escape($student['username'] ?? $student['student_code'] ?? '') ?>
                                    — <?= $escape($student['full_name'] ?? $student['student_name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="assignment-create-topic">Đề tài</label>
                        <select class="form-select" id="assignment-create-topic" name="topic_id" required>
                            <option value="">Chọn đề tài</option>
                            <?php foreach ($topics as $topic): ?>
                                <?php $topicId = (int)($topic['id'] ?? 0); ?>
                                <option value="<?= $topicId ?>"
                                    <?= (string)($old['topic_id'] ?? '') === (string)$topicId ? 'selected' : '' ?>>
                                    <?= $escape($topic['code'] ?? '') ?> — <?= $escape($topic['name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label required" for="assignment-create-lecturer">Giảng viên hướng dẫn</label>
                        <select class="form-select" id="assignment-create-lecturer" name="lecturer_id" required>
                            <option value="">Chọn giảng viên</option>
                            <?php foreach ($lecturers as $lecturer): ?>
                                <?php $lecturerId = (int)($lecturer['id'] ?? 0); ?>
                                <option value="<?= $lecturerId ?>"
                                    <?= (string)($old['lecturer_id'] ?? '') === (string)$lecturerId ? 'selected' : '' ?>>
                                    <?= $escape($lecturer['username'] ?? '') ?>
                                    <?= !empty($lecturer['username']) ? ' — ' : '' ?>
                                    <?= $escape($lecturer['full_name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= $escape($baseUrl) ?>/assignments" class="btn btn-outline-secondary">Hủy</a>
                    <button type="submit" class="btn btn-primary" <?= $canSubmit ? '' : 'disabled' ?>>
                        <i class="ti ti-device-floppy me-1"></i>Xác nhận phân công
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
