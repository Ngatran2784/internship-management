-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- TÀI LIỆU CẤU TRÚC CƠ SỞ DỮ LIỆU (DATABASE SCHEMA) - SPRINT 1
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- LƯU Ý BẢO MẬT VÀ AN TOÀN DỮ LIỆU:
-- 1. Script này KHÔNG chứa lệnh DROP DATABASE hay TRUNCATE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS).
-- 3. Script CHƯA được thực thi lên máy chủ MySQL khi chưa có sự phê duyệt của Trưởng nhóm/Người phụ trách.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1. BẢNG VAI TRÒ HỆ THỐNG (roles)
-- Chức năng: Lưu trữ danh mục các vai trò trong hệ thống:
--   - admin (Quản trị hệ thống)
--   - training_dept (Phòng đào tạo / Quản lý chung)
--   - department_head (Trưởng bộ môn / Khoa)
--   - lecturer (Giảng viên hướng dẫn / phản biện)
--   - student (Sinh viên)
--   - enterprise (Doanh nghiệp / Người hướng dẫn thực tập tại doanh nghiệp)
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên định danh vai trò (mã code)',
    `display_name` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị tiếng Việt',
    `description` VARCHAR(255) NULL COMMENT 'Mô tả phạm vi quyền hạn của vai trò',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục vai trò người dùng trong hệ thống';

-- 2. BẢNG NGƯỜI DÙNG HỆ THỐNG (users)
-- Chức năng: Lưu trữ thông tin tài khoản đăng nhập và hồ sơ cơ bản của người dùng
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập / Mã sinh viên / Mã giảng viên',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email liên hệ chính thức',
    `password` VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã được mã hóa Bcrypt an toàn',
    `full_name` VARCHAR(100) NOT NULL COMMENT 'Họ và tên đầy đủ',
    `phone` VARCHAR(20) NULL COMMENT 'Số điện thoại liên lạc',
    `avatar` VARCHAR(255) NULL COMMENT 'Đường dẫn ảnh đại diện',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái tài khoản: 1 = Hoạt động, 0 = Bị khóa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách tài khoản người dùng hệ thống';

-- 3. BẢNG LIÊN KẾT NGƯỜI DÙNG - VAI TRÒ (user_roles)
-- Chức năng: Thiết lập quan hệ nhiều - nhiều (N - N) giữa người dùng và vai trò.
-- Một người có thể đồng thời giữ nhiều vai trò (ví dụ: Giảng viên kiêm Trưởng bộ môn).
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến users(id)',
    `role_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến roles(id)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm gán vai trò',
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết phân quyền đa vai trò cho người dùng';

-- 4. BẢNG QUẢN LÝ ĐỢT THỰC TẬP VÀ ĐỒ ÁN (internship_periods)
-- Chức năng: Lưu trữ danh mục các đợt thực tập và đợt đồ án tốt nghiệp
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
    UNIQUE KEY `uk_internship_periods_code` (`code`),
    INDEX `idx_internship_periods_status` (`status`),
    INDEX `idx_internship_periods_type` (`type`),
    INDEX `idx_internship_periods_dates` (`start_date`, `end_date`),
    CONSTRAINT `chk_period_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý danh mục các đợt thực tập và đồ án tốt nghiệp sinh viên';

-- ==============================================================================
-- ĐỊNH HƯỚNG CÁC PHÂN HỆ TIẾP THEO (DỰ KIẾN TRIỂN KHAI TỪ SPRINT 2+):
-- - periods (Đợt thực tập / Đồ án tốt nghiệp theo năm học, học kỳ)
-- - student_profiles (Thông tin chi tiết sinh viên: lớp, ngành, GPA, CV)
-- - lecturer_profiles (Thông tin giảng viên: chuyên môn, hướng nghiên cứu, quota)
-- - enterprise_profiles (Thông tin doanh nghiệp: địa chỉ, lĩnh vực, liên hệ)
-- - topics (Đề tài đồ án: tên, mô tả, mục tiêu, công nghệ, số lượng SV, duyệt đề tài)
-- - topic_wishes (Nguyện vọng đăng ký và xếp hạng đề tài của sinh viên)
-- - matchings (Kết quả ghép cặp sinh viên - đề tài qua thuật toán Gale-Shapley/Hungaria)
-- - milestones & progress_reports (Kế hoạch công việc, nhật ký thực tập, báo cáo đợt)
-- - rubrics & evaluations (Thư viện tiêu chí rubric và bảng chấm điểm)
-- - audit_logs (Nhật ký lưu vết thao tác hệ thống)
-- ==============================================================================
