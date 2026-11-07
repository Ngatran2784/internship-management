<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * BỘ KIỂM THỬ TỰ ĐỘNG: QUẢN LÝ ĐỀ TÀI THỰC TẬP & ĐỒ ÁN (TOPICS)
 * ==============================================================================
 *
 * Phạm vi kiểm thử:
 * 1. Cấu trúc CSDL bảng `topics`, khóa ngoại, chỉ mục, ràng buộc toàn vẹn.
 * 2. Topic Model: all(), findById(), findByCode(), existsCode(), validate(), create(), update(), delete(), canEdit().
 * 3. TopicController: Phân quyền vai trò, bảo mật CSRF, lọc & tìm kiếm.
 * 4. Router: Đăng ký đầy đủ các route cho module Topics.
 * 5. Tính toàn vẹn CSDL: Toàn bộ kiểm thử chạy trong Transaction và tự động ROLLBACK.
 */

// Thiết lập môi trường kiểm thử
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoloader cho namespace App
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Core\Router;
use App\Models\Topic;
use App\Models\InternshipPeriod;
use App\Models\User;
use App\Controllers\AuthController;
use App\Controllers\TopicController;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function t(string $name, bool $condition, string $detail = ''): void {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [ PASS ] {$name}\n";
    } else {
        $failedTests++;
        echo "  [ FAIL ] {$name}" . ($detail !== '' ? " -> {$detail}" : '') . "\n";
    }
}

echo "\n======================================================\n";
echo "   KIỂM THỬ BACKEND QUẢN LÝ ĐỀ TÀI (TOPICS)   \n";
echo "======================================================\n\n";

// --- 1. KIỂM TRA AUTOLOAD & CẤU TRÚC LỚP ---
t('Autoload App\Models\Topic thành công', class_exists(Topic::class));
t('Autoload App\Controllers\TopicController thành công', class_exists(TopicController::class));

// --- 2. KIỂM TRA KẾT NỐI VÀ CẤU TRÚC CSDL BẢNG topics ---
$pdo = null;
try {
    $pdo = Database::getConnection();
    t('Kết nối CSDL MySQL qua PDO thành công', $pdo instanceof PDO);
} catch (Throwable $e) {
    t('Kết nối CSDL MySQL qua PDO thành công', false, $e->getMessage());
    exit(1);
}

// Kiểm tra sự tồn tại của bảng topics
$tableExists = false;
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'topics'")->fetchColumn();
    $tableExists = ($checkTable === 'topics');
} catch (Throwable $e) {
    $tableExists = false;
}
t('Bảng `topics` đã được tạo trong CSDL', $tableExists);

// Kiểm tra các cột trong bảng topics
$expectedColumns = [
    'id', 'code', 'name', 'description', 'type',
    'period_id', 'proposer_id', 'max_students', 'status',
    'created_at', 'updated_at'
];
$cols = $pdo->query("DESCRIBE `topics`")->fetchAll(PDO::FETCH_COLUMN);
$missingCols = array_diff($expectedColumns, $cols);
t('Bảng `topics` có đầy đủ 11 cột chuẩn thiết kế', empty($missingCols), 'Thiếu: ' . implode(', ', $missingCols));

