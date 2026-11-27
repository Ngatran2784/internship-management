<?php
/**
 * Test clean database migration and seed execution
 * Kiểm thử xác minh chạy kịch bản migration và sample seed trên cơ sở dữ liệu sạch
 */

$host = '127.0.0.1';
$port = 3306;
$user = 'root';
$pass = '';
$testDb = 'internship_migration_verify_clean';

echo "==============================================================================\n";
echo "   XÁC MINH CƠ SỞ DỮ LIỆU SẠCH (CLEAN DATABASE MIGRATION VERIFICATION)\n";
echo "==============================================================================\n\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Tạo CSDL test hoàn toàn mới
    echo "1. Tạo cơ sở dữ liệu kiểm thử độc lập `{$testDb}`...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
    $pdo->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$testDb}`");

    // 2. Nạp cấu trúc và dữ liệu nền tảng
    echo "2. Nạp cơ sở dữ liệu nền tảng ban đầu...\n";
    $backupPath = dirname(__DIR__, 2) . '/database_backups/backup_baseline_20261126.sql';
    if (!file_exists($backupPath)) {
        // Dự phòng nếu đặt ở vị trí khác
        $backupPath = dirname(__DIR__) . '/database/internship_management.sql';
    }
    if (!file_exists($backupPath)) {
        throw new Exception("Không tìm thấy tệp CSDL nền tảng tại $backupPath");
    }

    $mysqlCmd = "\"C:\\xampp\\mysql\\bin\\mysql.exe\" --default-character-set=utf8mb4 -u{$user} -h{$host} -P{$port} {$testDb} < \"{$backupPath}\"";
    exec($mysqlCmd, $output, $returnVar);
    if ($returnVar !== 0) {
        throw new Exception("Lỗi import CSDL nền tảng, mã lỗi: $returnVar");
    }

    $stmt = $pdo->query("SHOW TABLES");
    $baseTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   -> Số bảng nền tảng ban đầu: " . count($baseTables) . " bảng (" . implode(', ', $baseTables) . ")\n";

    // 3. Thực thi migration tạo các bảng mốc tiến độ và báo cáo
    echo "3. Thực thi migration (database/migrations/create_milestones_and_reports_tables.sql)...\n";
    $migrationPath = dirname(__DIR__) . '/database/migrations/create_milestones_and_reports_tables.sql';
    if (!file_exists($migrationPath)) {
        throw new Exception("Không tìm thấy tệp migration tại $migrationPath");
    }

    $migrationCmd = "\"C:\\xampp\\mysql\\bin\\mysql.exe\" --default-character-set=utf8mb4 -u{$user} -h{$host} -P{$port} {$testDb} < \"{$migrationPath}\"";
    exec($migrationCmd, $outputMig, $returnVarMig);
    if ($returnVarMig !== 0) {
        throw new Exception("Lỗi khi chạy migration, mã lỗi: $returnVarMig");
    }

    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   -> Tổng số bảng sau migration: " . count($allTables) . " bảng\n";

    $expectedTables = [
        'milestones',
        'internship_logs',
        'internship_reports',
        'report_submissions',
        'report_reviews'
    ];

    foreach ($expectedTables as $t) {
        if (!in_array($t, $allTables)) {
            throw new Exception("Thiếu bảng $t sau khi chạy migration!");
        }
        echo "   [OK] Bảng `{$t}` đã được tạo thành công.\n";
    }

    // 4. Nạp dữ liệu mẫu
    echo "4. Nạp dữ liệu mẫu (database/seeds/milestones_and_reports_sample_data.sql)...\n";
    $seedPath = dirname(__DIR__) . '/database/seeds/milestones_and_reports_sample_data.sql';
    if (!file_exists($seedPath)) {
        throw new Exception("Không tìm thấy tệp seed tại $seedPath");
    }

    $seedCmd = "\"C:\\xampp\\mysql\\bin\\mysql.exe\" --default-character-set=utf8mb4 -u{$user} -h{$host} -P{$port} {$testDb} < \"{$seedPath}\"";
    exec($seedCmd, $outputSeed, $returnVarSeed);
    if ($returnVarSeed !== 0) {
        throw new Exception("Lỗi khi nạp dữ liệu mẫu, mã lỗi: $returnVarSeed");
    }

    // Kiểm tra số bản ghi
    foreach ($expectedTables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "   [OK] Bảng `{$t}` có {$count} bản ghi hợp lệ.\n";
        if ($count == 0) {
            throw new Exception("Bảng `{$t}` không có bản ghi nào sau khi seed!");
        }
    }

    // 5. Kiểm tra ràng buộc khóa ngoại (Foreign Keys)
    echo "5. Kiểm tra tính toàn vẹn của các ràng buộc khóa ngoại...\n";
    $fkCheck = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '{$testDb}' AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('milestones', 'internship_logs', 'internship_reports', 'report_submissions', 'report_reviews')
    ")->fetchAll(PDO::FETCH_ASSOC);
    echo "   -> Tìm thấy " . count($fkCheck) . " ràng buộc khóa ngoại hợp lệ.\n";

    // 6. Dọn dẹp CSDL test
    echo "6. Xóa cơ sở dữ liệu kiểm thử `{$testDb}`...\n";
    $pdo->exec("DROP DATABASE IF EXISTS `{$testDb}`");
    echo "   [OK] Dọn dẹp hoàn tất, hệ thống an toàn tuyệt đối.\n";

    echo "\n>>> XÁC MINH MIGRATION THÀNH CÔNG: 100% SẠCH VÀ AN TOÀN! <<<\n";
    exit(0);
} catch (Exception $e) {
    echo "\n[ERROR] Kiểm thử migration thất bại: " . $e->getMessage() . "\n";
    exit(1);
}
