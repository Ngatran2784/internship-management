<?php
/**
 * KIỂM THỬ ENCODING TIẾNG VIỆT ĐĂNG KÝ NGUYỆN VỌNG SINH VIÊN
 * Chạy: php tests/test_vietnamese_encoding.php
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

use App\Core\Database;
use App\Models\TopicWish;

$pass = 0;
$fail = 0;

function t(string $desc, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    echo ($cond ? '[ PASS ] ' : '[ FAIL ] ') . $desc . (!$cond && $detail !== '' ? " -> {$detail}" : '') . PHP_EOL;
    $cond ? $pass++ : $fail++;
}

echo PHP_EOL . "==========================================================" . PHP_EOL;
echo "   KIỂM THỬ XỬ LÝ VÀ HIỂN THỊ TIẾNG VIỆT (UTF-8 / utf8mb4)" . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

$pdo = Database::getConnection();

// Bắt đầu transaction kiểm thử
$pdo->beginTransaction();

try {
    $testStudentId = 999955;
    $testPeriodId  = 999955;
    $testTopicId   = 999955;

    // 1. Tạo dữ liệu giả lập
    $pdo->prepare("
        INSERT INTO users (id, username, email, password, full_name, status)
        VALUES (?, 'sv_test_vn', 'sv_test_vn@vinhuni.edu.vn', 'hash', 'Sinh Viên Kiểm Thử Tiếng Việt', 1)
        ON DUPLICATE KEY UPDATE full_name = 'Sinh Viên Kiểm Thử Tiếng Việt'
    ")->execute([$testStudentId]);

    $pdo->prepare("
        INSERT INTO internship_periods (id, name, code, type, start_date, end_date, status)
        VALUES (?, 'Đợt Kiểm Thử Tiếng Việt 2026', 'PERIOD-VN-01', 'internship', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active')
        ON DUPLICATE KEY UPDATE status = 'active'
    ")->execute([$testPeriodId]);

    $pdo->prepare("
        INSERT INTO topics (id, code, name, description, period_id, proposer_id, status, max_students)
        VALUES (?, 'TOPIC-VN-01', 'Xây dựng Mô hình Xử lý Ngôn ngữ Tự nhiên cho Tiếng Việt', 'Mô tả đề tài tiếng Việt', ?, ?, 'approved', 3)
        ON DUPLICATE KEY UPDATE status = 'approved'
    ")->execute([$testTopicId, $testPeriodId, $testStudentId]);

    // 2. Chuỗi kiểm thử theo đúng yêu cầu
    $requiredTestString = "Tôi muốn đăng ký đề tài về trí tuệ nhân tạo và xử lý ngôn ngữ tiếng Việt.";

    // Lưu bằng TopicWish::saveWishes
    $saved = TopicWish::saveWishes($testStudentId, $testPeriodId, [
        [
            'topic_id' => $testTopicId,
            'priority' => 1,
            'note'     => $requiredTestString,
        ]
    ]);

    t("Lưu nguyện vọng với chuỗi tiếng Việt thành công", count($saved) === 1);

    // Đọc lại từ Model
    $wishes = TopicWish::getByStudentAndPeriod($testStudentId, $testPeriodId);
    t("Đọc lại từ database qua Model thành công", count($wishes) === 1);

    $retrievedNote = $wishes[0]['note'] ?? '';
    t("Ghi chú đọc lại giống chính xác chuỗi gốc 100%", $retrievedNote === $requiredTestString);

    // Kiểm tra HEX không có dấu hỏi 0x3F nào
    $hexStmt = $pdo->prepare("SELECT HEX(note) as hex_note FROM topic_wishes WHERE student_id = ? AND period_id = ? AND priority = 1");
    $hexStmt->execute([$testStudentId, $testPeriodId]);
    $hexNote = $hexStmt->fetchColumn();

    $expectedHex = strtoupper(bin2hex($requiredTestString));
    t("Mã HEX trong MySQL trùng khớp byte-for-byte với chuỗi UTF-8", $hexNote === $expectedHex);
    t("Không chứa bất kỳ ký tự dấu hỏi (0x3F) nào", !str_contains($hexNote, '3F'));

    // Kiểm tra render qua View escaping
    $escaped = htmlspecialchars($retrievedNote, ENT_QUOTES, 'UTF-8');
    t("Render qua htmlspecialchars ENT_QUOTES UTF-8 hiển thị nguyên vẹn", $escaped === $requiredTestString);

    // 3. Kiểm tra chỉnh sửa / cập nhật với chuỗi tiếng Việt khác
    $updatedString = "Cập nhật nguyện vọng: Ưu tiên nghiên cứu sâu về học máy và thị giác máy tính.";
    $updated = TopicWish::saveWishes($testStudentId, $testPeriodId, [
        [
            'topic_id' => $testTopicId,
            'priority' => 1,
            'note'     => $updatedString,
        ]
    ]);

    $wishesUpdated = TopicWish::getByStudentAndPeriod($testStudentId, $testPeriodId);
    t("Cập nhật ghi chú tiếng Việt thành công", ($wishesUpdated[0]['note'] ?? '') === $updatedString);

} finally {
    $pdo->rollBack();
}

echo PHP_EOL . "----------------------------------------------------------" . PHP_EOL;
echo "KẾT QUẢ KIỂM THỬ: {$pass} PASS, {$fail} FAIL." . PHP_EOL;
echo "==========================================================" . PHP_EOL;

if ($fail > 0) {
    exit(1);
}
exit(0);
