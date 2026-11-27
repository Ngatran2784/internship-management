-- ==============================================================================
-- DỮ LIỆU MẪU: MỐC TIẾN ĐỘ, NHẬT KÝ VÀ BÁO CÁO THỰC TẬP
-- Phục vụ phát triển, kiểm thử chức năng Quản lý Milestone, Nhật ký, Báo cáo
-- ==============================================================================

-- 1. Thêm mốc tiến độ (Milestones) cho Đợt 11 (HK1-2026-TT)
INSERT INTO `milestones` (`id`, `period_id`, `title`, `description`, `start_date`, `due_date`, `order_index`, `status`, `is_mandatory`, `created_by`, `created_at`)
VALUES
(1, 11, 'Nộp đề cương thực tập & Tiếp nhận DN', 'Sinh viên nộp bản đề cương chi tiết công việc thực tập kèm phiếu tiếp nhận có chữ ký và dấu mộc của doanh nghiệp.', '2026-09-01', '2026-09-15 23:59:59', 1, 'completed', 1, 1000124, '2026-09-01 08:00:00'),
(2, 11, 'Báo cáo tiến độ giữa kỳ (Tuần 8)', 'Sinh viên báo cáo kết quả công việc 8 tuần đầu, tiến độ tìm hiểu nghiệp vụ và các kết quả ban đầu.', '2026-09-16', '2026-10-25 23:59:59', 2, 'completed', 1, 1000124, '2026-09-01 08:00:00'),
(3, 11, 'Nộp bản thảo báo cáo thực tập tốt nghiệp', 'Nộp bản thảo toàn văn báo cáo để Giảng viên hướng dẫn đọc phản biện và góp ý chỉnh sửa trước khi nộp chính thức.', '2026-10-26', '2026-11-20 23:59:59', 3, 'overdue', 1, 1000124, '2026-09-01 08:00:00'),
(4, 11, 'Nộp báo cáo chính thức & Phiếu đánh giá DN', 'Nộp toàn văn báo cáo bản cuối cùng hoàn thiện kèm phiếu đánh giá điểm và nhận xét của người hướng dẫn tại doanh nghiệp.', '2026-11-21', '2026-12-15 23:59:59', 4, 'active', 1, 1000124, '2026-09-01 08:00:00')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `start_date` = VALUES(`start_date`), `due_date` = VALUES(`due_date`), `status` = VALUES(`status`), `is_mandatory` = VALUES(`is_mandatory`);

