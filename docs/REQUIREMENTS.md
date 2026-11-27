# TÀI LIỆU YÊU CẦU NGHIỆP VỤ HỆ THỐNG (SYSTEM REQUIREMENTS)
## DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN

- **Đơn vị áp dụng:** Trường Đại học Vinh
- **Mục tiêu hệ thống:** Số hóa toàn diện quy trình tổ chức, đăng ký, xét duyệt, phân công và theo dõi tiến độ thực tập tốt nghiệp, đồ án tốt nghiệp của sinh viên.
- **Tiêu chuẩn kỹ thuật:** PHP 8.2 thuần MVC, MySQL / MariaDB (InnoDB, UTF-8 MB4), Bootstrap 5, Tabler UI.

---

## 1. CÁC TÁC NHÂN HỆ THỐNG (ACTORS & ROLES)

Hệ thống quản lý phân quyền theo 6 vai trò chính (hỗ trợ tài khoản đa vai trò / kiêm nhiệm):

1. **Quản trị hệ thống (`admin`):**
   - Quản lý danh mục người dùng (thêm, sửa, khóa/mở khóa tài khoản, phân quyền vai trò).
   - Cấu hình hệ thống, quản lý mốc thời gian chung, sao lưu và phục hồi dữ liệu.
2. **Phòng Đào tạo (`training_dept`):**
   - Quản lý các đợt thực tập và đồ án tốt nghiệp (tạo đợt, mở đợt, đóng đợt).
   - Quản lý mốc tiến độ chung của đợt.
   - Chạy thuật toán đối sánh phân công đề tài tự động, điều chỉnh phân công thủ công và công bố kết quả chính thức toàn đợt.
3. **Trưởng bộ môn / Khoa (`department_head`):**
   - Quản lý và phê duyệt danh mục đề tài thực tập/đồ án thuộc bộ môn.
   - Phân công cán bộ giảng viên hướng dẫn (GVHD), giảng viên phản biện (GVPB).
   - Theo dõi tiến độ thực hiện đề tài và nộp báo cáo của sinh viên trong bộ môn.
4. **Giảng viên hướng dẫn (`lecturer`):**
   - Đề xuất đề tài thực tập / đồ án mới gửi Trưởng bộ môn phê duyệt.
   - Theo dõi danh sách sinh viên được phân công vào đề tài của mình.
   - Đọc, nhận xét và phê duyệt nhật ký thực tập hàng tuần của sinh viên.
   - Đọc báo cáo định kỳ, yêu cầu chỉnh sửa nộp lại hoặc chấm điểm theo rubric.
5. **Sinh viên (`student`):**
   - Tra cứu các đợt thực tập/đồ án đang mở và danh mục đề tài đã được duyệt.
   - Đăng ký và sắp xếp thứ tự ưu tiên các nguyện vọng đề tài (tối đa 3 nguyện vọng).
   - Tra cứu kết quả phân công chính thức sau khi Phòng Đào tạo công bố.
   - Xem lộ trình mốc tiến độ (milestones), viết nhật ký thực tập hàng tuần.
   - Nộp tệp báo cáo trực tuyến theo từng mốc và xem lịch sử nhận xét đánh giá.
6. **Doanh nghiệp tiếp nhận thực tập (`enterprise`):**
   - Đề xuất đề tài và chỉ tiêu tiếp nhận sinh viên thực tập tại đơn vị.
   - Tra cứu danh sách sinh viên được phân công về công ty thực tập.
   - Theo dõi chuyên cần, xác nhận nhật ký và đánh giá kết quả thực tập của sinh viên.

---

## 2. YÊU CẦU CHỨC NĂNG CHI TIẾT (FUNCTIONAL REQUIREMENTS)

### 2.1. Phân hệ Xác thực & Phân quyền (Authentication & Authorization)
- **FR-AUTH-01:** Đăng nhập an toàn bằng mã định danh (username/MSSV/MSGV) và mật khẩu đã được mã hóa chuẩn Bcrypt.
- **FR-AUTH-02:** Kiểm soát phiên làm việc (Session Management) và tự động đăng xuất khi kết thúc phiên.
- **FR-AUTH-03:** Hỗ trợ mô hình phân quyền đa vai trò (Multi-Role RBAC). Một tài khoản có thể kiêm nhiệm nhiều vai trò (ví dụ: Trưởng bộ môn kiêm Giảng viên hướng dẫn).
- **FR-AUTH-04:** Chặn truy cập trái phép ở cả tầng giao diện (Sidebar/View) và tầng Controller máy chủ (trả về HTTP 403 Forbidden).

