-- ==============================================================================
-- DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN
-- TỆP DỮ LIỆU MẪU: DANH MỤC 10 ĐỀ TÀI THỰC TẬP & ĐỒ ÁN TỐT NGHIỆP
-- Đường dẫn: database/seeds/topics.sql
-- Động cơ lưu trữ: InnoDB | Bộ mã ký tự: utf8mb4 | Đối chiếu: utf8mb4_unicode_ci
-- ==============================================================================
-- ĐIỀU KIỆN TIÊN QUYẾT (PREREQUISITES):
-- 1. Cơ sở dữ liệu `internship_management` đã được tạo và kích hoạt.
-- 2. Đã nhập schema nền tảng (`database/schema.sql`).
-- 3. Đã chạy migration tạo bảng đề tài (`database/migrations/create_topics_table.sql`).
-- 4. Đã có dữ liệu đợt (`HK1-2026-TT`, `HK2-2026-DA`) và tài khoản người dùng (`admin`).
--    (Có thể nạp nhanh qua lệnh: `php tests/seed_dev.php`).
-- ==============================================================================
-- NGUYÊN TẮC AN TOÀN VÀ IDEMPOTENT:
-- 1. Tuyệt đối KHÔNG xóa bảng (DROP), KHÔNG làm trống bảng (TRUNCATE).
-- 2. Không sử dụng ID số cứng; tự động tra cứu ID thực tế qua subquery/JOIN.
-- 3. Chỉ thêm bản ghi nếu mã đề tài chưa tồn tại (WHERE NOT EXISTS).
-- 4. Có thể thực thi nhiều lần liên tục mà không gây lỗi trùng lặp dữ liệu.
-- ==============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ------------------------------------------------------------------------------
-- 1. ĐỀ TÀI THỰC TẬP TỐT NGHIỆP (Đợt: HK1-2026-TT | Loại: internship)
-- ------------------------------------------------------------------------------

-- Đề tài 1: Trạng thái ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-001',
    'Xây dựng Hệ thống Quản lý Thực tập và Đồ án Sinh viên',
    'Nghiên cứu kiến trúc MVC thuần và xây dựng ứng dụng web quản lý toàn diện quy trình thực tập doanh nghiệp, nộp báo cáo định kỳ và đánh giá kết quả.',
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

-- Đề tài 2: Trạng thái ĐÃ DUYỆT (approved)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-002',
    'Ứng dụng Trí tuệ Nhân tạo trong Phân loại và Tóm tắt Văn bản Pháp luật',
    'Xây dựng hệ thống tự động trích xuất thực thể, phân loại văn bản và tóm tắt nội dung các thông tư, nghị định hướng dẫn bằng mô hình học sâu.',
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
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-002');

-- Đề tài 3: Trạng thái CHỜ DUYỆT (pending)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-003',
    'Phát triển Ứng dụng Di động Điểm danh Sinh viên bằng Định vị GPS và Quét mã QR',
    'Thiết kế ứng dụng mobile hỗ trợ giảng viên điểm danh nhanh trong lớp học dựa trên tọa độ GPS khuôn viên trường và mã QR động có thời hạn.',
    'internship',
    p.id,
    u.id,
    1,
    'pending',
    NOW(),
    NOW()
FROM `internship_periods` p
CROSS JOIN `users` u
WHERE p.code = 'HK1-2026-TT'
  AND u.username = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `topics` WHERE `code` = 'DT-2026-003');

-- Đề tài 4: Trạng thái BẢN NHÁP (draft)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-004',
    'Nghiên cứu và Triển khai Hệ thống Giám sát An toàn Thông tin dựa trên Wazuh SIEM',
    'Cài đặt cụm Wazuh Server và các Agent giám sát nhật ký máy chủ, phát hiện xâm nhập trái phép và cảnh báo sớm lỗ hổng bảo mật cho hệ thống mạng nội bộ.',
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

-- Đề tài 5: Trạng thái TỪ CHỐI (rejected)
INSERT INTO `topics` (`code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`)
SELECT 
    'DT-2026-005',
    'Xây dựng Website Thương mại Điện tử Nông sản Nghệ An ứng dụng Vi dịch vụ',
    'Nền tảng kết nối các cơ sở sản xuất nông sản đạt chuẩn OCOP với người tiêu dùng, tích hợp cổng thanh toán trực tuyến và gợi ý sản phẩm phù hợp.',
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

-- ------------------------------------------------------------------------------
-- 2. ĐỀ TÀI ĐỒ ÁN TỐT NGHIỆP (Đợt: HK2-2026-DA | Loại: graduation_project)
-- ------------------------------------------------------------------------------

-- Đề tài 6: Trạng thái ĐÃ DUYỆT (approved)
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

-- Đề tài 7: Trạng thái ĐÃ DUYỆT (approved)
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

-- Đề tài 8: Trạng thái CHỜ DUYỆT (pending)
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

-- Đề tài 9: Trạng thái BẢN NHÁP (draft)
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

-- Đề tài 10: Trạng thái TỪ CHỐI (rejected)
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
