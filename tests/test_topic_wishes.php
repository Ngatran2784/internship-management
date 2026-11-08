<?php
/**
 * KIỂM THỬ ĐĂNG KÝ VÀ SẮP XẾP NGUYỆN VỌNG ĐỀ TÀI (SPRINT 3: S3-04, S3-06, S3-07)
 * Chạy: php tests/test_topic_wishes.php
 *
 * Yêu cầu: MySQL đang chạy, đã có các bảng internship_periods, topics, topic_wishes.
 * Toàn bộ dữ liệu kiểm thử được bọc trong Transaction và tự động ROLLBACK khi kết thúc.
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
use App\Controllers\TopicWishController;
use App\Core\Database;
use App\Models\InternshipPeriod;
use App\Models\Role;
use App\Models\Topic;
use App\Models\TopicWish;
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
    } catch (\Throwable $e) {
        return false;
    }
    return false;
}

echo "\n======================================================\n";
echo "   KIỂM THỬ ĐĂNG KÝ NGUYỆN VỌNG ĐỀ TÀI (TOPIC WISHES)\n";
echo "======================================================\n\n";

try {
    $pdo = Database::getConnection();
} catch (Throwable $e) {
    echo "Không thể kết nối CSDL: " . $e->getMessage() . "\n";
    exit(1);
}

// ── 1. Kiểm tra cấu trúc lớp và phương thức ───────────────────────────────────
t("Có lớp TopicWish", class_exists(TopicWish::class));
t("Có lớp TopicWishController", class_exists(TopicWishController::class));

$refClass = new ReflectionClass(TopicWishController::class);
t("TopicWishController::index là hàm public", $refClass->hasMethod('index') && $refClass->getMethod('index')->isPublic());
t("TopicWishController::create là hàm public", $refClass->hasMethod('create') && $refClass->getMethod('create')->isPublic());
t("TopicWishController::save là hàm public", $refClass->hasMethod('save') && $refClass->getMethod('save')->isPublic());
t("TopicWishController::delete là hàm public", $refClass->hasMethod('delete') && $refClass->getMethod('delete')->isPublic());

// ── 2. Kiểm tra khai báo Route trong public/index.php ─────────────────────────
$indexSrc = file_get_contents($root . '/public/index.php');
t("Router đăng ký GET /wishes", str_contains($indexSrc, "'/wishes'"));
t("Router đăng ký GET /wishes/create", str_contains($indexSrc, "'/wishes/create'"));
t("Router đăng ký POST /wishes/save", str_contains($indexSrc, "'/wishes/save'"));
t("Router đăng ký POST /wishes/delete", str_contains($indexSrc, "'/wishes/delete'"));

// ── 3. Kiểm tra phân quyền đăng ký nguyện vọng (RBAC) ─────────────────────────
t("Sinh viên được phép đăng ký nguyện vọng", TopicWish::canRegister(['student']));
t("Admin KHÔNG được phép đăng ký nguyện vọng thay sinh viên", !TopicWish::canRegister(['admin']));
t("Giảng viên KHÔNG được phép đăng ký nguyện vọng", !TopicWish::canRegister(['lecturer']));
t("Doanh nghiệp KHÔNG được phép đăng ký nguyện vọng", !TopicWish::canRegister(['company']));
t("Phòng đào tạo KHÔNG được phép đăng ký nguyện vọng", !TopicWish::canRegister(['training_dept']));
t("Người dùng không có vai trò KHÔNG được phép đăng ký", !TopicWish::canRegister([]));

// ── 4. Kiểm tra cấu trúc bảng `topic_wishes` trong CSDL ─────────────────────
$tableCols = $pdo->query("DESCRIBE `topic_wishes`")->fetchAll(PDO::FETCH_COLUMN);
t("Bảng topic_wishes có cột id", in_array('id', $tableCols, true));
t("Bảng topic_wishes có cột student_id", in_array('student_id', $tableCols, true));
t("Bảng topic_wishes có cột topic_id", in_array('topic_id', $tableCols, true));
t("Bảng topic_wishes có cột period_id", in_array('period_id', $tableCols, true));
t("Bảng topic_wishes có cột priority", in_array('priority', $tableCols, true));
t("Bảng topic_wishes có cột status", in_array('status', $tableCols, true));
t("Bảng topic_wishes có cột note", in_array('note', $tableCols, true));

// Bắt đầu giao dịch kiểm thử nghiệp vụ (toàn bộ sẽ được ROLLBACK)
$pdo->beginTransaction();

try {
    // Chuẩn bị dữ liệu kiểm thử
    // 1. Tạo 1 đợt active hợp lệ
    $stmtP = $pdo->prepare("
        INSERT INTO `internship_periods` (`code`, `name`, `type`, `start_date`, `end_date`, `status`)
        VALUES ('TEST-P-OPEN', 'Đợt Test Mở', 'internship', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active')
    ");
    $stmtP->execute();
    $activePeriodId = (int)$pdo->lastInsertId();

    // 2. Tạo 1 đợt closed (đã đóng)
    $stmtPClosed = $pdo->prepare("
        INSERT INTO `internship_periods` (`code`, `name`, `type`, `start_date`, `end_date`, `status`)
        VALUES ('TEST-P-CLOSED', 'Đợt Test Đã Đóng', 'internship', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'closed')
    ");
    $stmtPClosed->execute();
    $closedPeriodId = (int)$pdo->lastInsertId();

    // 3. Tạo sinh viên test
    $stmtU = $pdo->prepare("
        INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `status`)
        VALUES ('test_sv_999', 'test_sv_999@vhu.edu.vn', 'hash', 'Sinh Viên Kiểm Thử', 1)
    ");
    $stmtU->execute();
    $testStudentId = (int)$pdo->lastInsertId();

    // 4. Tạo giảng viên test
    $stmtGv = $pdo->prepare("
        INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `status`)
        VALUES ('test_gv_999', 'test_gv_999@vhu.edu.vn', 'hash', 'Giảng Viên Test', 1)
    ");
    $stmtGv->execute();
    $testGvId = (int)$pdo->lastInsertId();

    // 5. Tạo các đề tài test
    // T1: Approved trong đợt active
    $stmtT1 = $pdo->prepare("
        INSERT INTO `topics` (`code`, `name`, `type`, `period_id`, `proposer_id`, `status`)
        VALUES ('TEST-TOPIC-01', 'Đề tài 01 Đã Duyệt', 'internship', :pid, :uid, 'approved')
    ");
    $stmtT1->execute([':pid' => $activePeriodId, ':uid' => $testGvId]);
    $topicApproved1 = (int)$pdo->lastInsertId();

    // T2: Approved trong đợt active
    $stmtT2 = $pdo->prepare("
        INSERT INTO `topics` (`code`, `name`, `type`, `period_id`, `proposer_id`, `status`)
        VALUES ('TEST-TOPIC-02', 'Đề tài 02 Đã Duyệt', 'internship', :pid, :uid, 'approved')
    ");
    $stmtT2->execute([':pid' => $activePeriodId, ':uid' => $testGvId]);
    $topicApproved2 = (int)$pdo->lastInsertId();

    // T3: Approved trong đợt active
    $stmtT3 = $pdo->prepare("
        INSERT INTO `topics` (`code`, `name`, `type`, `period_id`, `proposer_id`, `status`)
        VALUES ('TEST-TOPIC-03', 'Đề tài 03 Đã Duyệt', 'internship', :pid, :uid, 'approved')
    ");
    $stmtT3->execute([':pid' => $activePeriodId, ':uid' => $testGvId]);
    $topicApproved3 = (int)$pdo->lastInsertId();

    // T4: Approved nhưng thuộc đợt khác (closedPeriod)
    $stmtT4 = $pdo->prepare("
        INSERT INTO `topics` (`code`, `name`, `type`, `period_id`, `proposer_id`, `status`)
        VALUES ('TEST-TOPIC-04', 'Đề tài Đợt Đóng', 'internship', :pid, :uid, 'approved')
    ");
    $stmtT4->execute([':pid' => $closedPeriodId, ':uid' => $testGvId]);
    $topicOtherPeriod = (int)$pdo->lastInsertId();

    // T5: Chưa duyệt (pending) trong đợt active
    $stmtT5 = $pdo->prepare("
        INSERT INTO `topics` (`code`, `name`, `type`, `period_id`, `proposer_id`, `status`)
        VALUES ('TEST-TOPIC-05', 'Đề tài Chờ Duyệt', 'internship', :pid, :uid, 'pending')
    ");
    $stmtT5->execute([':pid' => $activePeriodId, ':uid' => $testGvId]);
    $topicPending = (int)$pdo->lastInsertId();

    // ── 5. Kiểm tra logic thời hạn đợt ─────────────────────────────────────────
    t("Đợt active và trong hạn trả về isPeriodOpenForRegistration = true", 
        TopicWish::isPeriodOpenForRegistration($activePeriodId) === true
    );
    t("Đợt closed trả về isPeriodOpenForRegistration = false", 
        TopicWish::isPeriodOpenForRegistration($closedPeriodId) === false
    );
    t("Đợt không tồn tại trả về isPeriodOpenForRegistration = false", 
        TopicWish::isPeriodOpenForRegistration(999999) === false
    );

    // ── 6. Kiểm tra danh sách đề tài mở cho sinh viên đăng ký ─────────────────
    $approvedList = TopicWish::getApprovedTopicsForPeriod($activePeriodId);
    t("Chỉ lấy đề tài status=approved cho đợt", count($approvedList) === 3);
    $approvedCodes = array_column($approvedList, 'code');
    t("Đề tài pending không xuất hiện trong danh mục chọn", !in_array('TEST-TOPIC-05', $approvedCodes, true));

    // ── 7. Kiểm tra các trường hợp vi phạm nghiệp vụ khi đăng ký ───────────────
    // A. Danh sách rỗng
    t("Từ chối khi danh sách nguyện vọng rỗng", throwsInvalid(function () use ($testStudentId, $activePeriodId) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, []);
    }));

    // B. Vượt quá 3 nguyện vọng
    t("Từ chối khi đăng ký quá 3 nguyện vọng", throwsInvalid(function () use ($testStudentId, $activePeriodId, $topicApproved1, $topicApproved2, $topicApproved3) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicApproved1, 'priority' => 1],
            ['topic_id' => $topicApproved2, 'priority' => 2],
            ['topic_id' => $topicApproved3, 'priority' => 3],
            ['topic_id' => $topicApproved1, 'priority' => 4],
        ]);
    }));

    // C. Trùng thứ tự ưu tiên
    t("Từ chối khi trùng thứ tự ưu tiên (cùng NV1)", throwsInvalid(function () use ($testStudentId, $activePeriodId, $topicApproved1, $topicApproved2) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicApproved1, 'priority' => 1],
            ['topic_id' => $topicApproved2, 'priority' => 1],
        ]);
    }));

    // D. Trùng đề tài
    t("Từ chối khi trùng đề tài ở 2 nguyện vọng", throwsInvalid(function () use ($testStudentId, $activePeriodId, $topicApproved1) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicApproved1, 'priority' => 1],
            ['topic_id' => $topicApproved1, 'priority' => 2],
        ]);
    }));

    // E. Đề tài chưa được duyệt
    t("Từ chối đăng ký đề tài đang pending", throwsInvalid(function () use ($testStudentId, $activePeriodId, $topicPending) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicPending, 'priority' => 1],
        ]);
    }));

    // F. Đề tài thuộc đợt khác
    t("Từ chối đăng ký đề tài không thuộc đợt", throwsInvalid(function () use ($testStudentId, $activePeriodId, $topicOtherPeriod) {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicOtherPeriod, 'priority' => 1],
        ]);
    }));

    // G. Đăng ký vào đợt đã đóng
    t("Từ chối đăng ký vào đợt đã đóng (closed)", throwsInvalid(function () use ($testStudentId, $closedPeriodId, $topicOtherPeriod) {
        TopicWish::saveWishes($testStudentId, $closedPeriodId, [
            ['topic_id' => $topicOtherPeriod, 'priority' => 1],
        ]);
    }));

    // ── 8. Đăng ký thành công và cập nhật nguyện vọng ─────────────────────────
    // Trường hợp 1: Sinh viên đăng ký đúng 1 nguyện vọng (NV1 duy nhất)
    $singleWish = TopicWish::saveWishes($testStudentId, $activePeriodId, [
        ['topic_id' => $topicApproved1, 'priority' => 1, 'note' => 'Chỉ đăng ký NV1'],
    ]);
    t("Sinh viên đăng ký đúng 1 nguyện vọng thành công", count($singleWish) === 1);
    t("NV1 duy nhất có trạng thái mặc định pending", ($singleWish[0]['status'] ?? '') === 'pending');

    // Trường hợp 2: Sinh viên đăng ký đủ 3 nguyện vọng (NV1, NV2, NV3)
    $threeWishes = TopicWish::saveWishes($testStudentId, $activePeriodId, [
        ['topic_id' => $topicApproved1, 'priority' => 1, 'note' => 'Ghi chú NV1'],
        ['topic_id' => $topicApproved2, 'priority' => 2, 'note' => 'Ghi chú NV2'],
        ['topic_id' => $topicApproved3, 'priority' => 3, 'note' => 'Ghi chú NV3'],
    ]);
    t("Sinh viên đăng ký đủ 3 nguyện vọng thành công", count($threeWishes) === 3);
    t("Thứ tự ưu tiên 3 nguyện vọng đúng 1, 2, 3", (int)$threeWishes[0]['priority'] === 1 && (int)$threeWishes[1]['priority'] === 2 && (int)$threeWishes[2]['priority'] === 3);

    // Lấy danh sách qua getByStudentAndPeriod
    $fetched = TopicWish::getByStudentAndPeriod($testStudentId, $activePeriodId);
    t("getByStudentAndPeriod trả về danh sách đã lưu", count($fetched) === 3);
    t("Danh sách được sắp xếp theo priority tăng dần (1 -> 2 -> 3)", (int)$fetched[0]['priority'] === 1 && (int)$fetched[1]['priority'] === 2 && (int)$fetched[2]['priority'] === 3);

    // Kiểm tra tính phân lập dữ liệu người dùng (Student B không thấy nguyện vọng của Student A)
    $otherStudentWishes = TopicWish::getByStudentAndPeriod(999998, $activePeriodId);
    t("Sinh viên khác không có nguyện vọng trong đợt này", count($otherStudentWishes) === 0);

    // Cập nhật lại: Đổi thứ tự ưu tiên (Chỉnh sửa trong hạn)
    $updated = TopicWish::saveWishes($testStudentId, $activePeriodId, [
        ['topic_id' => $topicApproved2, 'priority' => 1, 'note' => 'Đổi NV2 lên NV1'],
        ['topic_id' => $topicApproved3, 'priority' => 2, 'note' => 'Thêm NV3 vào thứ tự 2'],
        ['topic_id' => $topicApproved1, 'priority' => 3, 'note' => 'Chuyển NV1 cũ xuống NV3'],
    ]);

    t("Chỉnh sửa trong hạn: Cập nhật nguyện vọng thành công với 3 mục", count($updated) === 3);
    t("Ưu tiên mới NV1 là TEST-TOPIC-02", ($updated[0]['topic_code'] ?? '') === 'TEST-TOPIC-02');
    t("Ưu tiên mới NV2 là TEST-TOPIC-03", ($updated[1]['topic_code'] ?? '') === 'TEST-TOPIC-03');
    t("Ưu tiên mới NV3 là TEST-TOPIC-01", ($updated[2]['topic_code'] ?? '') === 'TEST-TOPIC-01');

    // Chỉnh sửa quá hạn: Thao tác vào đợt closed bị từ chối
    t("Chỉnh sửa quá hạn: Đợt đã kết thúc bị từ chối thay đổi", throwsInvalid(function () use ($testStudentId, $closedPeriodId, $topicOtherPeriod) {
        TopicWish::saveWishes($testStudentId, $closedPeriodId, [
            ['topic_id' => $topicOtherPeriod, 'priority' => 1],
        ]);
    }));

    // Kiểm tra tính nguyên tử (Atomicity & Rollback) trong Transaction:
    // Nếu trong mảng 3 nguyện vọng có 1 đề tài sai (ví dụ ID không tồn tại), toàn bộ batch bị hủy
    $beforeFailedBatch = TopicWish::getByStudentAndPeriod($testStudentId, $activePeriodId);
    $batchFailed = false;
    try {
        TopicWish::saveWishes($testStudentId, $activePeriodId, [
            ['topic_id' => $topicApproved1, 'priority' => 1],
            ['topic_id' => 999999, 'priority' => 2], // ID đề tài không tồn tại
        ]);
    } catch (\Throwable $e) {
        $batchFailed = true;
    }
    t("Transaction Rollback: Lỗi 1 đề tài làm từ chối toàn bộ batch", $batchFailed);
    $afterFailedBatch = TopicWish::getByStudentAndPeriod($testStudentId, $activePeriodId);
    t("Transaction Rollback: Dữ liệu cũ vẫn nguyên vẹn sau khi batch lỗi", count($beforeFailedBatch) === count($afterFailedBatch));

    // Lấy qua getByStudent
    $allWishes = TopicWish::getByStudent($testStudentId);
    t("getByStudent lấy được danh sách nguyện vọng của sinh viên", count($allWishes) === 3);

    // Hủy nguyện vọng trong hạn
    $delResult = TopicWish::deleteByStudentAndPeriod($testStudentId, $activePeriodId);
    t("deleteByStudentAndPeriod thực thi thành công", $delResult === true);
    $afterDel = TopicWish::getByStudentAndPeriod($testStudentId, $activePeriodId);
    t("Sau khi xóa danh sách nguyện vọng rỗng", count($afterDel) === 0);

    // Hủy nguyện vọng ngoài hạn (đợt closed) bị từ chối
    t("Hủy nguyện vọng ngoài hạn bị từ chối", throwsInvalid(function () use ($testStudentId, $closedPeriodId) {
        TopicWish::deleteByStudentAndPeriod($testStudentId, $closedPeriodId);
    }));

    // Kiểm tra token CSRF
    AuthController::startSession();
    $validToken = AuthController::csrfToken();
    $_POST['_csrf'] = 'invalid_token_xyz';
    t("CSRF Token sai bị từ chối", AuthController::verifyCsrf() === false);
    $_POST['_csrf'] = $validToken;
    t("CSRF Token đúng được chấp nhận", AuthController::verifyCsrf() === true);

} finally {
    // Luôn luôn ROLLBACK để CSDL sạch sẽ và an toàn
    $pdo->rollBack();
}

echo "\n----------------------------------------------\n";
echo "KẾT QUẢ KIỂM THỬ NGUYỆN VỌNG: {$pass} PASS, {$fail} FAIL.\n";
echo "(Dữ liệu kiểm thử đã được ROLLBACK an toàn)\n";
echo "==============================================\n";

if ($fail > 0) {
    exit(1);
}
exit(0);