### 2.2. Phân hệ Quản lý Tài khoản (User Management)
- **FR-USER-01:** Quản trị viên thêm mới tài khoản người dùng với đầy đủ thông tin cá nhân (họ tên, email, số điện thoại, mật khẩu).
- **FR-USER-02:** Khóa (`status = 0`) hoặc Mở khóa (`status = 1`) tài khoản. Tài khoản bị khóa sẽ không thể đăng nhập vào hệ thống.
- **FR-USER-03:** Cập nhật thông tin cá nhân và tải lên ảnh đại diện cá nhân (hỗ trợ crop ảnh và lưu trữ an toàn).

### 2.3. Phân hệ Quản lý Đợt thực tập / Đồ án (Internship Periods)
- **FR-PER-01:** Phòng Đào tạo tạo mới đợt thực tập/đồ án với mã đợt duy nhất, loại đợt (`internship` hoặc `graduation_project`), ngày bắt đầu và kết thúc (`start_date <= end_date`).
- **FR-PER-02:** Quản lý vòng đời trạng thái đợt: `draft` (Nháp) -> `active` (Đang diễn ra) -> `closed` (Đã đóng).
- **FR-PER-03:** Chỉ cho phép đăng ký nguyện vọng khi đợt ở trạng thái `active` và trong thời hạn quy định.
- **FR-PER-04:** Chặn xóa đợt khi đã có đề tài hoặc dữ liệu phân công liên kết để bảo đảm an toàn dữ liệu.

### 2.4. Phân hệ Quản lý & Phê duyệt Đề tài (Topic Management & Approval)
- **FR-TOP-01:** Giảng viên và Doanh nghiệp tạo đề tài mới (mã đề tài, tên đề tài, mô tả, chỉ tiêu sinh viên tối đa, yêu cầu kiến thức).
- **FR-TOP-02:** Đề tài sau khi tạo ở trạng thái `draft`, người đề xuất gửi duyệt (`submit`) chuyển sang `pending`.
- **FR-TOP-03:** Trưởng bộ môn xem danh sách đề tài chờ duyệt, thực hiện phê duyệt (`approved`) hoặc từ chối (`rejected`) kèm lý do giải thích.
- **FR-TOP-04:** Lưu trữ lịch sử toàn bộ các lần xét duyệt trong bảng `topic_approvals`.

### 2.5. Phân hệ Đăng ký Nguyện vọng Đề tài (Topic Wishes)
- **FR-WSH-01:** Sinh viên thuộc đợt đang mở được đăng ký tối đa 3 nguyện vọng (NV1, NV2, NV3) theo thứ tự ưu tiên.
- **FR-WSH-02:** Chỉ cho phép chọn các đề tài đã được phê duyệt (`approved`).
- **FR-WSH-03:** Chặn sinh viên đăng ký trùng lặp một đề tài cho nhiều nguyện vọng khác nhau trong cùng một đợt.
- **FR-WSH-04:** Hỗ trợ thay đổi thứ tự ưu tiên hoặc hủy nguyện vọng khi đợt còn mở cổng đăng ký.

### 2.6. Phân hệ Phân công & Công bố Kết quả (Topic Assignments & Matching)
- **FR-ASG-01:** Thuật toán tự động đối sánh nguyện vọng (Auto-Matching Algorithm):
  * Ưu tiên xét theo thứ tự nguyện vọng của sinh viên (NV1 -> NV2 -> NV3).
  * Tiêu chí giải quyết xung đột khi số lượng đăng ký vượt quá chỉ tiêu: Xét điểm trung bình tích lũy (GPA) từ cao xuống thấp; nếu GPA bằng nhau, xét thời điểm gửi đăng ký sớm hơn.
  * Đảm bảo chỉ tiêu tối đa của đề tài và hạn ngạch hướng dẫn tối đa của từng giảng viên.
