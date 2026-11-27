# HƯỚNG DẪN SỬ DỤNG HỆ THỐNG (USER MANUAL)
## DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN

Tài liệu hướng dẫn thao tác chi tiết dành cho 6 nhóm người dùng chính trên hệ thống.

---

## 1. DÀNH CHO QUẢN TRỊ VIÊN HỆ THỐNG (`admin`)

### 1.1. Đăng nhập và Bảng điều khiển
- Truy cập địa chỉ: `http://localhost/CNPM/internship-management/public/login`
- Đăng nhập với tài khoản: `admin` / Mật khẩu mặc định.
- Bảng điều khiển hiển thị tổng quan số lượng tài khoản, đợt thực tập đang mở và đề tài trong hệ thống.

### 1.2. Quản lý Tài khoản & Phân quyền
- **Xem danh sách người dùng:** Menu **Quản lý người dùng** -> `/users`. Hỗ trợ lọc theo vai trò, trạng thái hoặc tìm kiếm họ tên/email/username.
- **Thêm người dùng mới:** Bấm nút **Thêm người dùng** -> `/users/create`, nhập thông tin cá nhân và tích chọn một hoặc nhiều vai trò tương ứng.
- **Khóa / Mở khóa tài khoản:** Tại danh sách người dùng, bấm nút thao tác khóa hoặc mở khóa để kích hoạt/ngưng quyền truy cập của người dùng.
- **Quản trị Mốc tiến độ:** Mở menu **Mốc tiến độ** -> `/milestones` để xem, tạo mới (`/milestones/create`), chỉnh sửa (`/milestones/edit`) hoặc xóa mốc thời gian của các đợt.

---

## 2. DÀNH CHO PHÒNG ĐÀO TẠO (`training_dept`)

### 2.1. Quản lý Đợt thực tập / Đồ án
- **Danh sách đợt:** Menu **Đợt thực tập và đồ án** -> `/periods`.
- **Tạo đợt mới:** Bấm **Tạo đợt mới** -> `/periods/create`, nhập mã đợt (vd: `HK1-2026-TT`), tên đợt, loại đợt, ngày bắt đầu và kết thúc.
- **Kích hoạt mở đợt:** Chuyển trạng thái đợt từ `draft` sang `active` để sinh viên bắt đầu đăng ký nguyện vọng.

### 2.2. Phân công Đề tài & Công bố Kết quả
- **Quản lý phân công:** Menu **Phân công và công bố kết quả** -> `/assignments`.
- **Chạy thuật toán đối sánh tự động:**
  1. Chọn đợt cần phân công.
  2. Bấm nút **Chạy đối sánh tự động** (Auto-Match). Hệ thống sẽ tự động ghép cặp sinh viên trúng tuyển theo thứ tự nguyện vọng và điểm GPA, lưu ở trạng thái **Bản dự thảo (`draft`)**.
- **Điều chỉnh phân công thủ công:** Có thể gán bổ sung sinh viên chưa trúng tuyển vào đề tài còn chỉ tiêu hoặc hủy phân công sai sót.
- **Công bố kết quả chính thức:** Sau khi rà soát danh sách dự thảo, bấm **Công bố kết quả (Publish)**. Kết quả sẽ được công bố chính thức cho toàn trường.

---

## 3. DÀNH CHO TRƯỞNG BỘ MÔN / KHOA (`department_head`)

### 3.1. Phê duyệt Đề tài
- **Xem đề tài chờ duyệt:** Menu **Đề tài chờ duyệt** -> `/topics/pending`.
- **Xem chi tiết và phê duyệt:**
  * Bấm xem chi tiết nội dung đề tài, chỉ tiêu sinh viên và người đề xuất.
  * Bấm **Duyệt đề tài** (`approved`) nếu đề tài đạt chuẩn chất lượng của bộ môn.
  * Bấm **Từ chối đề tài** (`rejected`) và ghi rõ lý do để người đề xuất chỉnh sửa lại.

### 3.2. Bố trí Giảng viên hướng dẫn / Phản biện
- Truy cập menu **Bố trí GVHD / GVPB** để phân công cán bộ giảng viên hướng dẫn hoặc phản biện cho từng đề tài.

---

## 4. DÀNH CHO GIẢNG VIÊN HƯỚNG DẪN (`lecturer`)

### 4.1. Đề xuất Đề tài mới
- Menu **Đề xuất đề tài** -> `/topics/create`.
- Nhập thông tin đề tài, mô tả yêu cầu, chọn đợt thực tập/đồ án áp dụng và chỉ tiêu sinh viên tối đa.
- Sau khi tạo, bấm **Gửi duyệt** để chuyển đề tài lên Trưởng bộ môn xét duyệt.

### 4.2. Theo dõi Sinh viên & Đánh giá Báo cáo
- **Danh sách sinh viên hướng dẫn:** Menu **Sinh viên hướng dẫn** -> `/assignments/supervisor-students`.
- **Xem mốc tiến độ:** Menu **Mốc tiến độ** -> `/milestones` để theo dõi các hạn chót nộp bài của đợt.
- **Đánh giá báo cáo:** Xem bài nộp của sinh viên, xem các phiên bản (v1, v2...), viết nhận xét, yêu cầu nộp lại (`resubmit_required`) hoặc duyệt bài (`approved`) và chấm điểm.

---

## 5. DÀNH CHO SINH VIÊN (`student`)

### 5.1. Tra cứu Đề tài & Đăng ký Nguyện vọng
- **Danh mục đề tài đã duyệt:** Menu **Đề tài thực tập / đồ án** -> `/topics`.
- **Đăng ký nguyện vọng:** Menu **Nguyện vọng của tôi** -> `/wishes`.
  * Chọn tối đa 3 đề tài theo thứ tự ưu tiên (NV1, NV2, NV3).
  * Kiểm tra kỹ thứ tự trước khi lưu nguyện vọng. Sinh viên có thể điều chỉnh hoặc hủy đăng ký khi đợt còn mở cổng.

### 5.2. Tra cứu Kết quả Phân công
- Menu **Kết quả phân công** -> `/assignments/my-result`.
- Khi đợt chưa công bố: Màn hình hiển thị thông báo "Đợt chưa công bố kết quả phân công".
- Khi đợt đã công bố: Màn hình hiển thị đầy đủ tên đề tài chính thức, họ tên giảng viên hướng dẫn, email, số điện thoại liên hệ và nguyện vọng trúng tuyển.

### 5.3. Theo dõi Tiến độ & Nộp Báo cáo
- **Tiến độ của tôi:** Menu **Tiến độ của tôi** -> `/milestones` hiển thị lộ trình các mốc thời gian, hạn chót và trạng thái hoàn thành cá nhân.
- **Nhật ký & Nộp báo cáo:** Viết nhật ký công việc hàng tuần và tải lên tệp báo cáo (.pdf, .docx, .zip) đúng thời hạn quy định.

---

## 6. DÀNH CHO DOANH NGHIỆP TIẾP NHẬN (`enterprise`)

### 6.1. Đề xuất Đề tài Thực tập
- Menu **Đề xuất đề tài** -> `/topics/create`.
- Đăng tải các vị trí thực tập, yêu cầu công nghệ và chỉ tiêu tiếp nhận sinh viên tại công ty.

### 6.2. Tra cứu Sinh viên Thực tập Tiếp nhận
- Menu **Sinh viên thực tập được giao** -> `/assignments/enterprise-students`.
- Tra cứu danh sách các sinh viên được nhà trường phân bổ về thực tập tại đơn vị, hỗ trợ theo dõi chuyên cần và đánh giá kết quả thực tập cuối đợt.
