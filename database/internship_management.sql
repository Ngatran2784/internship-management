-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- TỆP CƠ SỞ DỮ LIỆU TỔNG HỢP TOÀN DIỆN (FULL DATABASE INITIALIZATION - SPRINT 3)
-- Đường dẫn: database/internship_management.sql
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- MỤC ĐÍCH SỬ DỤNG:
-- 1. Phục vụ khởi tạo toàn diện cấu trúc (DDL) và dữ liệu mẫu (Seed) cho môi trường mới.
-- 2. Thành viên nhóm sau khi clone dự án chỉ cần import tệp duy nhất này vào MySQL / phpMyAdmin.
-- 3. Bao gồm đầy đủ 7 bảng:
--    - roles: Danh mục 6 vai trò người dùng trong hệ thống
--    - users: Tài khoản người dùng và hồ sơ
--    - user_roles: Phân quyền vai trò người dùng (N - N)
--    - internship_periods: Đợt thực tập / đồ án tốt nghiệp
--    - topics: Danh mục đề tài thực tập và đồ án (Sprint 3)
--    - topic_approvals: Lịch sử xét duyệt / từ chối đề tài (Sprint 3)
--    - topic_wishes: Đăng ký nguyện vọng đề tài của sinh viên (Sprint 3)
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN VÀ IDEMPOTENT:
-- 1. Tuyệt đối KHÔNG chứa DROP DATABASE, DROP TABLE hay TRUNCATE.
-- 2. Chỉ tạo bảng nếu chưa tồn tại (CREATE TABLE IF NOT EXISTS).
-- 3. Chỉ nạp dữ liệu nếu chưa có bản ghi (WHERE NOT EXISTS / INSERT IGNORE).
-- 4. Sử dụng tra cứu động theo mã định danh duy nhất (code / username), không dùng ID cứng.
-- 5. Có thể thực thi nhiều lần mà không gây lỗi trùng lặp và không ghi đè dữ liệu cũ.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- PHẦN 1: KHỞI TẠO CẤU TRÚC BẢNG (TABLE SCHEMAS & CONSTRAINTS)
-- ==============================================================================

-- 1. BẢNG VAI TRÒ HỆ THỐNG (roles)
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên định danh vai trò (mã code)',
    `display_name` VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị tiếng Việt',
    `description` VARCHAR(255) NULL COMMENT 'Mô tả phạm vi quyền hạn của vai trò',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh mục vai trò người dùng trong hệ thống';

-- 2. BẢNG NGƯỜI DÙNG HỆ THỐNG (users)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tên đăng nhập / Mã sinh viên / Mã giảng viên',
    `email` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email liên hệ chính thức',
    `password` VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã được mã hóa Bcrypt an toàn',
    `full_name` VARCHAR(100) NOT NULL COMMENT 'Họ và tên đầy đủ',
    `phone` VARCHAR(20) NULL COMMENT 'Số điện thoại liên lạc',
    `avatar_path` VARCHAR(255) NULL COMMENT 'Đường dẫn ảnh đại diện chuẩn hóa',
    `avatar` VARCHAR(255) NULL COMMENT 'Đường dẫn ảnh đại diện (kế thừa cũ)',
    `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Trạng thái tài khoản: 1 = Hoạt động, 0 = Bị khóa',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Danh sách tài khoản người dùng hệ thống';

-- 3. BẢNG PHÂN QUYỀN NGƯỜI DÙNG - VAI TRÒ (user_roles)
CREATE TABLE IF NOT EXISTS `user_roles` (
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến users(id)',
    `role_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến roles(id)',
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm gán vai trò',
    PRIMARY KEY (`user_id`, `role_id`),
    INDEX `fk_user_roles_role` (`role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`)
        REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng liên kết phân quyền đa vai trò cho người dùng';