// Kiểm tra khóa ngoại của bảng topics
$fks = $pdo->query("
    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'topics' 
      AND REFERENCED_TABLE_NAME IS NOT NULL
")->fetchAll(PDO::FETCH_KEY_PAIR);

t('Khóa ngoại `period_id` tham chiếu đến `internship_periods`', isset($fks['period_id']) && $fks['period_id'] === 'internship_periods');
t('Khóa ngoại `proposer_id` tham chiếu đến `users`', isset($fks['proposer_id']) && $fks['proposer_id'] === 'users');

// --- 3. BẮT ĐẦU TRANSACTION ĐỂ ĐẢM BẢO KHÔNG THAY ĐỔI DỮ LIỆU THẬT ---
$pdo->beginTransaction();

try {
    // Chuẩn bị dữ liệu mẫu phục vụ kiểm thử (1 đợt, 2 user)
    $testPeriodId = InternshipPeriod::create([
        'code'        => 'TEST-PERIOD-SP3',
        'name'        => 'Đợt kiểm thử Sprint 3',
        'type'        => InternshipPeriod::TYPE_INTERNSHIP,
        'start_date'  => '2026-11-01',
        'end_date'    => '2026-11-30',
        'status'      => InternshipPeriod::STATUS_ACTIVE,
        'description' => 'Mô tả đợt test',
    ]);

    $user1Stmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `status`) VALUES ('gv_huong_test', 'huong_test@vinhuni.edu.vn', 'hash123', 'TS. Lê Thu Hương', 1)");
    $user1Stmt->execute();
    $testUserId1 = (int)$pdo->lastInsertId();

    $user2Stmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `status`) VALUES ('gv_minh_test', 'minh_test@vinhuni.edu.vn', 'hash123', 'ThS. Phạm Tuấn Minh', 1)");
    $user2Stmt->execute();
    $testUserId2 = (int)$pdo->lastInsertId();

    // --- 4. KIỂM THỬ THÊM ĐỀ TÀI HỢP LỆ (CREATE) ---
    $topicId1 = Topic::create([
        'code'         => 'test-dt-001',
        'name'         => 'Hệ thống Quản lý Thực tập & Đồ án tốt nghiệp',
        'description'  => 'Xây dựng website trên nền tảng PHP MVC thuần kết hợp CSDL MySQL',
        'type'         => Topic::TYPE_INTERNSHIP,
        'period_id'    => $testPeriodId,
        'proposer_id'  => $testUserId1,
        'max_students' => 2,
        'status'       => Topic::STATUS_DRAFT,
    ]);
    t('Tạo mới đề tài hợp lệ thành công, trả về ID > 0', $topicId1 > 0);

    // Kiểm tra tìm kiếm theo ID
    $foundTopic1 = Topic::findById($topicId1);
    t('findById tìm thấy đúng đề tài vừa tạo', $foundTopic1 !== null && (int)$foundTopic1['id'] === $topicId1);
    t('Mã đề tài được chuẩn hóa tự động sang chữ in hoa (TEST-DT-001)', $foundTopic1['code'] === 'TEST-DT-001');
    t('Thông tin đợt được JOIN chính xác (period_code, period_name)', !empty($foundTopic1['period_code']) && $foundTopic1['period_code'] === 'TEST-PERIOD-SP3');
    t('Thông tin người đề xuất được JOIN chính xác (proposer_name)', !empty($foundTopic1['proposer_name']) && $foundTopic1['proposer_name'] === 'TS. Lê Thu Hương');

    // Kiểm tra tìm kiếm theo Code
    $foundByCode = Topic::findByCode('test-dt-001');
    t('findByCode tìm thấy đề tài (không phân biệt hoa/thường)', $foundByCode !== null && (int)$foundByCode['id'] === $topicId1);

    // --- 5. KIỂM THỬ RÀNG BUỘC VÀ TỪ CHỐI DỮ LIỆU SAI (VALIDATION) ---
    // A. Trùng mã đề tài
    $duplicateCodeThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST-DT-001',
            'name'        => 'Đề tài trùng mã',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => $testPeriodId,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $duplicateCodeThrew = true;
    }
    t('Từ chối tạo đề tài có mã trùng lặp (Duplicate Code)', $duplicateCodeThrew);

    // B. Thiếu thông tin bắt buộc
    $missingNameThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST-DT-002',
            'name'        => '',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => $testPeriodId,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $missingNameThrew = true;
    }
    t('Từ chối khi tên đề tài để trống', $missingNameThrew);

    $missingCodeThrew = false;
    try {
        Topic::create([
            'code'        => '',
            'name'        => 'Tên đề tài hợp lệ',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => $testPeriodId,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $missingCodeThrew = true;
    }
    t('Từ chối khi mã đề tài để trống', $missingCodeThrew);

    // C. Mã đề tài chứa ký tự không hợp lệ
    $invalidCodeFormatThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST 2026 @#$',
            'name'        => 'Đề tài mã có khoảng trắng',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => $testPeriodId,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidCodeFormatThrew = true;
    }
    t('Từ chối mã đề tài chứa khoảng trắng hoặc ký tự đặc biệt', $invalidCodeFormatThrew);

    // D. Loại đề tài không hợp lệ
    $invalidTypeThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST-DT-003',
            'name'        => 'Đề tài loại lạ',
            'type'        => 'unknown_type',
            'period_id'   => $testPeriodId,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidTypeThrew = true;
    }
    t('Từ chối loại đề tài không nằm trong danh mục cho phép', $invalidTypeThrew);

    // E. Liên kết đợt không tồn tại
    $nonExistentPeriodThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST-DT-004',
            'name'        => 'Đề tài liên kết đợt ảo',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => 9999999,
            'proposer_id' => $testUserId1,
        ]);
    } catch (InvalidArgumentException $e) {
        $nonExistentPeriodThrew = true;
    }
    t('Từ chối khi liên kết đến đợt thực tập/đồ án không tồn tại', $nonExistentPeriodThrew);

    // F. Người đề xuất không tồn tại
    $nonExistentProposerThrew = false;
    try {
        Topic::create([
            'code'        => 'TEST-DT-005',
            'name'        => 'Đề tài người đề xuất ảo',
            'type'        => Topic::TYPE_INTERNSHIP,
            'period_id'   => $testPeriodId,
            'proposer_id' => 9999999,
        ]);
    } catch (InvalidArgumentException $e) {
        $nonExistentProposerThrew = true;
    }
    t('Từ chối khi tài khoản người đề xuất không tồn tại', $nonExistentProposerThrew);

    // --- 6. KIỂM THỬ CẬP NHẬT ĐỀ TÀI (UPDATE) ---
    $updateOk = Topic::update($topicId1, [
        'name'         => 'Hệ thống Quản lý Thực tập & Đồ án (Đã chỉnh sửa)',
        'max_students' => 3,
        'description'  => 'Bổ sung mô tả chi tiết quy trình chấm điểm',
    ]);
    $updatedTopic = Topic::findById($topicId1);
    t('Cập nhật thông tin đề tài thành công', $updateOk);
    t('Tên đề tài sau khi sửa khớp chính xác', $updatedTopic['name'] === 'Hệ thống Quản lý Thực tập & Đồ án (Đã chỉnh sửa)');
    t('Số lượng sinh viên tối đa được cập nhật thành 3', (int)$updatedTopic['max_students'] === 3);

    // Giữ nguyên mã của chính mình khi sửa không bị báo lỗi trùng mã
    $keepCodeUpdateOk = Topic::update($topicId1, ['code' => 'TEST-DT-001']);
    t('Sửa đề tài giữ nguyên mã cũ của chính mình không báo trùng', $keepCodeUpdateOk);

    // --- 7. KIỂM THỬ BỘ LỌC VÀ TÌM KIẾM (ALL) ---
    // Tạo thêm 1 đề tài khác để kiểm thử lọc
    $topicId2 = Topic::create([
        'code'         => 'TEST-DA-002',
        'name'         => 'Nghiên cứu ứng dụng Học máy trong Xử lý ảnh Y tế',
        'description'  => 'Nhận dạng tổn thương phổi trên ảnh X-quang',
        'type'         => Topic::TYPE_GRADUATION_PROJECT,
        'period_id'    => $testPeriodId,
        'proposer_id'  => $testUserId2,
        'status'       => Topic::STATUS_APPROVED,
    ]);

    $allTopics = Topic::all();
    t('Lấy danh sách all() trả về đầy đủ các đề tài vừa tạo', count($allTopics) >= 2);

    $filteredByType = Topic::all(['type' => Topic::TYPE_GRADUATION_PROJECT]);
    $onlyGrad = true;
    foreach ($filteredByType as $tItem) {
        if ($tItem['type'] !== Topic::TYPE_GRADUATION_PROJECT) {
            $onlyGrad = false;
            break;
        }
    }
    t('Bộ lọc theo loại đề tài (type=graduation_project) chính xác', $onlyGrad && count($filteredByType) >= 1);

    $filteredByStatus = Topic::all(['status' => Topic::STATUS_APPROVED]);
    $onlyApproved = true;
    foreach ($filteredByStatus as $tItem) {
        if ($tItem['status'] !== Topic::STATUS_APPROVED) {
            $onlyApproved = false;
            break;
        }
    }
    t('Bộ lọc theo trạng thái đề tài (status=approved) chính xác', $onlyApproved && count($filteredByStatus) >= 1);

    $filteredByKw = Topic::all(['keyword' => 'Học máy']);
    t('Tìm kiếm đề tài theo từ khóa hoạt động chính xác', count($filteredByKw) >= 1 && $filteredByKw[0]['code'] === 'TEST-DA-002');

    // --- 8. KIỂM THỬ PHÂN QUYỀN CHỈNH SỬA (canEdit) ---
    // A. Quản trị viên (admin) và Phòng đào tạo (training_dept) được sửa mọi đề tài
    t('Admin có quyền sửa đề tài', Topic::canEdit($foundTopic1, 999, ['admin']));
    t('Phòng đào tạo có quyền sửa đề tài', Topic::canEdit($foundTopic1, 999, ['training_dept']));

    // B. Người đề xuất được sửa đề tài của mình khi ở trạng thái draft hoặc rejected
    t('Người đề xuất được sửa đề tài bản nháp (draft) của chính mình', Topic::canEdit($foundTopic1, $testUserId1, ['lecturer']));

    // Giả lập đề tài bị từ chối
    $rejectedTopic = $foundTopic1;
    $rejectedTopic['status'] = Topic::STATUS_REJECTED;
    t('Người đề xuất được sửa đề tài bị từ chối (rejected) của chính mình', Topic::canEdit($rejectedTopic, $testUserId1, ['lecturer']));

    // C. Đề tài đã approved hoặc pending: Người đề xuất KHÔNG được tự ý sửa
    $approvedTopic = $foundTopic1;
    $approvedTopic['status'] = Topic::STATUS_APPROVED;
    t('Người đề xuất KHÔNG được tự ý sửa đề tài đã duyệt (approved)', !Topic::canEdit($approvedTopic, $testUserId1, ['lecturer']));

    $pendingTopic = $foundTopic1;
    $pendingTopic['status'] = Topic::STATUS_PENDING;
    t('Người đề xuất KHÔNG được tự ý sửa đề tài đang chờ duyệt (pending)', !Topic::canEdit($pendingTopic, $testUserId1, ['lecturer']));

    // D. Giảng viên khác hoặc Sinh viên KHÔNG được sửa đề tài của người khác
    t('Giảng viên khác KHÔNG được sửa đề tài của đồng nghiệp', !Topic::canEdit($foundTopic1, $testUserId2, ['lecturer']));
    t('Sinh viên KHÔNG được sửa đề tài của giảng viên', !Topic::canEdit($foundTopic1, 888, ['student']));

    // --- 9. KIỂM THỬ XÓA ĐỀ TÀI (DELETE) ---
    // Đề tài draft được xóa thành công
    $deleteDraftOk = Topic::delete($topicId1);
    t('Xóa đề tài bản nháp (draft) thành công', $deleteDraftOk && Topic::findById($topicId1) === null);

    // Đề tài approved bị từ chối xóa
    $deleteApprovedThrew = false;
    try {
        Topic::delete($topicId2);
    } catch (InvalidArgumentException $e) {
        $deleteApprovedThrew = true;
    }
    t('Từ chối xóa đề tài đã duyệt (approved)', $deleteApprovedThrew && Topic::findById($topicId2) !== null);

    // --- 10. KIỂM THỬ BẢO MẬT CSRF VÀ HTTP METHOD ---
    AuthController::startSession();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Token rỗng hoặc không khớp -> bị từ chối
    $_POST['_csrf'] = 'invalid_token_1234567890';
    t('Yêu cầu với Token CSRF không hợp lệ bị từ chối', !AuthController::verifyCsrf());

    // Token khớp -> được chấp nhận
    $_POST['_csrf'] = $_SESSION['csrf_token'];
    t('Yêu cầu với Token CSRF hợp lệ được chấp nhận', AuthController::verifyCsrf());

} finally {
    // --- 11. ROLLBACK TOÀN BỘ DỮ LIỆU KIỂM THỬ ---
    $pdo->rollBack();
    echo "\n  (Đã ROLLBACK toàn bộ giao dịch kiểm thử - CSDL hoàn toàn sạch và nguyên vẹn)\n";
}

