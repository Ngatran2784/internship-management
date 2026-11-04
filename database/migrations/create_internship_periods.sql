-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- MIGRATION: TẠO BẢNG QUẢN LÝ ĐỢT THỰC TẬP VÀ ĐỒ ÁN (internship_periods)
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN DỮ LIỆU:
-- 1. Tuyệt đối KHÔNG chứa DROP TABLE, TRUNCATE hay DROP DATABASE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS).
-- 3. Có thể thực thi nhiều lần (idempotent) mà không gây lỗi hoặc mất dữ liệu cũ.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE TABLE IF NOT EXISTS `internship_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `code` VARCHAR(50) NOT NULL COMMENT 'Mã đợt định danh duy nhất (vd: HK1-2026-TT, HK2-2026-DA)',
    `name` VARCHAR(150) NOT NULL COMMENT 'Tên hiển thị của đợt thực tập / đồ án',
    `type` ENUM('internship', 'graduation_project') NOT NULL COMMENT 'Loại đợt: internship (Thực tập) hoặc graduation_project (Đồ án tốt nghiệp)',
    `start_date` DATE NOT NULL COMMENT 'Ngày bắt đầu đợt (YYYY-MM-DD)',
    `end_date` DATE NOT NULL COMMENT 'Ngày kết thúc đợt (YYYY-MM-DD)',
    `status` ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái đợt: draft (Nháp/Chưa mở), active (Đang diễn ra), closed (Đã kết thúc/Đóng)',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết, quy định hoặc thông báo cho đợt',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo bản ghi',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
    
    -- Ràng buộc tính duy nhất của mã đợt
    UNIQUE KEY `uk_internship_periods_code` (`code`),
    
    -- Chỉ mục tối ưu tốc độ tìm kiếm và lọc dữ liệu
    INDEX `idx_internship_periods_status` (`status`),
    INDEX `idx_internship_periods_type` (`type`),
    INDEX `idx_internship_periods_dates` (`start_date`, `end_date`),
    
    -- Ràng buộc logic nghiệp vụ: Ngày kết thúc không được trước ngày bắt đầu
    CONSTRAINT `chk_period_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý danh mục các đợt thực tập và đồ án tốt nghiệp sinh viên';