-- 4. BẢNG ĐỢT THỰC TẬP & ĐỒ ÁN (internship_periods)
CREATE TABLE IF NOT EXISTS `internship_periods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã đợt định danh duy nhất (vd: HK1-2026-TT, HK2-2026-DA)',
    `name` VARCHAR(150) NOT NULL COMMENT 'Tên hiển thị của đợt thực tập / đồ án',
    `type` ENUM('internship', 'graduation_project') NOT NULL COMMENT 'Loại đợt: internship (Thực tập) hoặc graduation_project (Đồ án tốt nghiệp)',
    `start_date` DATE NOT NULL COMMENT 'Ngày bắt đầu đợt (YYYY-MM-DD)',
    `end_date` DATE NOT NULL COMMENT 'Ngày kết thúc đợt (YYYY-MM-DD)',
    `status` ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái đợt: draft (Nháp/Chưa mở), active (Đang diễn ra), closed (Đã kết thúc/Đóng)',
    `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết, quy định hoặc thông báo cho đợt',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo bản ghi',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
    INDEX `idx_internship_periods_status` (`status`),
    INDEX `idx_internship_periods_type` (`type`),
    INDEX `idx_internship_periods_dates` (`start_date`, `end_date`),
    CONSTRAINT `chk_period_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý danh mục các đợt thực tập và đồ án tốt nghiệp sinh viên';

-- 5. BẢNG ĐỀ TÀI THỰC TẬP & ĐỒ ÁN (topics)
CREATE TABLE IF NOT EXISTS `topics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Mã đề tài định danh duy nhất (vd: DT-2026-001)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Tên hiển thị của đề tài',
    `description` TEXT DEFAULT NULL COMMENT 'Mô tả chi tiết nội dung, mục tiêu, yêu cầu công nghệ của đề tài',
    `type` ENUM('internship', 'graduation_project') NOT NULL COMMENT 'Loại đề tài: internship (Thực tập) hoặc graduation_project (Đồ án tốt nghiệp)',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến đợt thực tập / đồ án (internship_periods.id)',
    `proposer_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến người đề xuất đề tài (users.id)',
    `max_students` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Số lượng sinh viên tối đa có thể thực hiện đề tài',
    `status` ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái đề tài: draft (Bản nháp), pending (Chờ duyệt), approved (Đã duyệt), rejected (Từ chối)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo đề tài',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
    INDEX `idx_topics_period_id` (`period_id`),
    INDEX `idx_topics_proposer_id` (`proposer_id`),
    INDEX `idx_topics_status` (`status`),
    INDEX `idx_topics_type` (`type`),
    INDEX `idx_topics_created_at` (`created_at`),
    CONSTRAINT `fk_topics_period` FOREIGN KEY (`period_id`)
        REFERENCES `internship_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topics_proposer` FOREIGN KEY (`proposer_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Quản lý danh mục các đề tài thực tập và đồ án tốt nghiệp sinh viên';

-- 6. BẢNG LỊCH SỬ XÉT DUYỆT ĐỀ TÀI (topic_approvals)
CREATE TABLE IF NOT EXISTS `topic_approvals` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `topic_id` INT UNSIGNED NOT NULL COMMENT 'Đề tài được xét duyệt (topics.id)',
    `reviewer_id` INT UNSIGNED NOT NULL COMMENT 'Người xử lý duyệt/từ chối (users.id)',
    `decision` ENUM('approved', 'rejected') NOT NULL COMMENT 'Quyết định: approved (Duyệt) hoặc rejected (Từ chối)',
    `comments` TEXT NULL COMMENT 'Nhận xét hoặc lý do khi từ chối',
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

-- 7. BẢNG ĐĂNG KÝ NGUYỆN VỌNG ĐỀ TÀI (topic_wishes)
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

-- ==============================================================================
-- PHẦN 2: DỮ LIỆU KHỞI TẠO VÀ DỮ LIỆU MẪU (SEED DATA - IDEMPOTENT)
-- ==============================================================================

-- 1. NẠP 6 VAI TRÒ HỆ THỐNG
INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'admin', 'Quản trị viên', 'Toàn quyền cấu hình và quản trị hệ thống', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'admin');

INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'training_dept', 'Phòng đào tạo', 'Quản lý các đợt thực tập, phân công và kế hoạch chung', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'training_dept');

INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'department_head', 'Trưởng bộ môn', 'Xét duyệt đề tài, phê duyệt hội đồng đánh giá', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'department_head');

INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'lecturer', 'Giảng viên', 'Đề xuất đề tài, hướng dẫn và chấm điểm sinh viên thực tập', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'lecturer');

INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'student', 'Sinh viên', 'Đăng ký nguyện vọng đề tài, nộp báo cáo và nhật ký thực tập', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'student');

INSERT INTO `roles` (`name`, `display_name`, `description`, `created_at`, `updated_at`)
SELECT 'company', 'Doanh nghiệp', 'Đề xuất chỉ tiêu tiếp nhận thực tập và đánh giá tại doanh nghiệp', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'company');

-- 2. NẠP 2 TÀI KHOẢN MẪU
-- Mật khẩu tài khoản admin: Admin@123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `status`, `created_at`, `updated_at`)
SELECT 'admin', 'admin@vhu.edu.vn', '$2y$10$wO3VDmS6f0cT6r94bLreMe7P1oYxK0I0xPZ5vM07N03z2iI9o29uC', 'Quản Trị Viên Hệ Thống', '0901234567', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'admin');

-- Mật khẩu tài khoản sv001: Sinhvien@123
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `phone`, `status`, `created_at`, `updated_at`)
SELECT 'sv001', 'sv001@sinhvien.vhu.edu.vn', '$2y$10$tZc4rXvWzR8n1d2s3a4e5u6f7g8h9i0j1k2l3m4n5o6p7q8r9s0t.', 'Nguyễn Văn An', '0912345678', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `username` = 'sv001');

-- 3. GÁN VAI TRÒ CHO TÀI KHOẢN MẪU
INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`)
SELECT u.id, r.id, NOW()
FROM `users` u
CROSS JOIN `roles` r
WHERE u.username = 'admin'
  AND r.name = 'admin'
  AND NOT EXISTS (
      SELECT 1 FROM `user_roles` ur 
      WHERE ur.user_id = u.id AND ur.role_id = r.id
  );

INSERT INTO `user_roles` (`user_id`, `role_id`, `assigned_at`)
SELECT u.id, r.id, NOW()
FROM `users` u
CROSS JOIN `roles` r
WHERE u.username = 'sv001'
  AND r.name = 'student'
  AND NOT EXISTS (
      SELECT 1 FROM `user_roles` ur 
      WHERE ur.user_id = u.id AND ur.role_id = r.id
  );

-- 4. NẠP 2 ĐỢT THỰC TẬP & ĐỒ ÁN MẪU
-- Đợt 1: Thực tập - Đang diễn ra (active)
INSERT INTO `internship_periods` (`code`, `name`, `type`, `description`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
SELECT 
    'HK1-2026-TT',
    'Thực tập Tốt nghiệp Doanh nghiệp Học kỳ 1 Năm 2026',
    'internship',
    'Đợt thực tập dành cho sinh viên khóa 62 Khoa Công nghệ Thông tin tại các doanh nghiệp đối tác.',
    DATE_SUB(CURDATE(), INTERVAL 15 DAY),
    DATE_ADD(CURDATE(), INTERVAL 75 DAY),
    'active',
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM `internship_periods` WHERE `code` = 'HK1-2026-TT');

-- Đợt 2: Đồ án - Dự kiến (draft)
INSERT INTO `internship_periods` (`code`, `name`, `type`, `description`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`)
SELECT 
    'HK2-2026-DA',
    'Đồ án Tốt nghiệp Đại học Học kỳ 2 Năm 2026',
    'graduation_project',
    'Đợt làm đồ án tốt nghiệp cuối khóa dành cho sinh viên đủ điều kiện tốt nghiệp đợt 2.',
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    DATE_ADD(CURDATE(), INTERVAL 150 DAY),
    'draft',
    NOW(),
    NOW()
WHERE NOT EXISTS (SELECT 1 FROM `internship_periods` WHERE `code` = 'HK2-2026-DA');

-- 5. NẠP 10 ĐỀ TÀI MẪU (5 Thực tập + 5 Đồ án, đa dạng 4 trạng thái)
-- Đề tài 1: Thực tập - ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-001',
    'Xây dựng Ứng dụng Quản lý Bán hàng Đa kênh Tích hợp Trí tuệ Nhân tạo',
    'Nghiên cứu và phát triển hệ thống bán hàng đa kênh cho doanh nghiệp vừa và nhỏ, tích hợp gợi ý sản phẩm thông minh và chatbot hỗ trợ khách hàng tự động.',
    'internship',
    p.id,
    u.id,
    2,
    'approved',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-001');

