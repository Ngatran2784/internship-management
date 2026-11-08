<?php
declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }

    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

$_SERVER['SCRIPT_NAME'] = '/public/index.php';
$_SERVER['REQUEST_URI'] = '/public/topics/pending';
$_SESSION = ['roles' => ['department_head']];
$baseUrl = '/public';
$csrfToken = 'modal-test-csrf-token';
$success = null;
$error = null;
$filters = [];
$periods = [];
$statusBadges = ['pending' => 'bg-warning-lt'];
$typeBadges = ['internship' => 'bg-blue-lt'];
$typeLabels = ['internship' => 'Thực tập tốt nghiệp'];
$topics = [
    [
        'id' => 101,
        'name' => 'Đề tài thứ nhất',
        'code' => 'DT-101',
        'period_name' => 'Đợt thực tập thứ nhất',
        'period_code' => 'TT-2026-1',
        'type' => 'internship',
        'status' => 'pending',
        'period_status' => 'active',
        'proposer_name' => 'Giảng viên A',
    ],
    [
        'id' => 202,
        'name' => 'Đề tài thứ hai',
        'code' => 'DT-202',
        'period_name' => 'Đợt đồ án thứ hai',
        'period_code' => 'DA-2026-2',
        'type' => 'internship',
        'status' => 'pending',
        'period_status' => 'active',
        'proposer_name' => 'Giảng viên B',
    ],
];

ob_start();
require $root . '/app/Views/topics/pending.php';
$html = ob_get_clean();
$checks = [
    'back-to-topic-list button uses the existing topics route' => str_contains(
        $html,
        '<a class="btn btn-outline-secondary" href="/public/topics">'
    ) && str_contains($html, '<i class="ti ti-arrow-left me-1"></i>Quay lại danh sách đề tài'),
    'confirm dialog removed' => !str_contains($html, "confirm('Xác nhận phê duyệt"),
    'approval modal exists' => str_contains($html, 'id="modalApproveTopic"'),
    'each approval button is non-submit' => substr_count($html, 'class="btn btn-sm btn-success w-100 js-approve-topic" type="button"') === 2,
    'first topic data is present' => str_contains($html, 'data-topic-id="101"') && str_contains($html, 'data-topic-name="Đề tài thứ nhất"'),
    'second topic data is present' => str_contains($html, 'data-topic-id="202"') && str_contains($html, 'data-topic-name="Đề tài thứ hai"'),
    'period information is present' => str_contains($html, 'data-period-name="Đợt đồ án thứ hai"') && str_contains($html, 'data-period-code="DA-2026-2"'),
    'approval submits to existing POST route' => str_contains($html, 'id="form-approve-topic" action="/public/topics/approve" method="POST"'),
    'approval POST includes CSRF and selected ID fields' => (bool)preg_match('/<input type="hidden" name="_csrf" value="[^"]+">/', $html)
        && str_contains($html, 'name="id" id="approve-topic-id"'),
    'cancel and close do not submit approval form' => substr_count($html, 'data-bs-dismiss="modal"') >= 2,
    'confirmation is the only approval submit button' => substr_count($html, 'type="submit" class="btn btn-success"') === 1,
    'modal content is assigned through textContent' => str_contains($html, 'approve-topic-name')
        && str_contains($html, '.textContent = button.dataset.topicName'),
];

$failed = false;
foreach ($checks as $name => $passed) {
    echo ($passed ? '[ PASS ] ' : '[ FAIL ] ') . $name . "\n";
    $failed = $failed || !$passed;
}

exit($failed ? 1 : 0);
