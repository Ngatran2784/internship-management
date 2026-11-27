<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * BỘ KIỂM THỬ QUẢN LÝ MILESTONE (MỐC TIẾN ĐỘ THỰC TẬP / ĐỒ ÁN)
 * Dự án: Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên
 * ==============================================================================
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\Database;
use App\Models\Milestone;
use App\Models\InternshipPeriod;
use App\Models\User;
use App\Controllers\MilestoneController;

$passed = 0;
$failed = 0;

function t(string $desc, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  [PASS] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$desc} " . ($detail ? "-> {$detail}" : '') . "\n";
    }
}

echo "==============================================================================\n";
echo "   CHẠY BỘ KIỂM THỬ QUẢN LÝ MILESTONE (MỐC TIẾN ĐỘ)\n";
echo "==============================================================================\n\n";

$pdo = Database::getConnection();
$pdo->beginTransaction();

try {
    // --- 1. KIỂM THỬ TRUY VẤN VÀ LỌC DANH SÁCH MILESTONE ---
    echo "--- 1. Kiểm thử Xem & Lọc danh sách Milestone ---\n";
    $allMs = Milestone::all();
    t("Lấy toàn bộ danh sách milestone", count($allMs) >= 8);

    $p11Ms = Milestone::all(11);
    t("Lọc milestone theo Đợt 11", count($p11Ms) === 4);

    $completedMs = Milestone::all(11, Milestone::STATUS_COMPLETED);
    t("Lọc milestone theo trạng thái 'completed' trong Đợt 11", count($completedMs) === 2);

    $searchMs = Milestone::all(11, null, 'đề cương');
    t("Tìm kiếm milestone theo từ khóa 'đề cương'", count($searchMs) >= 1 && str_contains(mb_strtolower($searchMs[0]['title']), 'đề cương'));

    // --- 2. KIỂM THỬ TẠO MỚI VÀ VALIDATION RÀNG BUỘC ---
    echo "\n--- 2. Kiểm thử Tạo mới Milestone & Kiểm tra dữ liệu đầu vào ---\n";
    
    // Test 2.1: Bỏ trống tiêu đề
    $errEmptyTitle = false;
    try {
        Milestone::create([
            'period_id'   => 11,
            'title'       => '',
            'start_date'  => '2026-11-01',
            'due_date'    => '2026-11-15 23:59:59',
            'created_by'  => 5,
        ]);
    } catch (InvalidArgumentException $e) {
        $errEmptyTitle = true;
    }
    t("Chặn tạo milestone khi bỏ trống tiêu đề", $errEmptyTitle);

    // Test 2.2: Đợt không tồn tại
    $errInvalidPeriod = false;
    try {
        Milestone::create([
            'period_id'   => 999999,
            'title'       => 'Mốc test đợt ảo',
            'start_date'  => '2026-11-01',
            'due_date'    => '2026-11-15 23:59:59',
            'created_by'  => 5,
        ]);
    } catch (InvalidArgumentException $e) {
        $errInvalidPeriod = true;
    }
    t("Chặn tạo milestone với period_id không tồn tại", $errInvalidPeriod);

    // Test 2.3: Ngày không hợp lệ (due_date < start_date)
    $errDueDateEarlier = false;
    try {
        Milestone::create([
            'period_id'   => 11,
            'title'       => 'Mốc lỗi thời gian',
            'start_date'  => '2026-11-20',
            'due_date'    => '2026-11-10 23:59:59', // Sớm hơn start_date!
            'created_by'  => 5,
        ]);
    } catch (InvalidArgumentException $e) {
        $errDueDateEarlier = true;
    }
    t("Chặn tạo milestone khi hạn nộp sớm hơn ngày bắt đầu (due_date < start_date)", $errDueDateEarlier);

    // Test 2.4: Tạo hợp lệ
    $newId = Milestone::create([
        'period_id'    => 11,
        'title'        => 'Mốc kiểm thử tự động',
        'description'  => 'Mô tả chi tiết yêu cầu nộp bài kiểm thử',
        'start_date'   => '2026-11-01',
        'due_date'     => '2026-11-30 23:59:59',
        'order_index'  => 5,
        'status'       => Milestone::STATUS_ACTIVE,
        'is_mandatory' => 1,
        'created_by'   => 5,
    ]);
    t("Tạo mới milestone thành công với dữ liệu hợp lệ", $newId > 0);

    $createdMs = Milestone::findById($newId);
    t("Tìm milestone vừa tạo theo ID chính xác", $createdMs !== null && $createdMs['title'] === 'Mốc kiểm thử tự động');
    t("Liên kết đúng đợt thực tập", (int)$createdMs['period_id'] === 11);

    // --- 3. KIỂM THỬ CẬP NHẬT MILESTONE ---
    echo "\n--- 3. Kiểm thử Cập nhật Milestone ---\n";
    $upOk = Milestone::update($newId, [
        'title'       => 'Mốc kiểm thử tự động (Đã sửa đổi)',
        'description' => 'Mô tả đã được cập nhật nội dung mới',
        'status'      => Milestone::STATUS_COMPLETED,
    ]);
    t("Cập nhật thông tin milestone thành công", $upOk);

    $updatedMs = Milestone::findById($newId);
    t("Dữ liệu sau khi cập nhật phản ánh chính xác", $updatedMs['title'] === 'Mốc kiểm thử tự động (Đã sửa đổi)' && $updatedMs['status'] === Milestone::STATUS_COMPLETED);

    // Cập nhật trạng thái nhanh
    $stOk = Milestone::updateStatus($newId, Milestone::STATUS_ACTIVE);
    t("Cập nhật nhanh trạng thái qua updateStatus()", $stOk && Milestone::findById($newId)['status'] === Milestone::STATUS_ACTIVE);

    // --- 4. KIỂM THỬ ĐỒNG BỘ MỐC QUÁ HẠN (OVERDUE SYNC) ---
    echo "\n--- 4. Kiểm thử Đồng bộ Mốc quá hạn (Overdue Sync) ---\n";
    // Mốc 3 trong seed có due_date là 2026-11-20 (đã qua so với hiện tại 2026-11-26)
    $overdueRows = Milestone::syncOverdueStatus(11);
    $m3 = Milestone::findById(3);
    t("Tự động đồng bộ mốc có due_date trong quá khứ sang trạng thái 'overdue'", $m3['status'] === Milestone::STATUS_OVERDUE);

    // --- 5. KIỂM THỬ XÓA VÀ RÀNG BUỘC TOÀN VẸN BẢO VỆ DỮ LIỆU ---
    echo "\n--- 5. Kiểm thử Xóa Milestone & Ràng buộc bảo vệ dữ liệu ---\n";
    // Mốc 1 đã có báo cáo nộp trong `internship_reports` -> PHẢI CHẶN XÓA!
    $reason = '';
    $canDeleteM1 = Milestone::canDelete(1, $reason);
    t("canDelete() phát hiện mốc đã có báo cáo nộp và trả về false", !$canDeleteM1 && !empty($reason));

    $deleteM1Blocked = false;
    try {
        Milestone::delete(1);
    } catch (InvalidArgumentException $e) {
        $deleteM1Blocked = true;
    }
    t("Chặn xóa thành công mốc đã có báo cáo nộp (Bảo vệ toàn vẹn dữ liệu)", $deleteM1Blocked);

    // Mốc $newId vừa tạo chưa có báo cáo -> Cho phép xóa an toàn
    $delOk = Milestone::delete($newId);
    t("Xóa thành công mốc hợp lệ chưa có dữ liệu báo cáo liên quan", $delOk && Milestone::findById($newId) === null);

    // --- 6. KIỂM THỬ TÍCH HỢP DỮ LIỆU THỐNG KÊ TIẾN ĐỘ ---
    echo "\n--- 6. Kiểm thử Cung cấp Dữ liệu Thống kê Tiến độ ---\n";
    $stats = Milestone::getProgressStats(11, 6);
    t("Tính toán tổng số mốc của đợt", $stats['total_milestones'] === 4);
    t("Tính toán mốc hoàn thành và tỷ lệ hoàn thành", isset($stats['completion_rate']) && $stats['completion_rate'] >= 0);
    t("Tính toán tiến độ riêng của sinh viên (student_rate)", isset($stats['student_rate']));

    $studentMilestones = Milestone::getStudentMilestones(6, 11);
    t("Lấy danh sách milestone kèm trạng thái nộp bài của sinh viên", count($studentMilestones) === 4);
    t("Nhận diện chính xác mốc sinh viên đã nộp báo cáo (report_id)", !empty($studentMilestones[0]['report_id']));

    // --- 7. KIỂM THỬ PHÂN QUYỀN (RBAC) CONTROLLER ---
    echo "\n--- 7. Kiểm thử Phân quyền (RBAC) Quản lý Milestone ---\n";
    t("Vai trò quản lý bao gồm admin, training_dept, department_head", 
        in_array('admin', MilestoneController::MANAGING_ROLES, true) &&
        in_array('training_dept', MilestoneController::MANAGING_ROLES, true) &&
        in_array('department_head', MilestoneController::MANAGING_ROLES, true)
    );
    t("Sinh viên (student) KHÔNG nằm trong danh sách quản lý mốc", 
        !in_array('student', MilestoneController::MANAGING_ROLES, true)
    );

} finally {
    // Luôn Rollback để bảo vệ CSDL
    $pdo->rollBack();
    echo "\n(Toàn bộ giao dịch kiểm thử đã được ROLLBACK an toàn tuyệt đối)\n";
}

echo "==============================================================================\n";
echo "TỔNG KẾT: {$passed} PASS, {$failed} FAIL / TỔNG SỐ " . ($passed + $failed) . " TIÊU CHÍ.\n";
echo "==============================================================================\n";
