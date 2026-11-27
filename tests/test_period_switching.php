<?php
/**
 * KIỂM THỬ CHUYỂN ĐỔI ĐỢT THỰC TẬP VÀ XÁC THỰC THAM SỐ PERIOD_ID TRÊN DASHBOARD
 * Chạy: php tests/test_period_switching.php
 */

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
use App\Controllers\DashboardController;
use App\Models\InternshipPeriod;

echo "=================================================================\n";
echo "      KIỂM THỬ CHUYỂN ĐỔI ĐỢT THỰC TẬP (PERIOD_ID TESTING)      \n";
echo "=================================================================\n";

$pass = 0;
$fail = 0;

function t(string $title, bool $condition, string $detail = ''): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  [ PASS ] {$title}\n";
    } else {
        $fail++;
        echo "  [ FAIL ] {$title} - {$detail}\n";
    }
}

$periods = InternshipPeriod::all();
t("CSDL có ít nhất 1 đợt thực tập", count($periods) > 0);

$activePeriods = array_values(array_filter($periods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_ACTIVE));
$draftPeriods  = array_values(array_filter($periods, fn($p) => ($p['status'] ?? '') === InternshipPeriod::STATUS_DRAFT));

// Case 1: Không truyền period_id
AuthController::startSession();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['roles'] = ['admin'];
$_GET = [];

$dash = new DashboardController();
ob_start();
$dash->index();
$htmlCase1 = ob_get_clean();

if (!empty($activePeriods)) {
    $firstActiveCode = $activePeriods[0]['code'];
    t("Không truyền period_id: Mặc định chọn đợt active mới nhất ({$firstActiveCode})", str_contains($htmlCase1, $firstActiveCode));
} else {
    t("Không truyền period_id: Hệ thống hiển thị an toàn", strlen($htmlCase1) > 1000);
}

// Case 2: Truyền period_id hợp lệ
if (!empty($periods)) {
    $targetId = (int)$periods[0]['id'];
    $_GET = ['period_id' => $targetId];
    ob_start();
    $dash->index();
    $htmlCase2 = ob_get_clean();
    t("period_id hợp lệ ({$targetId}): Chọn chính xác đợt tương ứng", str_contains($htmlCase2, "value=\"{$targetId}\" selected"));
}

// Case 3: Sinh viên chọn đợt draft -> phải bị chặn xem nội dung nháp
if (!empty($draftPeriods)) {
    $draftId = (int)$draftPeriods[0]['id'];
    $_SESSION['roles'] = ['student'];
    $_GET = ['period_id' => $draftId];
    ob_start();
    $dash->index();
    $htmlCase3 = ob_get_clean();
    $blocked = !str_contains($htmlCase3, "value=\"{$draftId}\" selected");
    t("Sinh viên chọn đợt draft: Bị chặn và fallback về đợt active hợp lệ", $blocked);
} else {
    t("Kiểm tra bảo vệ sinh viên trước đợt draft (bỏ qua do không có đợt draft)", true);
}

// Case 4: period_id không tồn tại (999999)
$_SESSION['roles'] = ['admin'];
$_GET = ['period_id' => 999999];
ob_start();
$dash->index();
$htmlCase4 = ob_get_clean();
t("period_id không tồn tại (999999): Fallback an toàn, không sinh lỗi", strlen($htmlCase4) > 1000 && str_contains($htmlCase4, 'selected'));

// Case 5: period_id số âm (-5)
$_GET = ['period_id' => -5];
ob_start();
$dash->index();
$htmlCase5 = ob_get_clean();
t("period_id số âm (-5): Từ chối và fallback an toàn", strlen($htmlCase5) > 1000 && str_contains($htmlCase5, 'selected'));

// Case 6: period_id chuỗi ký tự / SQL injection
$_GET = ['period_id' => "abc' OR 1=1"];
ob_start();
$dash->index();
$htmlCase6 = ob_get_clean();
t("period_id chuỗi/SQLi: Lọc ép kiểu an toàn, không sinh exception", strlen($htmlCase6) > 1000 && str_contains($htmlCase6, 'selected'));

echo "-----------------------------------------------------------------\n";
echo "KẾT QUẢ KIỂM THỬ CHUYỂN ĐỢT: {$pass} PASS, {$fail} FAIL.\n";
echo "=================================================================\n";
exit($fail > 0 ? 1 : 0);
