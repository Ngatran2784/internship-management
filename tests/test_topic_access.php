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

use App\Models\Topic;
use App\Models\TopicApproval;

$passed = 0;
$failed = 0;

function assertTopicAccess(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "[ PASS ] {$name}\n";
    } else {
        $failed++;
        echo "[ FAIL ] {$name}\n";
    }
}

$draft = ['status' => Topic::STATUS_DRAFT, 'proposer_id' => 7, 'period_status' => 'active'];
$approved = ['status' => Topic::STATUS_APPROVED, 'proposer_id' => 8, 'period_status' => 'active'];
$approvedClosed = ['status' => Topic::STATUS_APPROVED, 'proposer_id' => 8, 'period_status' => 'closed'];

assertTopicAccess('Admin xem được đề tài nháp của người khác', Topic::canView($draft, 9, ['admin']));
assertTopicAccess('Phòng đào tạo xem được đề tài nháp của người khác', Topic::canView($draft, 9, ['training_dept']));
assertTopicAccess('Trưởng bộ môn xem được đề tài đã duyệt', Topic::canView($approved, 9, ['department_head']));
assertTopicAccess('Giảng viên xem được đề tài đã duyệt', Topic::canView($approved, 9, ['lecturer']));
assertTopicAccess('Doanh nghiệp xem được đề tài đã duyệt', Topic::canView($approved, 9, ['enterprise']));
assertTopicAccess('Người đề xuất xem được bản nháp của mình', Topic::canView($draft, 7, ['lecturer']));
assertTopicAccess('Giảng viên không xem được bản nháp của người khác', !Topic::canView($draft, 9, ['lecturer']));
assertTopicAccess('Sinh viên xem được đề tài đã duyệt thuộc đợt đang mở', Topic::canView($approved, 9, ['student']));
assertTopicAccess('Sinh viên không xem được đề tài thuộc đợt đã đóng', !Topic::canView($approvedClosed, 9, ['student']));
assertTopicAccess('Sinh viên không xem được đề tài chưa duyệt', !Topic::canView($draft, 7, ['student']));
assertTopicAccess('Tài khoản không có vai trò không xem được đề tài', !Topic::canView($approved, 9, []));
assertTopicAccess('Chỉ vai trò duyệt được phép xét duyệt', TopicApproval::canReview(['department_head']));
assertTopicAccess('Giảng viên không có quyền xét duyệt', !TopicApproval::canReview(['lecturer']));

echo "\nKẾT QUẢ: {$passed} PASS, {$failed} FAIL.\n";
exit($failed > 0 ? 1 : 0);
