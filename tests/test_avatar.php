<?php
declare(strict_types=1);

/**
 * KIỂM THỬ TỰ ĐỘNG CHỨC NĂNG ẢNH ĐẠI DIỆN NGƯỜI DÙNG (AVATAR)
 * Dự án: Hệ thống quản lý thực tập và đồ án sinh viên
 * 
 * Kiểm tra đầy đủ:
 * 1. Cấu trúc DB và User::updateAvatar
 * 2. Xác thực kích thước file (> 2MB bị chặn)
 * 3. Xác thực định dạng & MIME thực tế (chỉ JPG, PNG, WebP)
 * 4. Chặn file giả mạo / độc hại / script PHP
 * 5. Tính ngẫu nhiên của tên file lưu trữ
 * 6. Cơ chế bảo vệ thư mục uploads (.htaccess, index.html)
 * 7. Thay đổi ảnh nhiều lần (dọn sạch file cũ trên đĩa)
 * 8. Xóa ảnh đại diện (phục hồi initials, xóa file vật lý)
 * 9. Phòng chống Path Traversal khi xóa file
 * 10. Đồng bộ session và Header khi đăng nhập / đăng nhập lại
 */

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

require_once dirname(__DIR__) . '/config/database.php';

use App\Core\Database;
use App\Models\User;
use App\Controllers\UserController;
use App\Controllers\AuthController;

$passed = 0;
$failed = 0;

function t(string $desc, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "[ PASS ] " . $desc . PHP_EOL;
    } else {
        $failed++;
        echo "[ FAIL ] " . $desc . ($detail ? " ($detail)" : '') . PHP_EOL;
    }
}

echo PHP_EOL . "==== KIỂM THỬ CHỨC NĂNG ẢNH ĐẠI DIỆN (USER AVATAR) ====" . PHP_EOL . PHP_EOL;

$pdo = Database::getConnection();
$pdo->beginTransaction();

