<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * BỘ KIỂM THỬ TỰ ĐỘNG: TIẾN ĐỘ, UPLOAD BÁO CÁO, PHÂN QUYỀN VÀ HẠN NỘP
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
use App\Models\InternshipReport;
use App\Models\ReportReview;
use App\Models\User;
use App\Services\ReportUploadService;
use App\Controllers\MilestoneController;

$passed = 0;
$failed = 0;
$pending = 0;

function t(string $id, string $desc, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  [PASS] [{$id}] {$desc}\n";
    } else {
        $failed++;
        echo "  [FAIL] [{$id}] {$desc} " . ($detail ? "-> {$detail}" : '') . "\n";
    }
}

function p(string $id, string $desc, string $note): void {
    global $pending;
    $pending++;
    echo "  [PENDING] [{$id}] {$desc} (Chờ tích hợp: {$note})\n";
}

echo "==============================================================================\n";
echo "   CHẠY BỘ KIỂM THỬ: TIẾN ĐỘ, UPLOAD BÁO CÁO, PHÂN QUYỀN VÀ HẠN NỘP\n";
echo "==============================================================================\n\n";

$pdo = Database::getConnection();
$pdo->beginTransaction();

// Tạo thư mục tạm an toàn trong OS temp dir để test upload file
$tmpDir = sys_get_temp_dir() . '/test_reports_' . uniqid();
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