-- Đề tài 2: Thực tập - ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-002',
    'Phát triển Hệ thống Quản trị Quan hệ Khách hàng (CRM) trên Nền tảng Điện toán Đám mây',
    'Thiết kế và triển khai giải pháp CRM phục vụ doanh nghiệp logistics, hỗ trợ quản lý phễu khách hàng tiềm năng, tự động hóa gửi email và báo cáo doanh thu theo thời gian thực.',
    'internship',
    p.id,
    u.id,
    3,
    'approved',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-002');

-- Đề tài 3: Thực tập - CHỜ DUYỆT (pending)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-003',
    'Xây dựng Cổng Dịch vụ Công Trực tuyến Phục vụ Tiếp nhận Hồ sơ Hành chính Một cửa',
    'Xây dựng cổng tiếp nhận và số hóa hồ sơ một cửa cấp phường/xã, xác thực danh tính điện tử và theo dõi tiến độ xử lý hồ sơ minh bạch.',
    'internship',
    p.id,
    u.id,
    2,
    'pending',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-003');

-- Đề tài 4: Thực tập - BẢN NHÁP (draft)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-004',
    'Nghiên cứu Quy trình Kiểm thử Tự động Hóa cho Ứng dụng Di động Fintech',
    'Áp dụng framework Appium và Selenium để xây dựng bộ kịch bản kiểm thử tự động hồi quy cho quy trình thanh toán trên ứng dụng tài chính ngân hàng.',
    'internship',
    p.id,
    u.id,
    1,
    'draft',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-004');

-- Đề tài 5: Thực tập - TỪ CHỐI (rejected)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-005',
    'Thiết kế Website Giới thiệu Sản phẩm Thủ công Mỹ nghệ Làng nghề Truyền thống',
    'Xây dựng website giới thiệu các sản phẩm gốm sứ và mây tre đan, tích hợp bản đồ chỉ dẫn du lịch làng nghề.',
    'internship',
    p.id,
    u.id,
    3,
    'rejected',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-005');

-- Đề tài 6: Đồ án - ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DA-2026-001',
    'Hệ thống Phát hiện và Cảnh báo Gian lận trong Thi Trực tuyến sử dụng Thị giác Máy tính',
    'Nghiên cứu ứng dụng thuật toán phát hiện khuôn mặt, hướng nhìn mắt và nhận dạng người lạ trong camera nhằm hỗ trợ giám sát phòng thi trực tuyến tự động.',
    'graduation_project',
    p.id,
    u.id,
    2,
    'approved',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK2-2026-DA'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DA-2026-001');

-- Đề tài 7: Đồ án - ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DA-2026-002',
    'Nghiên cứu Thuật toán Phân cụm và Đề xuất Lộ trình Học tập Cá nhân hóa',
    'Phân tích dữ liệu học tập của sinh viên qua các học kỳ, ứng dụng thuật toán K-Means và lọc cộng tác (Collaborative Filtering) để định hướng môn học tự chọn.',
    'graduation_project',
    p.id,
    u.id,
    1,
    'approved',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK2-2026-DA'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DA-2026-002');

-- Đề tài 8: Đồ án - CHỜ DUYỆT (pending)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DA-2026-003',
    'Xây dựng Nền tảng IoT Giám sát Chất lượng Nước nuôi Thủy sản vùng Ven biển',
    'Thiết kế mạch vi điều khiển kết nối cảm biến đo độ mặn, pH, nhiệt độ và oxy hòa tan; truyền dữ liệu qua giao thức MQTT lên Cloud để theo dõi và cảnh báo.',
    'graduation_project',
    p.id,
    u.id,
    2,
    'pending',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK2-2026-DA'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DA-2026-003');

