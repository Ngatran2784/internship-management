-- ==============================================================================
-- MIGRATION: TẠO BẢNG ĐĂNG KÝ NGUYỆN VỌNG ĐỀ TÀI (topic_wishes)
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN DỮ LIỆU:
-- 1. Không chứa DROP TABLE, TRUNCATE hay DROP DATABASE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS), chạy nhiều lần không lỗi.
-- 3. Ràng buộc toàn vẹn: Khóa ngoại tới users(id), topics(id), internship_periods(id).
-- 4. Ràng buộc duy nhất: Một sinh viên không đăng ký trùng đề tài hoặc trùng thứ tự NV trong cùng một đợt.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `topic_wishes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `student_id` INT UNSIGNED NOT NULL COMMENT 'Sinh viên đăng ký (users.id)',
    `topic_id` INT UNSIGNED NOT NULL COMMENT 'Đề tài đăng ký (topics.id)',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Đợt thực tập / đồ án (internship_periods.id)',
    `priority` TINYINT UNSIGNED NOT NULL COMMENT 'Thứ tự ưu tiên nguyện vọng: 1, 2, 3',
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái nguyện vọng',
    `note` VARCHAR(255) NULL COMMENT 'Ghi chú của sinh viên',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm đăng ký',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật',

    UNIQUE KEY `uk_student_period_topic` (`student_id`, `period_id`, `topic_id`),
    UNIQUE KEY `uk_student_period_priority` (`student_id`, `period_id`, `priority`),

    INDEX `idx_topic_wishes_student_id` (`student_id`),
    INDEX `idx_topic_wishes_topic_id` (`topic_id`),
    INDEX `idx_topic_wishes_period_id` (`period_id`),
    INDEX `idx_topic_wishes_priority` (`priority`),
    INDEX `idx_topic_wishes_status` (`status`),

    CONSTRAINT `fk_topic_wishes_student` FOREIGN KEY (`student_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_wishes_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `topics` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_wishes_period` FOREIGN KEY (`period_id`)
        REFERENCES `internship_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Đăng ký nguyện vọng đề tài của sinh viên';