try {
    // ==========================================================================
    // PHẦN 1: KIỂM THỬ TẢI LÊN BÁO CÁO (UPLOAD TESTING)
    // ==========================================================================
    echo "--- 1. KIỂM THỬ UPLOAD BÁO CÁO (UPLOAD TESTING) ---\n";

    // TC-UP-01: Tệp đúng định dạng cho phép (.pdf, .docx, .zip)
    $validFiles = [
        ['name' => 'Bao_cao_de_cuong.pdf', 'ext' => 'pdf', 'mime' => 'application/pdf'],
        ['name' => 'Bao_cao_giua_ky.docx', 'ext' => 'docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ['name' => 'Ma_nguon_va_tai_lieu.zip', 'ext' => 'zip', 'mime' => 'application/zip'],
    ];
    foreach ($validFiles as $vf) {
        $fakePath = $tmpDir . '/' . $vf['name'];
        file_put_contents($fakePath, "Fake content for {$vf['name']}");
        $fileArray = [
            'name'     => $vf['name'],
            'type'     => $vf['mime'],
            'tmp_name' => $fakePath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($fakePath),
        ];
        $errs = ReportUploadService::validateFile($fileArray);
        t("TC-UP-01", "Chấp nhận tệp định dạng hợp lệ (.{$vf['ext']})", empty($errs));
    }

    // TC-UP-02: Tệp không đúng định dạng (.exe, .php, .jpg)
    $invalidFiles = [
        ['name' => 'trojan.exe', 'ext' => 'exe'],
        ['name' => 'shell.php', 'ext' => 'php'],
        ['name' => 'avatar.jpg', 'ext' => 'jpg'],
    ];
    foreach ($invalidFiles as $ivf) {
        $fakePath = $tmpDir . '/' . $ivf['name'];
        file_put_contents($fakePath, "Fake malicious or invalid content");
        $fileArray = [
            'name'     => $ivf['name'],
            'type'     => 'application/octet-stream',
            'tmp_name' => $fakePath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($fakePath),
        ];
        $errs = ReportUploadService::validateFile($fileArray);
        t("TC-UP-02", "Từ chối tệp không đúng định dạng (.{$ivf['ext']})", !empty($errs['file']));
    }

    // TC-UP-03: Tệp vượt dung lượng (> 20MB)
    $overSizeFile = [
        'name'     => 'oversized_document.pdf',
        'type'     => 'application/pdf',
        'tmp_name' => $tmpDir . '/oversized_dummy.tmp',
        'error'    => UPLOAD_ERR_OK,
        'size'     => 25 * 1024 * 1024, // 25MB > 20MB
    ];
    $errsOverSize = ReportUploadService::validateFile($overSizeFile);
    t("TC-UP-03", "Từ chối tệp vượt quá dung lượng quy định (25MB > 20MB)", !empty($errsOverSize['file']) && str_contains($errsOverSize['file'], 'vượt quá giới hạn'));

    // TC-UP-04: Không chọn tệp nhưng vẫn gửi biểu mẫu
    $noFile = [
        'name'     => '',
        'type'     => '',
        'tmp_name' => '',
        'error'    => UPLOAD_ERR_NO_FILE,
        'size'     => 0,
    ];
    $errsNoFile = ReportUploadService::validateFile($noFile);
    t("TC-UP-04", "Báo lỗi khi gửi form mà không chọn tệp tin", !empty($errsNoFile['file']) && str_contains($errsNoFile['file'], 'Vui lòng chọn tệp tin'));

    // TC-UP-05: Lưu thông tin tệp và lịch sử nộp báo cáo (không ghi đè bản cũ)
    $fakeTestReport = $tmpDir . '/test_submission_v1.pdf';
    file_put_contents($fakeTestReport, "%PDF-1.4 Fake PDF Content v1");
    $storedV1 = ReportUploadService::processAndStore([
        'name'     => 'Bao_cao_sv001_v1.pdf',
        'type'     => 'application/pdf',
        'tmp_name' => $fakeTestReport,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($fakeTestReport),
    ], 11, 6, 2, 1, $tmpDir);

    $subResult1 = InternshipReport::submit([
        'milestone_id'  => 2,
        'assignment_id' => 154,
        'student_id'    => 6,
        'period_id'     => 11,
        'title'         => 'Báo cáo giữa kỳ sv001',
        'student_note'  => 'Nộp lần đầu v1',
    ], $storedV1);

    t("TC-UP-05.1", "Tạo bản ghi báo cáo phiên bản 1 (v1)", $subResult1['version'] === 1 && $subResult1['report_id'] > 0);

    // Nộp lại lần 2 (v2)
    $fakeTestReportV2 = $tmpDir . '/test_submission_v2.pdf';
    file_put_contents($fakeTestReportV2, "%PDF-1.4 Fake PDF Content v2 - Updated");
    $storedV2 = ReportUploadService::processAndStore([
        'name'     => 'Bao_cao_sv001_v2_updated.pdf',
        'type'     => 'application/pdf',
        'tmp_name' => $fakeTestReportV2,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($fakeTestReportV2),
    ], 11, 6, 2, 2, $tmpDir);

    $subResult2 = InternshipReport::submit([
        'milestone_id'  => 2,
        'assignment_id' => 154,
        'student_id'    => 6,
        'period_id'     => 11,
        'title'         => 'Báo cáo giữa kỳ sv001 - v2',
        'student_note'  => 'Nộp lại sau chỉnh sửa v2',
    ], $storedV2);

    t("TC-UP-05.2", "Tạo bản ghi báo cáo phiên bản 2 (v2)", $subResult2['version'] === 2);

    // Kiểm tra lịch sử lưu đủ 2 bản ghi
    $subs = InternshipReport::getSubmissions($subResult1['report_id']);
    t("TC-UP-05.3", "Bảo lưu lịch sử nộp đầy đủ 2 phiên bản (không bị ghi đè mất v1)", count($subs) === 2);
    t("TC-UP-05.4", "Phiên bản v2 lưu đúng tên tệp mới", $subs[0]['file_name'] === 'Bao_cao_sv001_v2_updated.pdf');
    t("TC-UP-05.5", "Phiên bản v1 vẫn còn nguyên vẹn", $subs[1]['file_name'] === 'Bao_cao_sv001_v1.pdf');

    // ==========================================================================
    // PHẦN 2: KIỂM THỬ PHÂN QUYỀN TRUY CẬP (RBAC TESTING)
    // ==========================================================================
    echo "\n--- 2. KIỂM THỬ PHÂN QUYỀN (RBAC TESTING) ---\n";

    // TC-SEC-01: Admin có toàn quyền quản lý mốc
    t("TC-SEC-01", "Quản trị viên (admin) có quyền quản lý milestone", in_array('admin', MilestoneController::MANAGING_ROLES, true));

    // TC-SEC-02: Phòng đào tạo có quyền quản lý mốc
    t("TC-SEC-02", "Phòng Đào tạo (training_dept) có quyền quản lý milestone", in_array('training_dept', MilestoneController::MANAGING_ROLES, true));

    // TC-SEC-03: Trưởng bộ môn có quyền quản lý mốc
    t("TC-SEC-03", "Trưởng bộ môn (department_head) có quyền quản lý milestone", in_array('department_head', MilestoneController::MANAGING_ROLES, true));

    // TC-SEC-04: Giảng viên thông thường không có quyền quản lý mốc chung
    t("TC-SEC-04", "Giảng viên (lecturer) không thuộc nhóm quản lý mốc chung", !in_array('lecturer', MilestoneController::MANAGING_ROLES, true));

    // TC-SEC-05: Sinh viên KHÔNG có quyền tạo/sửa/xóa mốc
    t("TC-SEC-05", "Sinh viên (student) bị chặn quyền quản lý mốc tiến độ (chỉ đọc)", !in_array('student', MilestoneController::MANAGING_ROLES, true));

    // TC-SEC-06: Sinh viên chỉ xem được mốc thuộc đợt được phân công
    $studentId = 6; // sv001
    $assignedPeriodId = 11;
    $studentMilestones = Milestone::getStudentMilestones($studentId, $assignedPeriodId);
    $allMsPeriod11 = Milestone::all($assignedPeriodId);
    t("TC-SEC-06", "Sinh viên chỉ nhận dữ liệu mốc thuộc đợt được phân công", count($studentMilestones) === count($allMsPeriod11));

    // ==========================================================================
    // PHẦN 3: KIỂM THỬ HẠN NỘP & LỊCH SỬ DUYỆT (DEADLINE TESTING)
    // ==========================================================================
    echo "\n--- 3. KIỂM THỬ HẠN NỘP & ĐỒNG BỘ TRẠNG THÁI (DEADLINE TESTING) ---\n";

    // TC-DL-01: Nộp trước hạn chót -> is_late = 0
    $subEarly = InternshipReport::submit([
        'milestone_id'  => 4, // due_date = 2026-12-15 (tương lai)
        'assignment_id' => 154,
        'student_id'    => 6,
        'period_id'     => 11,
        'title'         => 'Báo cáo nộp sớm',
    ], $storedV1);
    t("TC-DL-01", "Nộp trước hạn chót (now < due_date): Ghi nhận is_late = 0", $subEarly['is_late'] === 0);

    // TC-DL-02: Nộp sau hạn chót -> is_late = 1
    $subLate = InternshipReport::submit([
        'milestone_id'  => 3, // due_date = 2026-11-20 (quá khứ)
        'assignment_id' => 154,
        'student_id'    => 6,
        'period_id'     => 11,
        'title'         => 'Báo cáo nộp muộn',
    ], $storedV1);
    t("TC-DL-02", "Nộp sau hạn chót (now > due_date): Tự động gắn cờ is_late = 1", $subLate['is_late'] === 1);

    // TC-DL-03: Tự động cập nhật mốc quá hạn sang trạng thái 'overdue'
    $synced = Milestone::syncOverdueStatus(11);
    $ms3 = Milestone::findById(3);
    t("TC-DL-03", "Tự động phát hiện và chuyển mốc quá hạn sang trạng thái 'overdue'", $ms3['status'] === 'overdue');

    // TC-DL-04: Lưu nhận xét và yêu cầu nộp lại
    $reviewId = ReportReview::create([
        'report_id'         => $subResult1['report_id'],
        'submission_id'     => $subResult2['submission_id'],
        'reviewer_id'       => 1000126, // gv001
        'decision'          => 'resubmit_required',
        'comments'          => 'Báo cáo cần bổ sung kết quả phân tích số liệu thực tế.',
        'edit_requirements' => '1. Bổ sung biểu đồ phân tích\n2. Cập nhật kết luận',
        'score'             => null,
    ]);
    t("TC-DL-04.1", "Tạo nhận xét và yêu cầu nộp lại (resubmit_required) thành công", $reviewId > 0);

    $reportAfterReview = InternshipReport::findById($subResult1['report_id']);
    t("TC-DL-04.2", "Đồng bộ trạng thái báo cáo chính sang 'resubmit_required'", $reportAfterReview['status'] === 'resubmit_required');

    // ==========================================================================
    // PHẦN 4: KIỂM THỬ XÁC THỰC DỮ LIỆU ĐẦU VÀO (INPUT VALIDATION)
    // ==========================================================================
    echo "\n--- 4. KIỂM THỬ DỮ LIỆU ĐẦU VÀO (INPUT VALIDATION) ---\n";

    // TC-VAL-01: Bỏ trống tiêu đề mốc
    $valErrs1 = Milestone::validate(['title' => '', 'period_id' => 11, 'start_date' => '2026-11-01', 'due_date' => '2026-11-30']);
    t("TC-VAL-01", "Validation chặn khi bỏ trống tiêu đề mốc", isset($valErrs1['title']));

    // TC-VAL-02: Tiêu đề vượt quá 255 ký tự
    $valErrs2 = Milestone::validate(['title' => str_repeat('A', 256), 'period_id' => 11, 'start_date' => '2026-11-01', 'due_date' => '2026-11-30']);
    t("TC-VAL-02", "Validation chặn khi tiêu đề vượt quá 255 ký tự", isset($valErrs2['title']));

    // TC-VAL-03: Ngày tháng không hợp lệ
    $valErrs3 = Milestone::validate(['title' => 'Mốc test', 'period_id' => 11, 'start_date' => 'ngay-khong-hop-le', 'due_date' => '2026-11-30']);
    t("TC-VAL-03", "Validation chặn khi ngày bắt đầu sai định dạng", isset($valErrs3['start_date']));

    // TC-VAL-04: start_date > due_date
    $valErrs4 = Milestone::validate(['title' => 'Mốc test', 'period_id' => 11, 'start_date' => '2026-12-01', 'due_date' => '2026-11-01']);
    t("TC-VAL-04", "Validation chặn khi ngày bắt đầu lớn hơn hạn nộp (start_date > due_date)", isset($valErrs4['due_date']));

    // TC-VAL-05: Trạng thái không hợp lệ
    $valErrs5 = Milestone::validate(['title' => 'Mốc test', 'period_id' => 11, 'start_date' => '2026-11-01', 'due_date' => '2026-11-30', 'status' => 'trang_thai_la']);
    t("TC-VAL-05", "Validation chặn khi trạng thái không nằm trong danh mục cho phép", isset($valErrs5['status']));

    // ==========================================================================
    // PHẦN 5: THEO DÕI TÍCH HỢP CÁC CHỨC NĂNG GIAO DIỆN
    // ==========================================================================
    echo "\n--- 5. THEO DÕI TÍCH HỢP CÁC CHỨC NĂNG GIAO DIỆN ---\n";
    p("TC-INT-01", "Giao diện viết nhật ký thực tập hàng tuần", "Backend Model & CSDL đã sẵn sàng");
    p("TC-INT-02", "Giao diện tải lên báo cáo & xem phản hồi", "Backend Upload Service & CSDL đã sẵn sàng");
    p("TC-INT-03", "Giao diện dòng thời gian & biểu đồ tiến độ sinh viên", "Endpoint /api/milestones/progress đã sẵn sàng");
    p("TC-INT-04", "Giao diện chấm điểm & nhận xét rubric của GVHD", "Backend Model report_reviews đã sẵn sàng");

} finally {
    // Dọn dẹp tệp tạm
    if (is_dir($tmpDir)) {
        $files = glob($tmpDir . '/*');
        foreach ($files as $f) {
            if (is_file($f)) @unlink($f);
        }
        @rmdir($tmpDir);
    }

    $pdo->rollBack();
    echo "\n(Toàn bộ giao dịch kiểm thử đã được ROLLBACK an toàn tuyệt đối)\n";
}

echo "==============================================================================\n";
echo "TỔNG KẾT: {$passed} PASS, {$failed} FAIL, {$pending} PENDING (CHỜ TÍCH HỢP GIAO DIỆN).\n";
echo "==============================================================================\n";