-- Đề tài 9: Đồ án - BẢN NHÁP (draft)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DA-2026-004',
    'Ứng dụng Công nghệ Blockchain trong Quản lý và Xác thực Văn bằng Chứng chỉ',
    'Triển khai Smart Contract trên mạng thử nghiệm để lưu trữ mã băm (hash) của văn bằng tốt nghiệp, cung cấp cổng tra cứu công khai ngăn ngừa làm giả bằng cấp.',
    'graduation_project',
    p.id,
    u.id,
    2,
    'draft',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK2-2026-DA'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DA-2026-004');

-- Đề tài 10: Đồ án - TỪ CHỐI (rejected)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DA-2026-005',
    'Phát triển Trợ lý ảo AI Hỗ trợ Tư vấn Tuyển sinh và Hướng nghiệp Sinh viên',
    'Tích hợp mô hình ngôn ngữ lớn (LLM) với kỹ thuật Retrieval-Augmented Generation (RAG) trên tập dữ liệu đề án tuyển sinh để giải đáp thắc mắc tự động 24/7.',
    'graduation_project',
    p.id,
    u.id,
    1,
    'rejected',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK2-2026-DA'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DA-2026-005');

-- 6. NẠP DỮ LIỆU MẪU LỊCH SỬ XÉT DUYỆT (topic_approvals)
-- Ghi nhận lịch sử cho đề tài đã duyệt DT-2026-001
INSERT INTO `topic_approvals` (`topic_id`, `reviewer_id`, `decision`, `comments`, `created_at`)
SELECT t.id, u.id, 'approved', 'Đề tài có tính thực tiễn cao, nội dung phù hợp với định hướng doanh nghiệp.', NOW()
FROM `topics` t
CROSS JOIN `users` u
WHERE t.code = 'DT-2026-001'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topic_approvals` WHERE `topic_id` = t.id AND `reviewer_id` = u.id AND `decision` = 'approved');

-- Ghi nhận lịch sử cho đề tài bị từ chối DT-2026-005
INSERT INTO `topic_approvals` (`topic_id`, `reviewer_id`, `decision`, `comments`, `created_at`)
SELECT t.id, u.id, 'rejected', 'Độ phức tạp chưa đáp ứng yêu cầu thực tập tốt nghiệp chuyên ngành CNTT, đề nghị bổ sung phần phân tích nghiệp vụ.', NOW()
FROM `topics` t
CROSS JOIN `users` u
WHERE t.code = 'DT-2026-005'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topic_approvals` WHERE `topic_id` = t.id AND `reviewer_id` = u.id AND `decision` = 'rejected');

-- 7. NẠP DỮ LIỆU MẪU NGUYỆN VỌNG SINH VIÊN (topic_wishes)
-- Sinh viên sv001 đăng ký nguyện vọng 1 cho đề tài DT-2026-001
INSERT INTO `topic_wishes` (`student_id`, `topic_id`, `period_id`, `priority`, `status`, `note`, `created_at`, `updated_at`)
SELECT u.id, t.id, p.id, 1, 'pending', 'Em mong muốn được rèn luyện kỹ năng phát triển ứng dụng bán hàng tích hợp AI.', NOW(), NOW()
FROM `users` u
CROSS JOIN `topics` t
CROSS JOIN `internship_periods` p
WHERE u.username = 'sv001'
  AND t.code = 'DT-2026-001'
  AND p.code = 'HK1-2026-TT'
  AND NOT EXISTS (
      SELECT 1 FROM `topic_wishes` 
      WHERE `student_id` = u.id AND `period_id` = p.id AND `priority` = 1
  );

-- Sinh viên sv001 đăng ký nguyện vọng 2 cho đề tài DT-2026-002
INSERT INTO `topic_wishes` (`student_id`, `topic_id`, `period_id`, `priority`, `status`, `note`, `created_at`, `updated_at`)
SELECT u.id, t.id, p.id, 2, 'pending', 'Nguyện vọng phụ phù hợp với sở thích về điện toán đám mây.', NOW(), NOW()
FROM `users` u
CROSS JOIN `topics` t
CROSS JOIN `internship_periods` p
WHERE u.username = 'sv001'
  AND t.code = 'DT-2026-002'
  AND p.code = 'HK1-2026-TT'
  AND NOT EXISTS (
      SELECT 1 FROM `topic_wishes` 
      WHERE `student_id` = u.id AND `period_id` = p.id AND `priority` = 2
  );
