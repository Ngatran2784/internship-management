<?php
/**
 * KIỂM THỬ XÉT DUYỆT ĐỀ TÀI
 * Chạy: C:\xampp\php\php.exe tests/test_topic_approval.php
 *
 * Yêu cầu: MySQL đang chạy, đã có các bảng topics và internship_periods, và đã chạy migration
 * database/migrations/create_topic_approvals_table.sql.
 * Toàn bộ dữ liệu test nằm trong 1 transaction và được ROLLBACK ở cuối.
 */

$root = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($root) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Controllers\AuthController;
use App\Controllers\TopicApprovalController;
use App\Core\Database;
use App\Models\InternshipPeriod;
use App\Models\Role;
use App\Models\Topic;
use App\Models\TopicApproval;
use App\Models\User;

$pass = 0;
$fail = 0;

function t(string $desc, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    echo ($cond ? '[ PASS ] ' : '[ FAIL ] ') . $desc . (!$cond && $detail !== '' ? " -> {$detail}" : '') . "\n";
    $cond ? $pass++ : $fail++;
}

function throwsInvalid(callable $fn): bool
{
    try {
        $fn();
    } catch (InvalidArgumentException $e) {
        return true;
    }
    return false;
}

echo "\n==== KIỂM THỬ XÉT DUYỆT ĐỀ TÀI ====\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "Không kết nối được CSDL: " . $e->getMessage() . "\n";
    exit(1);
}

foreach (['topics', 'internship_periods', 'topic_approvals'] as $table) {
    if ($pdo->query("SHOW TABLES LIKE '{$table}'")->fetchColumn() === false) {
        echo "Thiếu bảng `{$table}`.\n";
        echo "Hãy import các file trong database/migrations (create_internship_periods.sql, create_topics_table.sql, create_topic_approvals_table.sql) bằng phpMyAdmin.\n";
        exit(1);
    }
}

$pdo->beginTransaction();