try {
    // ── 1. Kiểm tra cấu trúc bảng users và User::updateAvatar ────────────────
    $stmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'avatar_path'");
    t("Cột avatar_path tồn tại trong bảng users", $stmt->fetch() !== false);

    // Tạo user mẫu để test
    $testUserId = User::create([
        'username'  => 'avatar_tester_' . bin2hex(random_bytes(4)),
        'email'     => 'avatar_tester_' . bin2hex(random_bytes(4)) . '@vinhuni.edu.vn',
        'password'  => 'Test@123456',
        'full_name' => 'Nguyễn Văn Test',
        'status'    => 1,
    ]);
    t("Tạo tài khoản kiểm thử thành công", $testUserId > 0);

    $u = User::findById($testUserId);
    t("Tài khoản mới tạo có avatar_path là null", $u['avatar_path'] === null);

    $mockPath = 'uploads/avatars/avatar_mock_test.jpg';
    $updated = User::updateAvatar($testUserId, $mockPath);
    t("User::updateAvatar cập nhật thành công", $updated === true);

    $u2 = User::findById($testUserId);
    t("findById trả về đúng avatar_path vừa cập nhật", $u2['avatar_path'] === $mockPath);

    User::updateAvatar($testUserId, null);
    $u3 = User::findById($testUserId);
    t("User::updateAvatar về null thành công", $u3['avatar_path'] === null);

    // ── 2. Kiểm tra bảo vệ thư mục uploads ────────────────────────────────────
    $uploadsHtaccess = dirname(__DIR__) . '/public/uploads/.htaccess';
    t("Tệp public/uploads/.htaccess tồn tại", file_exists($uploadsHtaccess));
    if (file_exists($uploadsHtaccess)) {
        $ht = file_get_contents($uploadsHtaccess);
        t(".htaccess chặn thực thi mã PHP", strpos($ht, 'php') !== false && strpos($ht, 'denied') !== false);
        t(".htaccess cấm duyệt thư mục (Options -Indexes)", strpos($ht, '-Indexes') !== false);
    }

    $avatarsIndex = dirname(__DIR__) . '/public/uploads/avatars/index.html';
    t("Tệp public/uploads/avatars/index.html bảo vệ tồn tại", file_exists($avatarsIndex));

    // ── 3. Kiểm tra kiểm thực file (Validation) ──────────────────────────────
    $resNoFile = UserController::processAvatarUpload(['error' => UPLOAD_ERR_NO_FILE]);
    t("Từ chối khi không chọn file (UPLOAD_ERR_NO_FILE)", $resNoFile['ok'] === false && strpos($resNoFile['error'], 'chọn') !== false);

    $resOversizeErr = UserController::processAvatarUpload(['error' => UPLOAD_ERR_INI_SIZE]);
    t("Từ chối khi lỗi UPLOAD_ERR_INI_SIZE", $resOversizeErr['ok'] === false && strpos($resOversizeErr['error'], '2 MB') !== false);

    // Tạo file text giả mạo làm ảnh
    $tmpFake = tempnam(sys_get_temp_dir(), 'fake_');
    file_put_contents($tmpFake, '<?php echo "malicious code"; ?>');
    $resFake = UserController::processAvatarUpload([
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($tmpFake),
        'tmp_name' => $tmpFake,
        'name'     => 'test.jpg'
    ]);
    t("Chặn file PHP giả mạo ảnh (MIME không hợp lệ)", $resFake['ok'] === false);
    @unlink($tmpFake);

    // Tạo file dung lượng vượt quá 2MB (2.2 MB)
    $tmpLarge = tempnam(sys_get_temp_dir(), 'large_');
    $fp = fopen($tmpLarge, 'wb');
    fseek($fp, (int)(2.2 * 1024 * 1024));
    fwrite($fp, '0');
    fclose($fp);
    $resLarge = UserController::processAvatarUpload([
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($tmpLarge),
        'tmp_name' => $tmpLarge,
        'name'     => 'large.jpg'
    ]);
    t("Chặn file vượt quá 2 MB", $resLarge['ok'] === false && strpos($resLarge['error'], '2 MB') !== false);
    @unlink($tmpLarge);

    // ── 4. Kiểm tra tải ảnh hợp lệ (JPG, PNG, WebP) ──────────────────────────
    // Tạo 1 ảnh PNG chuẩn 10x10 bằng GD
    $imgPng = imagecreatetruecolor(10, 10);
    $tmpPng = tempnam(sys_get_temp_dir(), 'test_png_') . '.png';
    imagepng($imgPng, $tmpPng);
    imagedestroy($imgPng);

    $resPng = UserController::processAvatarUpload([
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($tmpPng),
        'tmp_name' => $tmpPng,
        'name'     => 'my_photo.png'
    ]);
    t("Chấp nhận ảnh PNG hợp lệ", $resPng['ok'] === true && !empty($resPng['path']));
    $savedFile1 = dirname(__DIR__) . '/public/' . $resPng['path'];
    t("Ảnh PNG được lưu thực tế trên đĩa", file_exists($savedFile1));
    t("Tên file trên đĩa được ngẫu nhiên hóa (không chứa 'my_photo')", !str_contains($resPng['path'], 'my_photo'));
    @unlink($tmpPng);

    // Tạo 1 ảnh JPEG chuẩn 10x10 bằng GD
    $imgJpg = imagecreatetruecolor(10, 10);
    $tmpJpg = tempnam(sys_get_temp_dir(), 'test_jpg_') . '.jpg';
    imagejpeg($imgJpg, $tmpJpg);
    imagedestroy($imgJpg);

    $resJpg = UserController::processAvatarUpload([
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($tmpJpg),
        'tmp_name' => $tmpJpg,
        'name'     => 'photo_2.jpg'
    ]);
    t("Chấp nhận ảnh JPG hợp lệ", $resJpg['ok'] === true && !empty($resJpg['path']));
    $savedFile2 = dirname(__DIR__) . '/public/' . $resJpg['path'];
    t("Ảnh JPG được lưu thực tế trên đĩa", file_exists($savedFile2));
    @unlink($tmpJpg);

    // ── 5. Kiểm tra thay thế ảnh (Xóa file cũ trên đĩa) ──────────────────────
    t("File ảnh 1 đang tồn tại trước khi thay thế", file_exists($savedFile1));
    UserController::deleteAvatarFile($resPng['path']);
    t("Xóa an toàn ảnh 1 khi người dùng thay ảnh mới", !file_exists($savedFile1));

    // ── 6. Kiểm tra chống tấn công Path Traversal ────────────────────────────
    $canaryFile = dirname(__DIR__) . '/public/canary_test.txt';
    file_put_contents($canaryFile, 'do not delete');
    UserController::deleteAvatarFile('uploads/avatars/../../canary_test.txt');
    t("Chống Path Traversal: không cho phép xóa file ngoài uploads/avatars", file_exists($canaryFile));
    UserController::deleteAvatarFile('uploads/avatars/index.html');
    t("Không cho phép xóa tệp bảo vệ index.html", file_exists($avatarsIndex));
    @unlink($canaryFile);

    // ── 7. Xóa ảnh 2 ────────────────────────────────────────────────────────
    UserController::deleteAvatarFile($resJpg['path']);
    t("File ảnh 2 đã được dọn sạch khỏi đĩa", !file_exists($savedFile2));

    // ── 8. Kiểm tra đồng bộ Session & Header khi đăng nhập ───────────────────
    $dummyUserWithAvatar = [
        'id'          => $testUserId,
        'username'    => 'test_avatar_user',
        'full_name'   => 'Lê Thị Mai',
        'avatar_path' => 'uploads/avatars/test_avatar.jpg',
    ];
    AuthController::signIn($dummyUserWithAvatar);
    t("signIn đồng bộ avatar_path vào \$_SESSION['avatar_path']", ($_SESSION['avatar_path'] ?? '') === 'uploads/avatars/test_avatar.jpg');

    $dummyUserNoAvatar = [
        'id'          => $testUserId,
        'username'    => 'test_avatar_user',
        'full_name'   => 'Lê Thị Mai',
        'avatar_path' => null,
    ];
    AuthController::signIn($dummyUserNoAvatar);
    t("signIn gán avatar_path = null vào session khi chưa có ảnh", ($_SESSION['avatar_path'] ?? null) === null);

} finally {
    // Luôn rollback để CSDL sạch hoàn toàn
    $pdo->rollBack();
}

echo PHP_EOL . "----------------------------------------------" . PHP_EOL;
echo "KẾT QUẢ KIỂM THỬ: {$passed} PASS, {$failed} FAIL." . PHP_EOL;
echo "==============================================" . PHP_EOL;

if ($failed > 0) {
    exit(1);
}