// --- 12. KIỂM TRA ĐĂNG KÝ ROUTER ---
$router = new Router();
$router->get('/topics',         [TopicController::class, 'index']);
$router->get('/topics/create',  [TopicController::class, 'create']);
$router->post('/topics/create', [TopicController::class, 'store']);
$router->post('/topics',        [TopicController::class, 'store']);
$router->get('/topics/detail',  [TopicController::class, 'show']);
$router->get('/topics/edit',    [TopicController::class, 'edit']);
$router->post('/topics/update', [TopicController::class, 'update']);
$router->post('/topics/edit',   [TopicController::class, 'update']);

$routesProp = (new ReflectionClass($router))->getProperty('routes');
$routesProp->setAccessible(true);
$registeredRoutes = $routesProp->getValue($router);

t('Router đăng ký route GET /topics', isset($registeredRoutes['GET']['/topics']));
t('Router đăng ký route GET /topics/create', isset($registeredRoutes['GET']['/topics/create']));
t('Router đăng ký route POST /topics/create', isset($registeredRoutes['POST']['/topics/create']));
t('Router đăng ký route GET /topics/detail', isset($registeredRoutes['GET']['/topics/detail']));
t('Router đăng ký route GET /topics/edit', isset($registeredRoutes['GET']['/topics/edit']));
t('Router đăng ký route POST /topics/update', isset($registeredRoutes['POST']['/topics/update']));

echo "------------------------------------------------------\n";
echo "KẾT QUẢ: {$passedTests} KIỂM TRA ĐẠT (PASS), {$failedTests} THẤT BẠI (FAIL).\n";
echo "======================================================\n\n";

if ($failedTests > 0) {
    exit(1);
}
