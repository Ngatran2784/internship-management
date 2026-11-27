-- ==============================================================================
-- CƠ SỞ DỮ LIỆU: QUẢN LÝ MỐC TIẾN ĐỘ, NHẬT KÝ VÀ BÁO CÁO THỰC TẬP
-- Hệ quản trị CSDL: MySQL / MariaDB (InnoDB, UTF-8 MB4)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------------
-- 1. BẢNG `milestones`: Quản lý các mốc tiến độ thực tập / đồ án
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `milestones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Đợt thực tập / đồ án áp dụng',
    `title` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề mốc tiến độ (vd: Nộp đề cương chi tiết)',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết yêu cầu, tiêu chí hoàn thành',
    `start_date` DATE NOT NULL COMMENT 'Ngày bắt đầu mốc tiến độ',
    `due_date` DATETIME NOT NULL COMMENT 'Hạn chót hoàn thành / nộp báo cáo',
    `order_index` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Thứ tự hiển thị trong đợt',
    `status` ENUM('pending', 'active', 'completed', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái mốc',
    `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Bắt buộc, 0: Tùy chọn',
    `created_by` INT UNSIGNED NOT NULL COMMENT 'ID người tạo mốc tiến độ (users.id)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_milestones_period` FOREIGN KEY (`period_id`) 
        REFERENCES `internship_periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_milestones_creator` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,

    INDEX `idx_milestones_period` (`period_id`),
    INDEX `idx_milestones_due_date` (`due_date`),
    INDEX `idx_milestones_status` (`status`),
    INDEX `idx_milestones_period_status` (`period_id`, `status`),
    INDEX `idx_milestones_order` (`period_id`, `order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý các mốc tiến độ thực tập / đồ án';

-- ------------------------------------------------------------------------------
-- 2. BẢNG `internship_logs`: Quản lý nhật ký thực tập của sinh viên
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `internship_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT UNSIGNED NOT NULL COMMENT 'Phân công chính thức của SV',
    `student_id` INT UNSIGNED NOT NULL COMMENT 'ID sinh viên',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'ID đợt thực tập',
    `week_number` INT UNSIGNED NOT NULL COMMENT 'Tuần thực tập thứ N',
    `log_date` DATE NOT NULL COMMENT 'Ngày ghi nhật ký / kết thúc tuần',
    `work_done` TEXT NOT NULL COMMENT 'Nội dung công việc đã thực hiện trong tuần',
    `difficulties` TEXT NULL COMMENT 'Khó khăn, vướng mắc cần giải quyết',
    `plan_next_week` TEXT NULL COMMENT 'Kế hoạch công việc cho tuần tiếp theo',
    `status` ENUM('draft', 'submitted', 'confirmed', 'rejected') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái nhật ký',
    `supervisor_comment` TEXT NULL COMMENT 'Nhận xét của GVHD hoặc cán bộ DN',
    `confirmed_by` INT UNSIGNED NULL COMMENT 'ID người xác nhận nhật ký',
    `confirmed_at` DATETIME NULL COMMENT 'Thời điểm xác nhận nhật ký',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_logs_assignment` FOREIGN KEY (`assignment_id`) 
        REFERENCES `topic_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_logs_student` FOREIGN KEY (`student_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_logs_period` FOREIGN KEY (`period_id`) 
        REFERENCES `internship_periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_logs_confirmer` FOREIGN KEY (`confirmed_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,

    UNIQUE KEY `uq_assignment_week` (`assignment_id`, `week_number`),
    INDEX `idx_logs_student` (`student_id`),
    INDEX `idx_logs_period` (`period_id`),
    INDEX `idx_logs_status` (`status`),
    INDEX `idx_logs_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng nhật ký thực tập hàng tuần của sinh viên';

-- ------------------------------------------------------------------------------
-- 3. BẢNG `internship_reports`: Quản lý báo cáo thực tập
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `internship_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `milestone_id` INT UNSIGNED NOT NULL COMMENT 'Mốc tiến độ cần nộp báo cáo',
    `assignment_id` INT UNSIGNED NOT NULL COMMENT 'Bản ghi phân công đề tài của SV',
    `student_id` INT UNSIGNED NOT NULL COMMENT 'ID sinh viên nộp báo cáo',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'ID đợt thực tập',
    `title` VARCHAR(255) NOT NULL COMMENT 'Tiêu đề báo cáo nộp',
    `current_version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Phiên bản nộp hiện tại',
    `status` ENUM('draft', 'submitted', 'reviewing', 'approved', 'rejected', 'resubmit_required') NOT NULL DEFAULT 'submitted' COMMENT 'Trạng thái duyệt báo cáo',
    `grade` DECIMAL(4,2) NULL COMMENT 'Điểm đánh giá (thang 10 nếu có)',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm nộp phiên bản mới nhất',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_reports_milestone` FOREIGN KEY (`milestone_id`) 
        REFERENCES `milestones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_assignment` FOREIGN KEY (`assignment_id`) 
        REFERENCES `topic_assignments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_student` FOREIGN KEY (`student_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reports_period` FOREIGN KEY (`period_id`) 
        REFERENCES `internship_periods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_student_milestone` (`student_id`, `milestone_id`),
    INDEX `idx_reports_assignment` (`assignment_id`),
    INDEX `idx_reports_milestone` (`milestone_id`),
    INDEX `idx_reports_status` (`status`),
    INDEX `idx_reports_period` (`period_id`),
    INDEX `idx_reports_submitted_at` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý báo cáo thực tập theo mốc tiến độ';

-- ------------------------------------------------------------------------------
-- 4. BẢNG `report_submissions`: Lưu lịch sử các lần nộp và nộp lại báo cáo
-- Không ghi đè mất dữ liệu của lần nộp trước
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `report_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT UNSIGNED NOT NULL COMMENT 'ID báo cáo chính',
    `version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Lần nộp thứ mấy (v1, v2, v3...)',
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Tên file gốc do SV tải lên',
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Đường dẫn lưu file trên máy chủ',
    `file_size` INT UNSIGNED NOT NULL COMMENT 'Dung lượng file tính bằng bytes',
    `file_mime` VARCHAR(100) NOT NULL COMMENT 'Định dạng MIME (vd: application/pdf)',
    `file_ext` VARCHAR(20) NOT NULL COMMENT 'Phần mở rộng file (pdf, doc, docx, zip)',
    `student_note` TEXT NULL COMMENT 'Ghi chú / tóm tắt chỉnh sửa của SV',
    `is_late` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1: Nộp muộn sau hạn chót, 0: Nộp đúng hạn',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tải lên lần nộp này',

    CONSTRAINT `fk_submissions_report` FOREIGN KEY (`report_id`) 
        REFERENCES `internship_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE KEY `uq_report_version` (`report_id`, `version`),
    INDEX `idx_submissions_report` (`report_id`),
    INDEX `idx_submissions_submitted_at` (`submitted_at`),
    INDEX `idx_submissions_is_late` (`is_late`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lịch sử các lần nộp tệp báo cáo';

-- ------------------------------------------------------------------------------
-- 5. BẢNG `report_reviews`: Lưu nhận xét, kết quả duyệt và yêu cầu chỉnh sửa
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `report_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT UNSIGNED NOT NULL COMMENT 'ID báo cáo được đánh giá',
    `submission_id` INT UNSIGNED NULL COMMENT 'Lần nộp cụ thể được nhận xét (nếu có)',
    `reviewer_id` INT UNSIGNED NOT NULL COMMENT 'ID người nhận xét (GVHD hoặc cán bộ DN)',
    `decision` ENUM('approved', 'rejected', 'resubmit_required', 'comment_only') NOT NULL DEFAULT 'comment_only' COMMENT 'Quyết định duyệt',
    `comments` TEXT NOT NULL COMMENT 'Nội dung nhận xét, góp ý chi tiết',
    `edit_requirements` TEXT NULL COMMENT 'Yêu cầu sinh viên chỉnh sửa bổ sung (khi resubmit_required)',
    `score` DECIMAL(4,2) NULL COMMENT 'Điểm đánh giá cho lần nộp này (nếu có)',
    `reviewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm hoàn thành nhận xét',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_reviews_report` FOREIGN KEY (`report_id`) 
        REFERENCES `internship_reports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_submission` FOREIGN KEY (`submission_id`) 
        REFERENCES `report_submissions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_reviews_reviewer` FOREIGN KEY (`reviewer_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,

    INDEX `idx_reviews_report` (`report_id`),
    INDEX `idx_reviews_submission` (`submission_id`),
    INDEX `idx_reviews_reviewer` (`reviewer_id`),
    INDEX `idx_reviews_decision` (`decision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu nhận xét, đánh giá và yêu cầu chỉnh sửa báo cáo';

SET FOREIGN_KEY_CHECKS = 1;