try {
    foreach (['admin', 'training_dept', 'department_head', 'lecturer', 'student'] as $roleName) {
        if (Role::findByName($roleName) === null) {
            $pdo->prepare('INSERT INTO roles (name, display_name) VALUES (:n, :d)')
                ->execute(['n' => $roleName, 'd' => $roleName]);
        }
    }
    $roleId = fn(string $name) => (int)Role::findByName($name)['id'];

    $sfx = bin2hex(random_bytes(3));
    $mkUser = function (string $label, string $role) use ($sfx, $roleId): int {
        $id = User::create([
            'username'  => "{$label}_{$sfx}",
            'email'     => "{$label}_{$sfx}@example.com",
            'password'  => 'Matkhau123',
            'full_name' => "Nguoi {$label}",
        ]);
        User::assignRoles($id, [$roleId($role)]);
        return $id;
    };

    $proposer = $mkUser('gv', 'lecturer');
    $otherGv  = $mkUser('gv2', 'lecturer');
    $head     = $mkUser('bm', 'department_head');
    $student  = $mkUser('sv', 'student');

    $mkPeriod = function (string $label, string $status) use ($pdo, $sfx): int {
        $pdo->prepare(
            "INSERT INTO internship_periods (code, name, type, start_date, end_date, status)
             VALUES (:c, :n, 'internship', '2026-01-01', '2026-12-31', :s)"
        )->execute(['c' => "T{$label}{$sfx}", 'n' => "Dot test {$label}", 's' => $status]);
        return (int)$pdo->lastInsertId();
    };
    $activePeriod = $mkPeriod('A', 'active');
    $closedPeriod = $mkPeriod('C', 'closed');

    $n = 0;
    $mkTopic = function (int $periodId, string $status = 'draft') use ($pdo, $sfx, $proposer, &$n): int {
        $n++;
        $pdo->prepare(
            "INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status)
             VALUES (:c, :n, 'internship', :p, :u, 2, :s)"
        )->execute(['c' => "TP{$sfx}{$n}", 'n' => "De tai kiem thu {$n}", 'p' => $periodId, 'u' => $proposer, 's' => $status]);
        return (int)$pdo->lastInsertId();
    };
    $statusOf = fn(int $id) => Topic::findById($id)['status'];

    // --- Cấu trúc & route ---
    t('Có lớp TopicApproval', class_exists(TopicApproval::class));
    t('Có lớp TopicApprovalController', class_exists(TopicApprovalController::class));
    foreach (['pending', 'history', 'submit', 'approve', 'reject'] as $m) {
        t("TopicApprovalController::{$m}() là hàm public",
            method_exists(TopicApprovalController::class, $m) && (new ReflectionMethod(TopicApprovalController::class, $m))->isPublic());
    }
    $indexSrc = (string)file_get_contents($root . '/public/index.php');
    foreach ([
        ['get', '/topics/pending', 'pending'], ['get', '/topics/history', 'history'],
        ['post', '/topics/submit', 'submit'], ['post', '/topics/approve', 'approve'], ['post', '/topics/reject', 'reject'],
    ] as [$verb, $uri, $action]) {
        t("Đã đăng ký route {$verb} {$uri}",
            (bool)preg_match('/\$router->' . $verb . '\(\'' . preg_quote($uri, '/') . '\',\s*\[TopicApprovalController::class,\s*\'' . $action . '\'\]\)/', $indexSrc));
    }

    // --- Phân quyền ---
    t('Trưởng bộ môn được xét duyệt', TopicApproval::canReview(['department_head']) === true);
    t('Phòng đào tạo được xét duyệt', TopicApproval::canReview(['training_dept']) === true);
    t('Admin được xét duyệt', TopicApproval::canReview(['admin']) === true);
    t('Giảng viên KHÔNG được xét duyệt', TopicApproval::canReview(['lecturer']) === false);
    t('Sinh viên KHÔNG được xét duyệt', TopicApproval::canReview(['student']) === false);
    t('Doanh nghiệp KHÔNG được xét duyệt', TopicApproval::canReview(['enterprise']) === false);
    t('Không có vai trò nào thì không được xét duyệt', TopicApproval::canReview([]) === false);

    $draft = ['status' => 'draft', 'proposer_id' => $proposer];
    t('Người đề xuất được gửi duyệt đề tài nháp', TopicApproval::canSubmit($draft, $proposer, ['lecturer']) === true);
    t('Giảng viên khác KHÔNG được gửi duyệt đề tài của người khác', TopicApproval::canSubmit($draft, $otherGv, ['lecturer']) === false);
    t('Admin được gửi duyệt hộ', TopicApproval::canSubmit($draft, $otherGv, ['admin']) === true);
    t('Đề tài bị từ chối được gửi duyệt lại', TopicApproval::canSubmit(['status' => 'rejected', 'proposer_id' => $proposer], $proposer, ['lecturer']) === true);
    t('Đề tài đang chờ duyệt KHÔNG gửi duyệt lại được', TopicApproval::canSubmit(['status' => 'pending', 'proposer_id' => $proposer], $proposer, ['lecturer']) === false);
    t('Đề tài đã duyệt KHÔNG gửi duyệt lại được', TopicApproval::canSubmit(['status' => 'approved', 'proposer_id' => $proposer], $proposer, ['admin']) === false);

    // --- Gửi duyệt ---
    $t1 = $mkTopic($activePeriod);
    TopicApproval::submit($t1);
    t('Gửi duyệt: draft chuyển thành pending', $statusOf($t1) === 'pending');
    t('Gửi duyệt lần hai bị từ chối (đang chờ duyệt)', throwsInvalid(fn() => TopicApproval::submit($t1)));
    t('Gửi duyệt đề tài không tồn tại bị từ chối', throwsInvalid(fn() => TopicApproval::submit(999999999)));
    $tClosed = $mkTopic($closedPeriod);
    t('Gửi duyệt đề tài thuộc đợt đã kết thúc bị từ chối', throwsInvalid(fn() => TopicApproval::submit($tClosed)));
    t('Đề tài thuộc đợt đã kết thúc vẫn giữ trạng thái draft', $statusOf($tClosed) === 'draft');

    // --- Dữ liệu sai khi xét duyệt ---
    t('Quyết định không hợp lệ bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'maybe', null)));
    t('Từ chối mà không có lý do bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'rejected', '')));
    t('Từ chối chỉ có khoảng trắng bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'rejected', "   ")));
    t('Nhận xét quá dài bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'approved', str_repeat('a', 1001))));
    t('Xét duyệt đề tài không tồn tại bị từ chối', throwsInvalid(fn() => TopicApproval::review(999999999, $head, 'approved', null)));
    t('Dữ liệu sai không làm đổi trạng thái đề tài', $statusOf($t1) === 'pending');
    t('Dữ liệu sai không ghi lịch sử', TopicApproval::history($t1) === []);
    $tDraft = $mkTopic($activePeriod);
    t('Duyệt đề tài còn là bản nháp bị từ chối', throwsInvalid(fn() => TopicApproval::review($tDraft, $head, 'approved', null)));

    // --- Duyệt ---
    TopicApproval::review($t1, $head, 'approved', 'Đề tài phù hợp');
    t('Duyệt: pending chuyển thành approved', $statusOf($t1) === 'approved');
    $h = TopicApproval::history($t1);
    t('Duyệt: ghi đúng 1 dòng lịch sử', count($h) === 1);
    t('Lịch sử ghi đúng người xử lý, quyết định, nhận xét',
        (int)$h[0]['reviewer_id'] === $head && $h[0]['decision'] === 'approved' && $h[0]['comments'] === 'Đề tài phù hợp');
    t('Lịch sử có tên người xử lý và thời điểm', $h[0]['reviewer_name'] === 'Nguoi bm' && !empty($h[0]['created_at']));
    t('Duyệt lặp bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'approved', null)));
    t('Từ chối đề tài đã duyệt bị từ chối', throwsInvalid(fn() => TopicApproval::review($t1, $head, 'rejected', 'Đổi ý')));
    t('Duyệt lặp không tạo thêm lịch sử', count(TopicApproval::history($t1)) === 1);
    t('Đề tài đã duyệt vẫn là approved sau các lần thử lặp', $statusOf($t1) === 'approved');

    // --- Từ chối, sửa, gửi lại, duyệt ---
    $t2 = $mkTopic($activePeriod, 'pending');
    TopicApproval::review($t2, $head, 'rejected', 'Cần bổ sung mục tiêu');
    t('Từ chối: pending chuyển thành rejected', $statusOf($t2) === 'rejected');
    $h2 = TopicApproval::history($t2);
    t('Từ chối: ghi lý do vào lịch sử', count($h2) === 1 && $h2[0]['decision'] === 'rejected' && $h2[0]['comments'] === 'Cần bổ sung mục tiêu');
    t('Đề tài bị từ chối được người đề xuất sửa lại', Topic::canEdit(Topic::findById($t2), $proposer, ['lecturer']) === true);

    TopicApproval::submit($t2);
    t('Gửi duyệt lại: rejected chuyển thành pending', $statusOf($t2) === 'pending');
    TopicApproval::review($t2, $head, 'approved', null);
    t('Duyệt sau khi gửi lại: approved', $statusOf($t2) === 'approved');
    $h2 = TopicApproval::history($t2);
    t('Lịch sử giữ đủ 2 lần xử lý', count($h2) === 2);
    t('Lịch sử xếp mới nhất trước', $h2[0]['decision'] === 'approved' && $h2[1]['decision'] === 'rejected');
    t('Duyệt không kèm nhận xét lưu NULL', $h2[0]['comments'] === null);

    // --- Đợt đã kết thúc ---
    $tOld = $mkTopic($closedPeriod, 'pending');
    t('Xét duyệt đề tài thuộc đợt đã kết thúc bị từ chối', throwsInvalid(fn() => TopicApproval::review($tOld, $head, 'approved', null)));
    t('Đề tài thuộc đợt đã kết thúc vẫn là pending', $statusOf($tOld) === 'pending');

    // --- Toàn vẹn dữ liệu ---
    t('hasHistory: đề tài đã xét duyệt trả về true', TopicApproval::hasHistory($t1) === true);
    t('hasHistory: đề tài chưa xét duyệt trả về false', TopicApproval::hasHistory($tDraft) === false);

    $tRejected = $mkTopic($activePeriod, 'pending');
    TopicApproval::review($tRejected, $head, 'rejected', 'Không phù hợp');
    $deleteBlocked = false;
    try {
        Topic::delete($tRejected);
    } catch (Throwable $e) {
        $deleteBlocked = true;
    }
    t('Không xóa cứng được đề tài đã có lịch sử xét duyệt', $deleteBlocked);
    t('Đề tài đã có lịch sử vẫn còn trong CSDL', Topic::findById($tRejected) !== null);

    // --- Không ảnh hưởng các phân hệ khác ---
    t('Topic::all() vẫn hoạt động', is_array(Topic::all()));
    t('Topic::all() lọc theo trạng thái pending đúng', in_array($tOld, array_map(fn($x) => (int)$x['id'], Topic::all(['status' => 'pending'])), true));
    t('User::all() vẫn hoạt động', is_array(User::all()));
    t('InternshipPeriod::all() vẫn hoạt động', is_array(InternshipPeriod::all()));

    // --- CSRF ---
    $_SESSION = [];
    $token = AuthController::csrfToken();
    $_POST = ['_csrf' => 'sai'];
    t('CSRF token sai bị từ chối', AuthController::verifyCsrf() === false);
    $_POST = ['_csrf' => $token];
    t('CSRF token đúng được chấp nhận', AuthController::verifyCsrf() === true);
} catch (Throwable $e) {
    echo "\n[ LỖI ] " . get_class($e) . ': ' . $e->getMessage() . ' (dòng ' . $e->getLine() . ")\n";
    $fail++;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "\n----------------------------------------------\n";
echo "KẾT QUẢ: {$pass} PASS, {$fail} FAIL.\n";
echo "(Dữ liệu test đã được rollback, CSDL không bị thay đổi)\n\n";
exit($fail > 0 ? 1 : 0);
