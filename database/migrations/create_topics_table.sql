-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- MIGRATION: TẠO BẢNG QUẢN LÝ ĐỀ TÀI THỰC TẬP & ĐỒ ÁN (topics)
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN DỮ LIỆU:
-- 1. Tuyệt đối KHÔNG chứa DROP TABLE, TRUNCATE hay DROP DATABASE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS).
-- 3. Có thể thực thi nhiều lần (idempotent) mà không gây lỗi hoặc mất dữ liệu cũ.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `topics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `code` VARCHAR(50) NOT NULL COMMENT 'Mã đề tài định danh duy nhất (vd: DT-2026-001)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Tên hiển thị của đề tài',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết nội dung, mục tiêu, yêu cầu công nghệ của đề tài',
    `type` ENUM('internship', 'graduation_project') NOT NULL COMMENT 'Loại đề tài: internship (Thực tập) hoặc graduation_project (Đồ án tốt nghiệp)',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến đợt thực tập / đồ án (internship_periods.id)',
    `proposer_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến người đề xuất đề tài (users.id)',
    `max_students` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Số lượng sinh viên tối đa có thể thực hiện đề tài',
    `status` ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái đề tài: draft (Bản nháp), pending (Chờ duyệt), approved (Đã duyệt), rejected (Từ chối)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo đề tài',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
    
    -- Ràng buộc tính duy nhất của mã đề tài
    UNIQUE KEY `uk_topics_code` (`code`),
    
    -- Các chỉ mục tối ưu tìm kiếm và lọc dữ liệu
    INDEX `idx_topics_period_id` (`period_id`),
    INDEX `idx_topics_proposer_id` (`proposer_id`),
    INDEX `idx_topics_status` (`status`),
    INDEX `idx_topics_type` (`type`),
    INDEX `idx_topics_created_at` (`created_at`),
    
    -- Khóa ngoại liên kết toàn vẹn dữ liệu
    CONSTRAINT `fk_topics_period` FOREIGN KEY (`period_id`)
        REFERENCES `internship_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topics_proposer` FOREIGN KEY (`proposer_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý danh mục các đề tài thực tập và đồ án tốt nghiệp sinh viên';
