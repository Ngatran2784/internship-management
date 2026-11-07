-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- MIGRATION: BỔ SUNG CỘT avatar_path VÀO BẢNG users
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN DỮ LIỆU:
-- 1. Tuyệt đối KHÔNG chứa DROP TABLE, TRUNCATE hay DROP DATABASE.
-- 2. Không xóa hoặc tạo lại bảng users.
-- 3. Bổ sung an toàn cột avatar_path để lưu đường dẫn ảnh đại diện người dùng.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Thêm cột avatar_path nếu chưa tồn tại
ALTER TABLE `users` 
    ADD COLUMN `avatar_path` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện người dùng' AFTER `phone`;
