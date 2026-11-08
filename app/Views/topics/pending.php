<?php
$pageTitle = 'Đề tài chờ duyệt';
$pageHeading = 'Quản lý đề tài chờ xét duyệt';
$pageDescription = 'HỆ THỐNG QUẢN LÝ THỰC TẬP VÀ ĐỒ ÁN SINH VIÊN';
$activePage = 'topics';
require dirname(__DIR__) . '/layouts/header.php';

$escape = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$topics = $topics ?? [];
$periods = $periods ?? [];
$filters = $filters ?? [];
$statusBadges = $statusBadges ?? [];
$typeBadges = $typeBadges ?? [];
$typeLabels = $typeLabels ?? [];
?>
<div class="container-xl">
    <?php if (!empty($success)): ?><div class="alert alert-success" role="alert"><?= $escape($success) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= $escape($error) ?></div><?php endif; ?>
    <div class="mb-3">
        <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl) ?>/topics">
            <i class="ti ti-arrow-left me-1"></i>Quay lại danh sách đề tài
        </a>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">
            <form action="<?= $escape($baseUrl) ?>/topics/pending" method="GET" class="row g-2 align-items-end w-100">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="period-filter">Đợt</label>
                    <select class="form-select" name="period_id" id="period-filter">
                        <option value="">Tất cả các đợt</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int)$period['id'] ?>" <?= (string)($filters['period_id'] ?? '') === (string)$period['id'] ? 'selected' : '' ?>>
                                <?= $escape($period['code'] . ' — ' . $period['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="type-filter">Loại đề tài</label>
                    <select class="form-select" name="type" id="type-filter">
                        <option value="">Tất cả</option>
                        <?php foreach ($typeLabels as $key => $label): ?>
                            <option value="<?= $escape($key) ?>" <?= ($filters['type'] ?? '') === $key ? 'selected' : '' ?>><?= $escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md">
                    <label class="form-label" for="keyword">Tìm kiếm</label>
                    <input class="form-control" id="keyword" name="keyword" value="<?= $escape($filters['keyword'] ?? '') ?>" placeholder="Mã, tên hoặc người đề xuất">
                </div>
                <div class="col-auto"><button class="btn btn-primary" type="submit"><i class="ti ti-search me-1"></i>Lọc</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Mã / tên đề tài</th><th>Đợt</th><th>Loại</th><th>Người đề xuất</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php if (!$topics): ?>
                        <tr><td colspan="6" class="text-center text-secondary py-5">Không có đề tài đang chờ xét duyệt.</td></tr>
                    <?php else: foreach ($topics as $topic): ?>
                        <tr>
                            <td><a class="fw-semibold" href="<?= $escape($baseUrl) ?>/topics/detail?id=<?= (int)$topic['id'] ?>"><?= $escape($topic['name']) ?></a>
                                <div class="text-secondary small"><?= $escape($topic['code']) ?></div></td>
                            <td><?= $escape($topic['period_name']) ?><div class="text-secondary small"><?= $escape($topic['period_code']) ?></div></td>
                            <td><span class="badge <?= $escape($typeBadges[$topic['type']] ?? 'bg-secondary-lt') ?>"><?= $escape($typeLabels[$topic['type']] ?? $topic['type']) ?></span></td>
                            <td><?= $escape($topic['proposer_name']) ?></td>
                            <td><span class="badge <?= $escape($statusBadges[$topic['status']] ?? 'bg-warning-lt') ?>">Chờ duyệt</span></td>
                            <td>
                                <?php if (($topic['period_status'] ?? null) === 'closed'): ?>
                                    <span class="text-secondary small">Đợt đã kết thúc</span>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2" style="min-width: 13rem">
                                        <button class="btn btn-sm btn-success w-100 js-approve-topic" type="button"
                                                data-topic-id="<?= (int)$topic['id'] ?>"
                                                data-topic-name="<?= $escape($topic['name']) ?>"
                                                data-topic-code="<?= $escape($topic['code']) ?>"
                                                data-period-name="<?= $escape($topic['period_name']) ?>"
                                                data-period-code="<?= $escape($topic['period_code']) ?>">
                                            <i class="ti ti-check me-1"></i>Phê duyệt
                                        </button>
                                        <form action="<?= $escape($baseUrl) ?>/topics/reject" method="POST"
                                              onsubmit="return confirm('Xác nhận từ chối đề tài này?');">
                                            <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$topic['id'] ?>">
                                            <label class="form-label visually-hidden" for="reason-<?= (int)$topic['id'] ?>">Lý do từ chối</label>
                                            <textarea class="form-control form-control-sm mb-1" id="reason-<?= (int)$topic['id'] ?>"
                                                      name="comments" rows="2" maxlength="1000" required placeholder="Lý do từ chối (bắt buộc)"></textarea>
                                            <button class="btn btn-sm btn-outline-danger w-100" type="submit"><i class="ti ti-x me-1"></i>Từ chối</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modalApproveTopic" tabindex="-1" aria-labelledby="modalApproveTopicTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-3 text-success" id="modalApproveTopicTitle">
                    <i class="ti ti-circle-check me-2" aria-hidden="true"></i>Xác nhận phê duyệt đề tài
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <span class="avatar avatar-xl bg-success-lt text-success rounded-circle mb-3">
                        <i class="ti ti-circle-check fs-1" aria-hidden="true"></i>
                    </span>
                    <p class="mb-1 text-dark fw-semibold">Bạn có chắc chắn muốn phê duyệt đề tài này?</p>
                </div>
                <dl class="row mb-4">
                    <dt class="col-sm-4 text-secondary">Tên đề tài</dt>
                    <dd class="col-sm-8 fw-semibold text-break" id="approve-topic-name"></dd>
                    <dt class="col-sm-4 text-secondary">Mã đề tài</dt>
                    <dd class="col-sm-8" id="approve-topic-code"></dd>
                    <dt class="col-sm-4 text-secondary">Đợt thực tập / đồ án</dt>
                    <dd class="col-sm-8 mb-0 text-break" id="approve-period"></dd>
                </dl>
                <div class="alert bg-success-lt text-success py-2 px-3 small mb-0">
                    <i class="ti ti-info-circle me-1" aria-hidden="true"></i>
                    Sau khi phê duyệt, đề tài sẽ được chuyển sang trạng thái Đã duyệt và có thể tiếp tục thực hiện các bước tiếp theo theo quy trình.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1" aria-hidden="true"></i>Hủy
                </button>
                <form id="form-approve-topic" action="<?= $escape($baseUrl) ?>/topics/approve" method="POST" class="m-0">
                    <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
                    <input type="hidden" name="id" id="approve-topic-id" value="">
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-check me-1" aria-hidden="true"></i>Xác nhận phê duyệt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var modalElement = document.getElementById('modalApproveTopic');
    var approvalForm = document.getElementById('form-approve-topic');

    document.querySelectorAll('.js-approve-topic').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('approve-topic-id').value = button.dataset.topicId || '';
            document.getElementById('approve-topic-name').textContent = button.dataset.topicName || '';
            document.getElementById('approve-topic-code').textContent = button.dataset.topicCode || '';
            document.getElementById('approve-period').textContent = [
                button.dataset.periodName || '',
                button.dataset.periodCode || ''
            ].filter(Boolean).join(' — ');

            var modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
            modal.show();
        });
    });

    approvalForm.addEventListener('submit', function () {
        var modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
    });
})();
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