- **FR-ASG-02:** Toàn bộ kết quả chạy tự động được lưu ở trạng thái Bản dự thảo (`draft`).
- **FR-ASG-03:** Bảo mật kết quả: Khi ở trạng thái `draft`, Sinh viên tuyệt đối không thể tra cứu kết quả phân công.
- **FR-ASG-04:** Cán bộ quản lý có thể điều chỉnh phân công thủ công (Manual Assignment) hoặc hủy phân công.
- **FR-ASG-05:** Phòng Đào tạo thực hiện thao tác **Công bố kết quả (Publish)**. Khi đã công bố:
  * Trạng thái phân công chuyển sang `published`.
  * Sinh viên mở khóa màn hình xem kết quả chính thức (đề tài, giảng viên hướng dẫn, thông tin liên hệ).
  * Giảng viên và Doanh nghiệp tra cứu danh sách sinh viên được phân công chính thức.

### 2.7. Phân hệ Quản lý Mốc tiến độ (Milestone Management)
- **FR-MLS-01:** Quản lý tạo các mốc thời gian quan trọng trong đợt (nộp đề cương, báo cáo giữa kỳ, nộp bản thảo, nộp báo cáo cuối kỳ).
- **FR-MLS-02:** Kiểm tra tính hợp lệ thời gian: Bắt buộc tiêu đề, ngày bắt đầu phải nhỏ hơn hoặc bằng hạn nộp (`start_date <= due_date`).
- **FR-MLS-03:** Tự động phát hiện và chuyển trạng thái mốc sang `overdue` khi đã quá hạn chót mà chưa hoàn thành.
- **FR-MLS-04:** Bảo vệ an toàn dữ liệu: Chặn xóa mốc thời gian khi đã có báo cáo nộp của sinh viên liên kết.
- **FR-MLS-05:** Cung cấp số liệu KPI thống kê tiến độ hoàn thành chung của đợt và riêng của từng sinh viên qua REST API.

### 2.8. Phân hệ Nhật ký & Báo cáo Thực tập (Logs & Reports)
- **FR-REP-01:** Sinh viên viết nhật ký thực tập theo tuần. Ràng buộc duy nhất `(assignment_id, week_number)` chống nộp trùng tuần.
- **FR-REP-02:** Người hướng dẫn xác nhận (`confirmed`) hoặc yêu cầu viết lại (`rejected`) nhật ký tuần.
- **FR-REP-03:** Tải lên tệp báo cáo an toàn: Kiểm tra định dạng (.pdf, .doc, .docx, .zip), kiểm tra MIME type thực tế, chặn mã độc thực thi (.php, .exe, .sh), giới hạn tối đa 20MB.
- **FR-REP-04:** Lưu lịch sử đa phiên bản (`v1, v2, v3...`): Không ghi đè mất tệp và nhận xét của phiên bản nộp trước.
- **FR-REP-05:** Tự động phát hiện nộp muộn: So sánh thời điểm nộp với `milestones.due_date` để gắn cờ `is_late = 1`.
- **FR-REP-06:** Người hướng dẫn đánh giá nhận xét, cho điểm hoặc yêu cầu chỉnh sửa nộp lại (`resubmit_required`).

---

## 3. YÊU CẦU PHI CHỨC NĂNG (NON-FUNCTIONAL REQUIREMENTS)

- **Hiệu năng:** Thời gian phản hồi các truy vấn thông thường < 200ms; thuật toán đối sánh tự động xử lý hàng trăm sinh viên trong < 2 giây.
- **Bảo mật:**
  * Phòng chống SQL Injection bằng PDO Prepared Statements 100%.
  * Phòng chống tấn công Cross-Site Scripting (XSS) bằng hàm encode `htmlspecialchars` ở tầng View.
  * Phòng chống giả mạo request (CSRF) qua token xác thực trong biểu mẫu.
  * Tách biệt thư mục lưu trữ tệp tải lên, đổi tên file ngẫu nhiên bằng chuỗi băm SHA-256 chống đoán đường dẫn.
- **Khả năng tương thích:** Tương thích tốt trên trình duyệt Chrome, Edge, Firefox và thiết bị di động (Responsive Layout).
