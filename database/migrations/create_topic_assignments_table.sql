-- ==============================================================================
-- MIGRATION: TẠO BẢNG KẾT QUẢ PHÂN CÔNG ĐỀ TÀI VÀ GIẢNG VIÊN (topic_assignments)
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC THIẾT KẾ & AN TOÀN DỮ LIỆU:
-- 1. Tuyệt đối KHÔNG chứa DROP DATABASE, DROP TABLE hay TRUNCATE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS), an toàn khi chạy lại nhiều lần.
-- 3. Ràng buộc toàn vẹn khóa ngoại:
--    - period_id   -> internship_periods(id)
--    - student_id  -> users(id)
--    - topic_id    -> topics(id)
--    - lecturer_id -> users(id)
--    - assigned_by -> users(id)
-- 4. Ràng buộc duy nhất: Mỗi sinh viên chỉ được phân công duy nhất 1 đề tài trong cùng một đợt:
--    - UNIQUE KEY `uk_period_student` (`period_id`, `student_id`)
-- 5. Quy tắc trạng thái phân công:
--    - draft     : Bản dự thảo do thuật toán tự động hoặc cán bộ phân công (chưa công bố cho SV/GV)
--    - published : Kết quả chính thức đã được phê duyệt và công bố công khai
--    - cancelled : Phân công bị hủy hoặc điều chỉnh sang đề tài khác
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `topic_assignments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Đợt thực tập / đồ án (internship_periods.id)',
    `student_id` INT UNSIGNED NOT NULL COMMENT 'Sinh viên được phân công (users.id)',
    `topic_id` INT UNSIGNED NOT NULL COMMENT 'Đề tài được phân công (topics.id)',
    `lecturer_id` INT UNSIGNED NOT NULL COMMENT 'Giảng viên hướng dẫn (users.id)',
    `matched_wish_priority` TINYINT UNSIGNED NULL COMMENT 'Thứ tự nguyện vọng trúng tuyển: 1, 2, 3 hoặc NULL nếu phân công chỉ định/thủ công',
    `assignment_method` ENUM('auto', 'manual') NOT NULL DEFAULT 'auto' COMMENT 'Phương thức phân công: auto (thuật toán tự động), manual (cán bộ phân công thủ công)',
    `status` ENUM('draft', 'published', 'cancelled') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái phân công: draft (dự thảo), published (đã công bố), cancelled (hủy)',
    `assigned_by` INT UNSIGNED NULL COMMENT 'Người thực hiện / duyệt phân công (users.id, NULL nếu tự động)',
    `notes` VARCHAR(255) NULL COMMENT 'Ghi chú phân công / lý do điều chỉnh',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm phân công',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',

    UNIQUE KEY `uk_period_student` (`period_id`, `student_id`),

    INDEX `idx_topic_assignments_period` (`period_id`),
    INDEX `idx_topic_assignments_student` (`student_id`),
    INDEX `idx_topic_assignments_topic` (`topic_id`),
    INDEX `idx_topic_assignments_lecturer` (`lecturer_id`),
    INDEX `idx_topic_assignments_status` (`status`),
    INDEX `idx_topic_assignments_method` (`assignment_method`),
    INDEX `idx_topic_assignments_priority` (`matched_wish_priority`),

    CONSTRAINT `fk_topic_assignments_period` FOREIGN KEY (`period_id`)
        REFERENCES `internship_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_assignments_student` FOREIGN KEY (`student_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_assignments_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `topics` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_assignments_lecturer` FOREIGN KEY (`lecturer_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_assignments_assigned_by` FOREIGN KEY (`assigned_by`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kết quả phân công đề tài và giảng viên hướng dẫn';
