-- ==============================================================================
-- MIGRATION: TẠO BẢNG LỊCH SỬ XÉT DUYỆT ĐỀ TÀI (topic_approvals)
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN DỮ LIỆU:
-- 1. Không chứa DROP TABLE, TRUNCATE hay DROP DATABASE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS), chạy nhiều lần không lỗi.
-- 3. Không sửa bảng hiện có. Chỉ thêm bảng mới tham chiếu `topics` và `users`.
-- ĐIỀU KIỆN TIÊN QUYẾT: đã có bảng `topics` (create_topics_table.sql) và `users`.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `topic_approvals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `topic_id` INT UNSIGNED NOT NULL COMMENT 'Đề tài được xét duyệt (topics.id)',
    `reviewer_id` INT UNSIGNED NOT NULL COMMENT 'Người xử lý duyệt/từ chối (users.id)',
    `decision` ENUM('approved', 'rejected') NOT NULL COMMENT 'Quyết định: approved (Duyệt) hoặc rejected (Từ chối)',
    `comments` TEXT NULL COMMENT 'Nhận xét, bắt buộc có lý do khi từ chối',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm xử lý',

    INDEX `idx_topic_approvals_topic_id` (`topic_id`),
    INDEX `idx_topic_approvals_reviewer_id` (`reviewer_id`),
    INDEX `idx_topic_approvals_decision` (`decision`),
    INDEX `idx_topic_approvals_created_at` (`created_at`),

    CONSTRAINT `fk_topic_approvals_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `topics` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topic_approvals_reviewer` FOREIGN KEY (`reviewer_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lịch sử xét duyệt đề tài';
