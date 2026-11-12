<?php
/**
 * KIỂM THỬ PHÂN CÔNG THỦ CÔNG, XÁC NHẬN VÀ CÔNG BỐ
 * Chạy: C:\xampp\php\php.exe tests/test_topic_assignments.php
 *
 * Yêu cầu: MySQL đang chạy và đã import các bảng topics, topic_wishes, topic_assignments.
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
use App\Controllers\TopicAssignmentController;
use App\Core\Database;
use App\Models\Role;
use App\Models\TopicAssignment;
use App\Models\User;

$pass = 0;
$fail = 0;
$skip = 0;

function t(string $desc, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    echo ($cond ? '[ PASS ] ' : '[ FAIL ] ') . $desc . (!$cond && $detail !== '' ? " -> {$detail}" : '') . "\n";
    $cond ? $pass++ : $fail++;
}

function skip(string $desc, string $reason): void
{
    global $skip;
    echo "[ SKIP ] {$desc} ({$reason})\n";
    $skip++;
}

/** Gọi $fn và trả về lớp ngoại lệ ném ra (hoặc null nếu không ném) */
function thrown(callable $fn): ?string
{
    try {
        $fn();
    } catch (Throwable $e) {
        return get_class($e);
    }
    return null;
}

echo "\n==== KIỂM THỬ PHÂN CÔNG THỦ CÔNG, XÁC NHẬN VÀ CÔNG BỐ ====\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "Không kết nối được CSDL: " . $e->getMessage() . "\n";
    exit(1);
}

foreach (['topics', 'internship_periods', 'topic_assignments'] as $table) {
    if ($pdo->query("SHOW TABLES LIKE '{$table}'")->fetchColumn() === false) {
        echo "Thiếu bảng `{$table}`. Hãy import các file trong database/migrations bằng phpMyAdmin.\n";
        exit(1);
    }
}

$pdo->beginTransaction();