-- 2. Thêm mốc tiến độ (Milestones) cho Đợt 12 (HK2-2026-DA)
INSERT INTO `milestones` (`id`, `period_id`, `title`, `description`, `start_date`, `due_date`, `order_index`, `status`, `is_mandatory`, `created_by`, `created_at`)
VALUES
(5, 12, 'Đăng ký đề cương đồ án tốt nghiệp', 'Nộp đề cương nghiên cứu, mục tiêu, phạm vi và công nghệ dự kiến sử dụng cho đồ án tốt nghiệp.', '2027-01-15', '2027-02-10 23:59:59', 1, 'pending', 1, 1000124, '2026-11-15 08:00:00'),
(6, 12, 'Báo cáo tiến độ đồ án đợt 1 (Kiến trúc & CSDL)', 'Báo cáo thiết kế hệ thống, sơ đồ cơ sở dữ liệu, các giao diện phác thảo cơ bản (mockup / wireframe).', '2027-02-11', '2027-03-25 23:59:59', 2, 'pending', 1, 1000124, '2026-11-15 08:00:00'),
(7, 12, 'Báo cáo tiến độ đợt 2 & Demo sản phẩm', 'Trình bày sản phẩm chạy thực tế (prototype demo), các chức năng cốt lõi và kiểm thử ban đầu.', '2027-03-26', '2027-04-30 23:59:59', 3, 'pending', 1, 1000124, '2026-11-15 08:00:00'),
(8, 12, 'Nộp toàn văn đồ án tốt nghiệp & Slide bảo vệ', 'Nộp bản quyển đồ án hoàn chỉnh, mã nguồn chương trình và slide chuẩn bị cho buổi bảo vệ trước hội đồng.', '2027-05-01', '2027-05-20 23:59:59', 4, 'pending', 1, 1000124, '2026-11-15 08:00:00')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`), `start_date` = VALUES(`start_date`), `due_date` = VALUES(`due_date`), `status` = VALUES(`status`), `is_mandatory` = VALUES(`is_mandatory`);

-- 3. Tạo phân công cho SV sv001 trong Đợt 11 nếu chưa có
INSERT INTO `topic_assignments` (`id`, `period_id`, `student_id`, `topic_id`, `lecturer_id`, `matched_wish_priority`, `assignment_method`, `status`, `assigned_by`, `notes`, `assigned_at`)
SELECT 154, 11, 6, 7, 1000126, 1, 'auto', 'published', 5, 'Phân công chính thức đợt thực tập', NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `topic_assignments` WHERE `period_id` = 11 AND `student_id` = 6
);

-- 4. Dữ liệu mẫu Nhật ký thực tập (internship_logs) cho sv001
INSERT INTO `internship_logs` (`id`, `assignment_id`, `student_id`, `period_id`, `week_number`, `log_date`, `work_done`, `difficulties`, `plan_next_week`, `status`, `supervisor_comment`, `confirmed_by`, `confirmed_at`)
VALUES
(1, 154, 6, 11, 1, '2026-09-07', 'Tiếp nhận vị trí thực tập, làm quen môi trường làm việc, cài đặt môi trường phát triển (PHP, MySQL, Git) và nhận tài liệu nghiệp vụ.', 'Chưa nắm rõ cấu trúc thư mục dự án của công ty.', 'Tìm hiểu luồng xác thực đăng nhập và cấu trúc CSDL hiện tại.', 'confirmed', 'Em nắm bắt môi trường tốt, tiếp tục phát huy.', 1000126, '2026-09-08 14:00:00'),
(2, 154, 6, 11, 2, '2026-09-14', 'Nghiên cứu tài liệu phân tích thiết kế, viết tài liệu đặc tả chức năng phân công đề tài, thảo luận với anh Mentor.', 'Một số ràng buộc nghiệp vụ về hạn ngạch giảng viên còn phức tạp.', 'Bắt đầu viết mã nguồn cho Controller và Service đối sánh.', 'confirmed', 'Nội dung công việc rõ ràng, đã đạt mục tiêu tuần.', 1000126, '2026-09-15 10:30:00'),
(3, 154, 6, 11, 3, '2026-09-21', 'Triển khai thuật toán ghép cặp tự động, viết migration cho bảng topic_assignments.', 'Cần tối ưu thời gian chạy thuật toán khi số lượng sinh viên lớn.', 'Tiếp tục hoàn thiện các hàm kiểm tra chỉ tiêu và chạy kiểm thử đơn vị.', 'submitted', NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `work_done` = VALUES(`work_done`), `difficulties` = VALUES(`difficulties`), `plan_next_week` = VALUES(`plan_next_week`), `supervisor_comment` = VALUES(`supervisor_comment`);

-- 5. Dữ liệu mẫu Báo cáo (internship_reports), Lịch sử nộp (report_submissions) và Nhận xét (report_reviews)
-- Báo cáo Mốc 1: Đã nộp 2 phiên bản (v1 bị yêu cầu chỉnh sửa, v2 đã được duyệt)
INSERT INTO `internship_reports` (`id`, `milestone_id`, `assignment_id`, `student_id`, `period_id`, `title`, `current_version`, `status`, `grade`, `submitted_at`)
VALUES
(1, 1, 154, 6, 11, 'Báo cáo Đề cương Thực tập tốt nghiệp - SV Nguyễn Văn A', 2, 'approved', 9.00, '2026-09-12 16:30:00')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `current_version` = VALUES(`current_version`);

-- Lịch sử nộp phiên bản 1 (v1)
INSERT INTO `report_submissions` (`id`, `report_id`, `version`, `file_name`, `file_path`, `file_size`, `file_mime`, `file_ext`, `student_note`, `is_late`, `submitted_at`)
VALUES
(1, 1, 1, 'De_cuong_thuc_tap_v1.pdf', 'uploads/reports/2026/period_11/sv001_ms1_v1.pdf', 1048576, 'application/pdf', 'pdf', 'Em gửi Thầy bản đề cương sơ bộ đợt 1.', 0, '2026-09-10 14:20:00')
ON DUPLICATE KEY UPDATE `file_name` = VALUES(`file_name`), `student_note` = VALUES(`student_note`);

-- Nhận xét cho phiên bản 1 (yêu cầu chỉnh sửa lại)
INSERT INTO `report_reviews` (`id`, `report_id`, `submission_id`, `reviewer_id`, `decision`, `comments`, `edit_requirements`, `score`, `reviewed_at`)
VALUES
(1, 1, 1, 1000126, 'resubmit_required', 'Đề cương còn thiếu mục tiêu cụ thể và sơ đồ Gantt timeline chi tiết.', 'Bổ sung kế hoạch thực hiện theo từng tuần và công nghệ sẽ áp dụng tại công ty.', NULL, '2026-09-11 09:15:00')
ON DUPLICATE KEY UPDATE `comments` = VALUES(`comments`), `edit_requirements` = VALUES(`edit_requirements`);

-- Lịch sử nộp phiên bản 2 (v2 - bản hoàn thiện)
INSERT INTO `report_submissions` (`id`, `report_id`, `version`, `file_name`, `file_path`, `file_size`, `file_mime`, `file_ext`, `student_note`, `is_late`, `submitted_at`)
VALUES
(2, 1, 2, 'De_cuong_thuc_tap_v2_final.pdf', 'uploads/reports/2026/period_11/sv001_ms1_v2.pdf', 1572864, 'application/pdf', 'pdf', 'Em đã bổ sung timeline chi tiết và mô tả công nghệ theo góp ý của Thầy.', 0, '2026-09-12 16:30:00')
ON DUPLICATE KEY UPDATE `file_name` = VALUES(`file_name`), `student_note` = VALUES(`student_note`);

-- Nhận xét và phê duyệt cho phiên bản 2
INSERT INTO `report_reviews` (`id`, `report_id`, `submission_id`, `reviewer_id`, `decision`, `comments`, `edit_requirements`, `score`, `reviewed_at`)
VALUES
(2, 1, 2, 1000126, 'approved', 'Bản đề cương hoàn thiện tốt, kế hoạch rõ ràng và khả thi. Duyệt đề cương.', NULL, 9.00, '2026-09-13 10:00:00')
ON DUPLICATE KEY UPDATE `comments` = VALUES(`comments`), `score` = VALUES(`score`);
