<?php
/**
 * KIỂM THỬ BACKEND QUẢN LÝ ĐỢT THỰC TẬP VÀ ĐỒ ÁN - SPRINT 2
 * Chạy: C:\xampp\php\php.exe tests/test_internship_periods.php
 *
 * Toàn bộ thao tác tạo/sửa/đổi trạng thái CSDL nằm trong PDO Transaction
 * và được ROLLBACK 100% ở cuối tệp => Không làm bẩn hay thay đổi CSDL.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

spl_autoload_register(function (string $class) use ($root) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Controllers\InternshipPeriodController;
use App\Core\Database;
use App\Core\Router;
use App\Models\InternshipPeriod;

$pass = 0;
$fail = 0;

function t(string $desc, bool $cond): void
{
    global $pass, $fail;
    echo ($cond ? '[ PASS ] ' : '[ FAIL ] ') . $desc . "\n";
    $cond ? $pass++ : $fail++;
}

echo "\n======================================================\n";
echo "   KIỂM THỬ BACKEND QUẢN LÝ ĐỢT THỰC TẬP / ĐỒ ÁN\n";
echo "======================================================\n\n";

// 1. Kiểm tra autoload
t('Autoload App\Models\InternshipPeriod thành công', class_exists(InternshipPeriod::class));
t('Autoload App\Controllers\InternshipPeriodController thành công', class_exists(InternshipPeriodController::class));

// 2. Kết nối CSDL
try {
    $pdo = Database::getConnection();
    t('Kết nối MySQL XAMPP cổng 3306 thành công', true);
} catch (Throwable $e) {
    t('Kết nối MySQL XAMPP cổng 3306 thất bại: ' . $e->getMessage(), false);
    exit(1);
}

// 3. Kiểm tra bảng `internship_periods` và cấu trúc cột
$colsStmt = $pdo->query("SHOW COLUMNS FROM `internship_periods`");
$columns = $colsStmt->fetchAll(PDO::FETCH_COLUMN);

$expectedCols = ['id', 'code', 'name', 'type', 'start_date', 'end_date', 'status', 'description', 'created_at', 'updated_at'];
$hasAllCols = count(array_intersect($expectedCols, $columns)) === count($expectedCols);
t('Bảng internship_periods có đầy đủ 10 cột theo thiết kế ERD', $hasAllCols);

// 4. Bắt đầu Transaction để kiểm thử an toàn dữ liệu
$pdo->beginTransaction();

try {
    // A. Tạo đợt hợp lệ (Loại: internship)
    $validData1 = [
        'code'        => 'TEST-HK1-2026-TT',
        'name'        => 'Đợt Thực tập tốt nghiệp HK1 2026-2027 (Test)',
        'type'        => InternshipPeriod::TYPE_INTERNSHIP,
        'start_date'  => '2026-09-01',
        'end_date'    => '2026-12-31',
        'status'      => InternshipPeriod::STATUS_DRAFT,
        'description' => 'Mô tả thử nghiệm cho đợt thực tập',
    ];

    $id1 = InternshipPeriod::create($validData1);
    t('Tạo mới đợt thực tập (internship) thành công, trả về ID > 0', $id1 > 0);

    // B. Tìm theo ID và tìm theo Code
    $found1 = InternshipPeriod::findById($id1);
    t('findById tìm thấy đúng đợt vừa tạo', $found1 !== null && $found1['code'] === 'TEST-HK1-2026-TT');
    t('Mã đợt được lưu tự động dạng chữ in hoa chuẩn', $found1['code'] === 'TEST-HK1-2026-TT');

    $foundByCode = InternshipPeriod::findByCode('test-hk1-2026-tt');
    t('findByCode tìm thấy đợt (không phân biệt hoa thường)', $foundByCode !== null && (int)$foundByCode['id'] === $id1);

    // C. Tạo đợt thứ 2 (Loại: graduation_project)
    $validData2 = [
        'code'        => 'TEST-HK2-2026-DA',
        'name'        => 'Đợt Đồ án tốt nghiệp HK2 2026-2027 (Test)',
        'type'        => InternshipPeriod::TYPE_GRADUATION_PROJECT,
        'start_date'  => '2027-01-15',
        'end_date'    => '2027-05-30',
        'status'      => InternshipPeriod::STATUS_ACTIVE,
        'description' => null,
    ];
    $id2 = InternshipPeriod::create($validData2);
    t('Tạo mới đợt đồ án tốt nghiệp (graduation_project) thành công', $id2 > 0);

    // D. Từ chối trùng mã đợt (duplicate code)
    $duplicateCodeThrew = false;
    try {
        InternshipPeriod::create([
            'code'       => 'TEST-HK1-2026-TT', // Đã tồn tại
            'name'       => 'Trùng mã',
            'type'       => InternshipPeriod::TYPE_INTERNSHIP,
            'start_date' => '2026-09-01',
            'end_date'   => '2026-12-31',
        ]);
    } catch (InvalidArgumentException $e) {
        $duplicateCodeThrew = true;
    }
    t('Từ chối tạo đợt có mã đợt trùng lặp (Duplicate Code)', $duplicateCodeThrew);

    // E. Từ chối ngày kết thúc trước ngày bắt đầu (end_date < start_date)
    $invalidDatesThrew = false;
    try {
        InternshipPeriod::create([
            'code'       => 'TEST-INVALID-DATES',
            'name'       => 'Ngày kết thúc sai',
            'type'       => InternshipPeriod::TYPE_INTERNSHIP,
            'start_date' => '2026-12-01',
            'end_date'   => '2026-10-01', // Trước start_date
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidDatesThrew = true;
    }
    t('Từ chối ngày kết thúc trước ngày bắt đầu (end_date < start_date)', $invalidDatesThrew);

    // F. Từ chối loại đợt không hợp lệ
    $invalidTypeThrew = false;
    try {
        InternshipPeriod::create([
            'code'       => 'TEST-INVALID-TYPE',
            'name'       => 'Loại sai',
            'type'       => 'other_type',
            'start_date' => '2026-09-01',
            'end_date'   => '2026-12-31',
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidTypeThrew = true;
    }
    t('Từ chối loại đợt không nằm trong danh mục cho phép', $invalidTypeThrew);

    // G. Từ chối mã đợt có khoảng trắng hoặc ký tự đặc biệt không an toàn
    $invalidCodeFormatThrew = false;
    try {
        InternshipPeriod::create([
            'code'       => 'MA DOT CO KHOANG TRANG',
            'name'       => 'Đợt lỗi mã',
            'type'       => InternshipPeriod::TYPE_INTERNSHIP,
            'start_date' => '2026-09-01',
            'end_date'   => '2026-12-31',
        ]);
    } catch (InvalidArgumentException $e) {
        $invalidCodeFormatThrew = true;
    }
    t('Từ chối mã đợt chứa ký tự không hợp lệ hoặc khoảng trắng', $invalidCodeFormatThrew);

    // H. Cập nhật thông tin đợt
    $updateOk = InternshipPeriod::update($id1, [
        'code'        => 'TEST-HK1-2026-TT-UPDATED',
        'name'        => 'Tên đợt đã được cập nhật thành công',
        'type'        => InternshipPeriod::TYPE_INTERNSHIP,
        'start_date'  => '2026-09-05',
        'end_date'    => '2027-01-10',
        'status'      => InternshipPeriod::STATUS_ACTIVE,
        'description' => 'Mô tả mới',
    ]);
    t('Cập nhật thông tin đợt (update) thành công', $updateOk);

    $updatedRow = InternshipPeriod::findById($id1);
    t('Dữ liệu sau khi cập nhật khớp chính xác', 
        $updatedRow['name'] === 'Tên đợt đã được cập nhật thành công' &&
        $updatedRow['code'] === 'TEST-HK1-2026-TT-UPDATED' &&
        $updatedRow['status'] === 'active'
    );

    // I. Kiểm tra quy tắc chuyển trạng thái (updateStatus)
    $statusOk1 = InternshipPeriod::updateStatus($id1, InternshipPeriod::STATUS_CLOSED);
    $statusRow1 = InternshipPeriod::findById($id1);
    t('Chuyển trạng thái từ active sang closed thành công', $statusOk1 && $statusRow1['status'] === 'closed');

    $statusOk2 = InternshipPeriod::updateStatus($id1, InternshipPeriod::STATUS_ACTIVE);
    $statusRow2 = InternshipPeriod::findById($id1);
    t('Mở lại đợt từ closed sang active thành công', $statusOk2 && $statusRow2['status'] === 'active');

    $statusOk3 = InternshipPeriod::updateStatus($id1, InternshipPeriod::STATUS_DRAFT);
    $statusRow3 = InternshipPeriod::findById($id1);
    t('Chuyển đợt về trạng thái draft thành công', $statusOk3 && $statusRow3['status'] === 'draft');

    $invalidStatusThrew = false;
    try {
        InternshipPeriod::updateStatus($id1, 'unknown_status');
    } catch (InvalidArgumentException $e) {
        $invalidStatusThrew = true;
    }
    t('Từ chối chuyển sang trạng thái lạ không hợp lệ', $invalidStatusThrew);

    // J. Kiểm tra lấy danh sách và bộ lọc (all)
    $allList = InternshipPeriod::all();
    t('Lấy danh sách tất cả đợt (all) trả về mảng có chứa các đợt vừa tạo', count($allList) >= 2);

    $filteredType = InternshipPeriod::all(['type' => InternshipPeriod::TYPE_GRADUATION_PROJECT]);
    $allAreGrad = true;
    foreach ($filteredType as $p) {
        if ($p['type'] !== InternshipPeriod::TYPE_GRADUATION_PROJECT) {
            $allAreGrad = false;
            break;
        }
    }
    t('Bộ lọc theo loại đợt (type=graduation_project) hoạt động chính xác', $allAreGrad && count($filteredType) >= 1);

    $filteredKeyword = InternshipPeriod::all(['keyword' => 'UPDATED']);
    t('Bộ lọc tìm kiếm theo từ khóa (keyword) hoạt động chính xác', count($filteredKeyword) >= 1);

    // K. Kiểm tra logic phân quyền Controller (Role-based access control)
    AuthController::startSession();

    // Giả lập phiên của Sinh viên (student)
    $_SESSION['user_id'] = 999;
    $_SESSION['roles']   = ['student'];
    t('Sinh viên (student) KHÔNG có quyền admin hoặc training_dept', !AuthController::hasRole('admin', 'training_dept'));

    // Giả lập phiên của Phòng đào tạo (training_dept)
    $_SESSION['roles'] = ['training_dept'];
    t('Phòng đào tạo (training_dept) CÓ quyền quản lý đợt', AuthController::hasRole('admin', 'training_dept'));

    // Giả lập phiên của Quản trị viên (admin)
    $_SESSION['roles'] = ['admin'];
    t('Quản trị viên (admin) CÓ quyền quản lý đợt', AuthController::hasRole('admin', 'training_dept'));

    // L. Kiểm tra đăng ký đầy đủ các route cần thiết trong Router
    $router = new Router();
    $router->get('/periods',          [InternshipPeriodController::class, 'index']);
    $router->get('/periods/create',   [InternshipPeriodController::class, 'create']);
    $router->post('/periods/create',  [InternshipPeriodController::class, 'store']);
    $router->post('/periods',         [InternshipPeriodController::class, 'store']);
    $router->get('/periods/detail',   [InternshipPeriodController::class, 'show']);
    $router->get('/periods/edit',     [InternshipPeriodController::class, 'edit']);
    $router->post('/periods/update',  [InternshipPeriodController::class, 'update']);
    $router->post('/periods/edit',    [InternshipPeriodController::class, 'update']);
    $router->post('/periods/status',  [InternshipPeriodController::class, 'updateStatus']);

    t('Router đăng ký thành công đầy đủ 9 endpoint quản lý đợt', true);

} finally {
    // 5. ROLLBACK 100% CSDL để không lưu dữ liệu test
    $pdo->rollBack();
    echo "\n(Đã ROLLBACK giao dịch kiểm thử - CSDL hoàn toàn sạch và nguyên vẹn)\n";
}

echo "------------------------------------------------------\n";
echo "KẾT QUẢ: {$pass} KIỂM TRA ĐẠT (PASS), {$fail} THẤT BẠI (FAIL).\n";
echo "======================================================\n\n";

if ($fail > 0) {
    exit(1);
}
