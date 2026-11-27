<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * ReportUploadService - Dịch vụ xử lý và xác thực tải lên tệp tin báo cáo thực tập
 *
 * Tiêu chuẩn kỹ thuật & Bảo mật:
 * - Cho phép các định dạng: PDF (.pdf), Word (.doc, .docx), Tệp nén (.zip)
 * - Chặn tuyệt đối các tệp nguy hiểm thực thi mã nguồn: .php, .exe, .sh, .bat, .js, .phtml...
 * - Giới hạn dung lượng tối đa: 20MB (20 * 1024 * 1024 bytes).
 * - Lưu trữ file với tên được chuẩn hóa an toàn, phân tách theo thư mục đợt và mốc tiến độ.
 */
class ReportUploadService
{
    /** Kích thước file tối đa: 20MB */
    public const MAX_FILE_SIZE = 20971520; // 20 * 1024 * 1024 bytes

    /** Các phần mở rộng tệp tin được phép */
    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'zip'];

    /** Các định dạng MIME hợp lệ */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream', // Một số trình duyệt gửi cho zip
    ];

    /**
     * Xác thực thông tin tệp tải lên ($_FILES['report_file'])
     *
     * @param array $file Mảng $_FILES['...']
     * @return array Danh sách lỗi (rỗng nếu hợp lệ)
     */
    public static function validateFile(array $file): array
    {
        $errors = [];

        // 1. Kiểm tra có chọn file hay không
        if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['file'] = 'Vui lòng chọn tệp tin báo cáo để tải lên.';
            return $errors;
        }

        // 2. Kiểm tra mã lỗi upload của PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['file'] = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Tệp tin vượt quá dung lượng cho phép của máy chủ.',
                UPLOAD_ERR_PARTIAL                       => 'Tệp tin chỉ mới được tải lên một phần, vui lòng thử lại.',
                default                                  => 'Lỗi trong quá trình tải tệp tin lên máy chủ (Mã: ' . $file['error'] . ').',
            };
            return $errors;
        }

        // 3. Kiểm tra dung lượng tệp
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            $errors['file'] = 'Tệp tin rỗng (dung lượng 0 bytes).';
        } elseif ($size > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / (1024 * 1024);
            $errors['file'] = "Dung lượng tệp tin ({$size} bytes) vượt quá giới hạn cho phép ({$maxMb}MB).";
        }

        // 4. Kiểm tra phần mở rộng file (Extension)
        $origName = $file['name'] ?? '';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $allowedList = implode(', ', self::ALLOWED_EXTENSIONS);
            $errors['file'] = "Định dạng tệp không được hỗ trợ (.{$ext}). Hệ thống chỉ chấp nhận: {$allowedList}.";
            return $errors;
        }

        // 5. Kiểm tra định dạng MIME thực tế (nếu tệp tạm tồn tại)
        if (!empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            // Kiểm tra tệp thực thi ngụy trang (vd: file.php đổi tên thành file.pdf)
            $dangerousMimes = ['text/x-php', 'application/x-httpd-php', 'application/x-dosexec', 'application/x-sh'];
            if (in_array($detectedMime, $dangerousMimes, true)) {
                $errors['file'] = 'Phát hiện tệp tin chứa mã độc hoặc tập lệnh thực thi nguy hiểm.';
            }
        }

        return $errors;
    }

    /**
     * Chuẩn hóa và lưu trữ tệp tin vào thư mục lưu trữ an toàn
     */
    public static function processAndStore(
        array $file,
        int $periodId,
        int $studentId,
        int $milestoneId,
        int $version = 1,
        ?string $uploadBaseDir = null
    ): array {
        $errors = self::validateFile($file);
        if (!empty($errors)) {
            throw new InvalidArgumentException(reset($errors));
        }

        $origName = $file['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size     = (int)$file['size'];

        // MIME
        $mime = $file['type'] ?? 'application/octet-stream';
        if (!empty($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detected = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if ($detected) {
                $mime = $detected;
            }
        }

        $baseDir = $uploadBaseDir ?? (dirname(__DIR__, 2) . '/public/uploads/reports');
        $relativeDir = "period_{$periodId}/student_{$studentId}";
        $targetDir = "{$baseDir}/{$relativeDir}";

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException("Không thể khởi tạo thư mục lưu trữ: {$targetDir}");
        }

        // Tên file an toàn: ms_{milestoneId}_v{version}_{timestamp}_{random}.{ext}
        $random = bin2hex(random_bytes(4));
        $safeFileName = "ms{$milestoneId}_v{$version}_" . time() . "_{$random}.{$ext}";
        $destination = "{$targetDir}/{$safeFileName}";

        // Nếu là tệp thật từ upload thì di chuyển, nếu tệp test giả lập thì copy
        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new \RuntimeException("Lỗi di chuyển tệp tải lên vào thư mục đích.");
            }
        } else {
            if (!copy($file['tmp_name'], $destination)) {
                throw new \RuntimeException("Lỗi sao lưu tệp tin kiểm thử.");
            }
        }

        $relativePath = "uploads/reports/{$relativeDir}/{$safeFileName}";

        return [
            'file_name' => $origName,
            'file_path' => $relativePath,
            'file_size' => $size,
            'file_mime' => $mime,
            'file_ext'  => $ext,
        ];
    }
}