try {
    foreach (['admin', 'training_dept', 'department_head', 'lecturer', 'student', 'enterprise'] as $roleName) {
        if (Role::findByName($roleName) === null) {
            $pdo->prepare('INSERT INTO roles (name, display_name) VALUES (:n, :d)')
                ->execute(['n' => $roleName, 'd' => $roleName]);
        }
    }
    $roleId = fn(string $name) => (int)Role::findByName($name)['id'];

    $sfx = bin2hex(random_bytes(3));
    $mkUser = function (string $label, string $role, int $status = 1) use ($sfx, $roleId): int {
        $id = User::create([
            'username'  => "{$label}_{$sfx}",
            'email'     => "{$label}_{$sfx}@example.com",
            'password'  => 'Matkhau123',
            'full_name' => "Nguoi {$label}",
            'status'    => $status,
        ]);
        User::assignRoles($id, [$roleId($role)]);
        return $id;
    };

    $admin  = $mkUser('ad', 'admin');
    $gv1    = $mkUser('gv1', 'lecturer');
    $gv2    = $mkUser('gv2', 'lecturer');
    $sv     = [];
    for ($i = 1; $i <= 6; $i++) {
        $sv[$i] = $mkUser("sv{$i}", 'student');
    }
    $svLocked = $mkUser('svlock', 'student', 0);
    $company  = $mkUser('dn', 'enterprise');

    $mkPeriod = function (string $label, string $status) use ($pdo, $sfx): int {
        $pdo->prepare(
            "INSERT INTO internship_periods (code, name, type, start_date, end_date, status)
             VALUES (:c, :n, 'internship', '2026-01-01', '2026-12-31', :s)"
        )->execute(['c' => "P{$label}{$sfx}", 'n' => "Dot test {$label}", 's' => $status]);
        return (int)$pdo->lastInsertId();
    };
    $pA = $mkPeriod('A', 'active');
    $pC = $mkPeriod('C', 'closed');
    $pD = $mkPeriod('D', 'active');

    $n = 0;
    $mkTopic = function (int $periodId, int $proposer, int $max, string $status = 'approved') use ($pdo, $sfx, &$n): int {
        $n++;
        $pdo->prepare(
            "INSERT INTO topics (code, name, type, period_id, proposer_id, max_students, status)
             VALUES (:c, :n, 'internship', :p, :u, :m, :s)"
        )->execute(['c' => "TA{$sfx}{$n}", 'n' => "De tai phan cong {$n}", 'p' => $periodId, 'u' => $proposer, 'm' => $max, 's' => $status]);
        return (int)$pdo->lastInsertId();
    };
    $tBig     = $mkTopic($pA, $gv1, 3);
    $tOne     = $mkTopic($pA, $gv1, 1);
    $tDraft   = $mkTopic($pA, $gv1, 2, 'draft');
    $tOther   = $mkTopic($pD, $gv2, 2);
    $tClosed  = $mkTopic($pC, $gv1, 2);

    $statusOf = fn(int $studentId, int $periodId) => TopicAssignment::getByStudent($studentId, $periodId)['status'] ?? null;
    $rawStatus = function (int $assignmentId) use ($pdo) {
        $stmt = $pdo->prepare('SELECT status FROM topic_assignments WHERE id = :id');
        $stmt->execute(['id' => $assignmentId]);
        return $stmt->fetchColumn();
    };

    // --- Cấu trúc & route ---
    t('Có lớp TopicAssignment và TopicAssignmentController', class_exists(TopicAssignment::class) && class_exists(TopicAssignmentController::class));
    foreach (['manual', 'assignManual', 'cancel', 'check', 'publish'] as $m) {
        t("TopicAssignmentController::{$m}() là hàm public",
            method_exists(TopicAssignmentController::class, $m) && (new ReflectionMethod(TopicAssignmentController::class, $m))->isPublic());
    }
    $indexSrc = (string)file_get_contents($root . '/public/index.php');
    foreach ([
        ['get', '/assignments/manual', 'manual'], ['post', '/assignments/manual', 'assignManual'],
        ['post', '/assignments/cancel', 'cancel'], ['get', '/assignments/check', 'check'],
        ['post', '/assignments/publish', 'publish'],
    ] as [$verb, $uri, $action]) {
        t("Đã đăng ký route {$verb} {$uri}",
            (bool)preg_match('/\$router->' . $verb . '\(\'' . preg_quote($uri, '/') . '\',\s*\[TopicAssignmentController::class,\s*\'' . $action . '\'\]\)/', $indexSrc));
    }
    $routeCounts = [];
    preg_match_all('/\$router->(get|post)\(\'([^\']+)\'/', $indexSrc, $mm, PREG_SET_ORDER);
    foreach ($mm as $row) {
        $key = $row[1] . ' ' . $row[2];
        $routeCounts[$key] = ($routeCounts[$key] ?? 0) + 1;
    }
    t('Không có route bị khai báo trùng', max($routeCounts) === 1);

    // --- Phân quyền ---
    foreach (['admin', 'training_dept', 'department_head'] as $r) {
        t("Vai trò {$r} được phân công, xác nhận, công bố", TopicAssignmentController::canManage([$r]) === true);
    }
    foreach (['student', 'lecturer', 'enterprise'] as $r) {
        t("Vai trò {$r} KHÔNG được phân công, xác nhận, công bố", TopicAssignmentController::canManage([$r]) === false);
    }
    t('Không có vai trò thì KHÔNG được quản lý phân công', TopicAssignmentController::canManage([]) === false);

    // --- CSRF ---
    $_SESSION = [];
    $token = AuthController::csrfToken();
    $_POST = ['_csrf' => 'sai'];
    t('CSRF token sai bị từ chối', AuthController::verifyCsrf('sai') === false);
    $_POST = ['_csrf' => $token];
    t('CSRF token đúng được chấp nhận', AuthController::verifyCsrf($token) === true);

    // --- Kiểm tra dữ liệu phân công thủ công ---
    t('Dữ liệu hợp lệ: không có lỗi', TopicAssignment::validateManual($pA, $sv[1], $tBig, $gv1) === []);
    t('Đợt không tồn tại bị từ chối', TopicAssignment::validateManual(999999999, $sv[1], $tBig, $gv1) !== []);
    t('Đợt đã kết thúc bị từ chối', TopicAssignment::validateManual($pC, $sv[1], $tClosed, $gv1) !== []);
    t('Đề tài chưa được duyệt bị từ chối', TopicAssignment::validateManual($pA, $sv[1], $tDraft, $gv1) !== []);
    t('Đề tài thuộc đợt khác bị từ chối', TopicAssignment::validateManual($pA, $sv[1], $tOther, $gv1) !== []);
    t('Đề tài không tồn tại bị từ chối', TopicAssignment::validateManual($pA, $sv[1], 999999999, $gv1) !== []);
    t('Người không phải sinh viên bị từ chối', TopicAssignment::validateManual($pA, $gv2, $tBig, $gv1) !== []);
    t('Sinh viên bị khóa bị từ chối', TopicAssignment::validateManual($pA, $svLocked, $tBig, $gv1) !== []);
    t('Sinh viên không tồn tại bị từ chối', TopicAssignment::validateManual($pA, 999999999, $tBig, $gv1) !== []);
    t('Người không phải giảng viên bị từ chối', TopicAssignment::validateManual($pA, $sv[1], $tBig, $company) !== []);
    t('Sinh viên làm giảng viên bị từ chối', TopicAssignment::validateManual($pA, $sv[1], $tBig, $sv[2]) !== []);

    // --- Phân công hợp lệ ---
    $id1 = TopicAssignment::assignManual($pA, $sv[1], $tBig, $gv1, $admin, 'Ghi chú thử');
    $row1 = TopicAssignment::getByStudent($sv[1], $pA);
    t('Phân công hợp lệ: có bản ghi', $row1 !== null);
    t('Phân công thủ công lưu ở trạng thái Dự thảo', $row1['status'] === 'draft');
    t('Phân công thủ công ghi đúng phương thức manual', $row1['assignment_method'] === 'manual');
    t('Phân công thủ công không gắn nguyện vọng', $row1['matched_wish_priority'] === null);
    t('Ghi nhận đúng người phân công và đề tài', (int)$row1['assigned_by'] === $admin && (int)$row1['topic_id'] === $tBig && (int)$row1['lecturer_id'] === $gv1);
    t('Lưu ghi chú phân công', $row1['notes'] === 'Ghi chú thử');

    // --- Trùng sinh viên ---
    $dup = TopicAssignment::validateManual($pA, $sv[1], $tBig, $gv1);
    t('Phân công trùng sinh viên trong đợt bị từ chối', $dup !== [] && str_contains(implode(' ', $dup), 'đã được phân công'));
    t('Sinh viên đã phân công không còn trong danh sách có thể phân công',
        !in_array($sv[1], array_column(TopicAssignment::getAssignableStudents($pA), 'student_id'), true));
    t('Sinh viên bị khóa không nằm trong danh sách có thể phân công',
        !in_array($svLocked, array_column(TopicAssignment::getAssignableStudents($pA), 'student_id'), true));
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM topic_assignments WHERE student_id = {$sv[1]} AND period_id = {$pA}")->fetchColumn();
    t('Chỉ có 1 bản ghi cho mỗi sinh viên trong đợt', $cnt === 1);

    // --- Hết chỉ tiêu đề tài ---
    TopicAssignment::assignManual($pA, $sv[2], $tOne, $gv1, $admin);
    t('Phân công vào đề tài còn chỗ (1/1) thành công', $statusOf($sv[2], $pA) === 'draft');
    t('Đề tài đã đủ chỉ tiêu: ném RuntimeException',
        thrown(fn() => TopicAssignment::assignManual($pA, $sv[3], $tOne, $gv1, $admin)) === RuntimeException::class);
    t('Đề tài hết chỉ tiêu: sinh viên không bị phân công', $statusOf($sv[3], $pA) === null);

    // --- Hết chỉ tiêu giảng viên ---
    TopicAssignment::assignManual($pA, $sv[3], $tBig, $gv2, $admin, null, 1);
    t('Giảng viên nhận sinh viên đầu tiên (1/1) thành công', $statusOf($sv[3], $pA) === 'draft');
    t('Giảng viên đã đủ chỉ tiêu: ném RuntimeException',
        thrown(fn() => TopicAssignment::assignManual($pA, $sv[4], $tBig, $gv2, $admin, null, 1)) === RuntimeException::class);
    t('Giảng viên hết chỉ tiêu: sinh viên không bị phân công', $statusOf($sv[4], $pA) === null);
    $loads = array_column(TopicAssignment::getLecturersWithLoad($pA), 'assigned_count', 'id');
    t('Danh sách giảng viên có số sinh viên đang hướng dẫn', (int)($loads[$gv2] ?? -1) === 1 && (int)($loads[$gv1] ?? -1) === 2);
    $caps = array_column(TopicAssignment::getApprovedTopicsWithCapacity($pA), 'assigned_count', 'id');
    t('Danh sách đề tài đã duyệt có số sinh viên đã gán', (int)($caps[$tOne] ?? -1) === 1 && (int)($caps[$tBig] ?? -1) === 2);
    t('Danh sách đề tài không có đề tài chưa duyệt', !isset($caps[$tDraft]) && !isset($caps[$tOther]));

    // --- Hủy bản dự thảo và phân công lại ---
    $a2 = TopicAssignment::getByStudent($sv[2], $pA);
    TopicAssignment::cancelDraft((int)$a2['id'], 'Đổi đề tài');
    t('Hủy bản dự thảo: trạng thái cancelled', $rawStatus((int)$a2['id']) === 'cancelled');
    t('Hủy bản dự thảo: ghi lý do vào ghi chú', str_contains((string)TopicAssignment::findById((int)$a2['id'])['notes'], 'Đổi đề tài'));
    t('Hủy lần hai bị từ chối (đã hủy)', thrown(fn() => TopicAssignment::cancelDraft((int)$a2['id'])) === InvalidArgumentException::class);
    t('Hủy phân công không tồn tại bị từ chối', thrown(fn() => TopicAssignment::cancelDraft(999999999)) === InvalidArgumentException::class);
    t('Sau khi hủy, sinh viên được phân công lại', TopicAssignment::validateManual($pA, $sv[2], $tBig, $gv1) === []);
    TopicAssignment::assignManual($pA, $sv[2], $tBig, $gv1, $admin);
    t('Phân công lại sau khi hủy thành công (vẫn 1 bản ghi)',
        $statusOf($sv[2], $pA) === 'draft'
        && (int)$pdo->query("SELECT COUNT(*) FROM topic_assignments WHERE student_id = {$sv[2]} AND period_id = {$pA}")->fetchColumn() === 1);

    // --- Nguyện vọng Sprint 3 không bị xóa ---
    $wishRows = 0;
    $wishInserted = false;
    try {
        $wishStmt = $pdo->prepare('INSERT INTO topic_wishes (period_id, student_id, topic_id, priority) VALUES (:p, :s, :t, 1)');
        $wishInserted = $wishStmt->execute(['p' => $pA, 's' => $sv[5], 't' => $tBig]);
    } catch (Throwable $e) {
        $wishInserted = false;
    }
    if ($wishInserted) {
        $countWishes = fn() => (int)$pdo->query("SELECT COUNT(*) FROM topic_wishes WHERE period_id = {$pA}")->fetchColumn();
        $wishesBefore = $countWishes();
    }

    // --- Ẩn bản dự thảo với người không có quyền ---
    $all = TopicAssignment::getByPeriod($pA);
    $uid = fn(int $id) => $id;
    t('Đợt có bản ghi dự thảo trước khi công bố', count($all) >= 3 && count(array_filter($all, fn($a) => $a['status'] === 'draft')) === count($all));
    t('Sinh viên KHÔNG thấy bản dự thảo của chính mình',
        TopicAssignmentController::visibleAssignments($all, ['student'], $sv[1]) === []);
    t('Sinh viên không thấy bản dự thảo của người khác',
        TopicAssignmentController::visibleAssignments($all, ['student'], $sv[9999] ?? 999999999) === []);
    t('Giảng viên KHÔNG thấy bản dự thảo', TopicAssignmentController::visibleAssignments($all, ['lecturer'], $gv1) === []);
    t('Doanh nghiệp KHÔNG thấy bản dự thảo', TopicAssignmentController::visibleAssignments($all, ['enterprise'], $company) === []);
    t('Người quản lý thấy cả bản dự thảo', count(TopicAssignmentController::visibleAssignments($all, ['admin'], $admin)) === count($all));
    t('Truy vấn theo trạng thái published không có bản dự thảo', TopicAssignment::getByPeriod($pA, ['status' => 'published']) === []);

    // --- Kiểm tra trước khi công bố ---
    $before = $pdo->query("SELECT id, status FROM topic_assignments WHERE period_id = {$pA} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    t('Đợt hợp lệ: không có xung đột khi kiểm tra trước công bố', TopicAssignment::findPublishConflicts($pA, 5) === []);
    $afterCheck = $pdo->query("SELECT id, status FROM topic_assignments WHERE period_id = {$pA} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    t('Kiểm tra trước công bố không làm đổi dữ liệu', $before === $afterCheck);
    t('Chỉ tiêu giảng viên được kiểm tra lại: gv1 có 2, giới hạn 1 thì báo xung đột', TopicAssignment::findPublishConflicts($pA, 1) !== []);

    // --- Công bố bị chặn khi còn xung đột (tại thời điểm xác nhận) ---
    $svX = $mkUser('svx', 'student');
    TopicAssignment::assignManual($pD, $svX, $tOther, $gv2, $admin);
    $pdo->exec("UPDATE topics SET status = 'rejected' WHERE id = {$tOther}");
    t('Đề tài bị đổi trạng thái sau khi phân công: phát hiện xung đột', TopicAssignment::findPublishConflicts($pD, 5) !== []);
    t('Công bố bị chặn khi đề tài không còn được duyệt: ném InvalidArgumentException',
        thrown(fn() => TopicAssignment::publishChecked($pD, $admin, 5)) === InvalidArgumentException::class);
    t('Công bố bị chặn: bản dự thảo giữ nguyên', $statusOf($svX, $pD) === 'draft');
    $pdo->exec("UPDATE topics SET status = 'approved' WHERE id = {$tOther}");

    $pdo->exec("UPDATE users SET status = 0 WHERE id = {$svX}");
    t('Sinh viên bị khóa sau khi phân công: phát hiện xung đột', TopicAssignment::findPublishConflicts($pD, 5) !== []);
    $pdo->exec("UPDATE users SET status = 1 WHERE id = {$svX}");

    // Vượt chỉ tiêu do dữ liệu bị chèn trực tiếp (bỏ qua kiểm tra của Model)
    $pdo->prepare(
        "INSERT INTO topic_assignments (period_id, student_id, topic_id, lecturer_id, assignment_method, status)
         VALUES (:p, :s, :t, :l, 'manual', 'draft')"
    )->execute(['p' => $pD, 's' => $sv[6], 't' => $tOther, 'l' => $gv2]);
    $svY = $mkUser('svy', 'student');
    $pdo->prepare(
        "INSERT INTO topic_assignments (period_id, student_id, topic_id, lecturer_id, assignment_method, status)
         VALUES (:p, :s, :t, :l, 'manual', 'draft')"
    )->execute(['p' => $pD, 's' => $svY, 't' => $tOther, 'l' => $gv2]);
    $over = TopicAssignment::findPublishConflicts($pD, 10);
    t('Đề tài vượt chỉ tiêu (3/2): phát hiện xung đột', $over !== [] && str_contains(implode(' ', $over), 'vượt chỉ tiêu'));
    t('Công bố bị chặn khi vượt chỉ tiêu', thrown(fn() => TopicAssignment::publishChecked($pD, $admin, 10)) === InvalidArgumentException::class);

    // --- Đợt đã kết thúc ---
    t('Đợt đã kết thúc: công bố bị từ chối (không có bản dự thảo)', thrown(fn() => TopicAssignment::publishChecked($pC, $admin, 5)) === RuntimeException::class);

    // --- Xác nhận và công bố ---
    $statsBefore = TopicAssignment::getStatistics($pA);
    $published = TopicAssignment::publishChecked($pA, $admin, 5);
    t('Công bố trả về số bản ghi đã công bố', $published === 3, "nhận {$published}");
    $afterPub = $pdo->query("SELECT status, assigned_by FROM topic_assignments WHERE period_id = {$pA} AND status != 'cancelled'")->fetchAll(PDO::FETCH_ASSOC);
    t('Sau công bố: tất cả bản ghi chuyển sang published', $afterPub !== [] && count(array_filter($afterPub, fn($r) => $r['status'] === 'published')) === count($afterPub));
    t('Sau công bố: ghi nhận người xác nhận', count(array_filter($afterPub, fn($r) => (int)$r['assigned_by'] === $admin)) === count($afterPub));
    $statsAfter = TopicAssignment::getStatistics($pA);
    t('Thống kê: dự thảo giảm về 0 và published tăng', $statsBefore['draft_count'] === 3 && $statsAfter['draft_count'] === 0 && $statsAfter['published_count'] === 3);

    // --- Xác nhận lặp ---
    t('Xác nhận / công bố lặp bị từ chối', thrown(fn() => TopicAssignment::publishChecked($pA, $admin, 5)) === RuntimeException::class);
    $afterRepeat = $pdo->query("SELECT status FROM topic_assignments WHERE period_id = {$pA} AND status != 'cancelled'")->fetchAll(PDO::FETCH_COLUMN);
    t('Công bố lặp không làm đổi trạng thái', count(array_unique($afterRepeat)) === 1 && $afterRepeat[0] === 'published');

    // --- Bản đã công bố ---
    t('Phân công đã công bố không hủy được bằng cancelDraft', thrown(fn() => TopicAssignment::cancelDraft((int)TopicAssignment::getByStudent($sv[1], $pA)['id'])) === InvalidArgumentException::class);
    t('Sinh viên đã có kết quả công bố không phân công lại được', TopicAssignment::validateManual($pA, $sv[1], $tBig, $gv1) !== []);
    $pubRows = TopicAssignment::getByPeriod($pA);
    $mine = TopicAssignmentController::visibleAssignments($pubRows, ['student'], $sv[1]);
    t('Sau công bố: sinh viên thấy kết quả của chính mình', count($mine) === 1 && (int)$mine[0]['student_id'] === $sv[1]);
    t('Sau công bố: sinh viên không thấy kết quả của người khác', count(array_filter($mine, fn($a) => (int)$a['student_id'] !== $sv[1])) === 0);
    $lecRows = TopicAssignmentController::visibleAssignments($pubRows, ['lecturer'], $gv2);
    t('Sau công bố: giảng viên chỉ thấy sinh viên mình hướng dẫn', count($lecRows) === 1 && (int)$lecRows[0]['lecturer_id'] === $gv2);

    if ($wishInserted) {
        t('Công bố không xóa nguyện vọng của sinh viên (Sprint 3)', $countWishes() === $wishesBefore && $wishesBefore >= 1);
    } else {
        skip('Công bố không xóa nguyện vọng của sinh viên', 'không chèn được dữ liệu mẫu vào topic_wishes, kiểm tra lại cấu trúc bảng');
    }

    // --- Không ảnh hưởng các phân hệ khác ---
    t('TopicAssignment::getStatistics() vẫn hoạt động', is_array(TopicAssignment::getStatistics($pA)));
    t('TopicAssignment::getUnassignedStudents() vẫn hoạt động', is_array(TopicAssignment::getUnassignedStudents($pA)));
    t('User::all() vẫn hoạt động', is_array(User::all()));
} catch (Throwable $e) {
    echo "\n[ LỖI ] " . get_class($e) . ': ' . $e->getMessage() . ' (dòng ' . $e->getLine() . ")\n";
    $fail++;
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "\n----------------------------------------------\n";
echo "KẾT QUẢ: {$pass} PASS, {$fail} FAIL, {$skip} SKIP.\n";
echo "(Dữ liệu test đã được rollback, CSDL không bị thay đổi)\n\n";
exit($fail > 0 ? 1 : 0);
