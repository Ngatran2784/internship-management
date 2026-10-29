# KẾ HOẠCH & TIÊU CHÍ NGHIỆM THU SPRINT 1

Dự án: **Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên**  
Quy mô nhóm: **3 thành viên**

---

## 1. Phân công Trách nhiệm & Quyền sở hữu Tệp tin (File Ownership)

Để tránh xung đột mã nguồn (Merge Conflict) khi làm việc song song trên Git, các thành viên tuân thủ nghiêm ngặt ranh giới sở hữu tệp tin:

| Thành viên | Trách nhiệm chính | Quyền sở hữu tệp tin (File Ownership) |
| :--- | :--- | :--- |
| **TV1 (Nhóm trưởng)** | Software Architect & Core Engine | - `app/Core/*` (`Database.php`, `Router.php`, `Controller.php`)<br>- `public/index.php`, `public/.htaccess`<br>- `config/database.example.php`, `.gitignore`<br>- `database/schema.sql`<br>- `README.md`, `docs/*` |
| **TV2 (Thành viên 2)** | Backend Logic & Auth / Users | - `app/Controllers/AuthController.php`<br>- `app/Controllers/UserController.php`<br>- `app/Models/User.php`<br>- `app/Models/Role.php` |
| **TV3 (Thành viên 3)** | Frontend UI & Tabler Integration | - `public/assets/*` (CSS, JS, Fonts của Tabler)<br>- `app/Views/layouts/header.php`<br>- `app/Views/layouts/sidebar.php`<br>- `app/Views/layouts/footer.php`<br>- `app/Views/auth/login.php`<br>- `app/Views/dashboard/index.php` |

---

## 2. Chi tiết công việc từng thành viên trong Sprint 1

### A. TV1 (Nhóm trưởng) - Xây dựng Khung kiến trúc và Nền tảng
- [x] Thiết lập cấu trúc thư mục MVC chuẩn cho PHP thuần 8.2.
- [x] Viết lớp `Database.php` kết nối MySQL bằng PDO (an toàn mật khẩu, chuẩn UTF-8 mb4, bắt exception không làm lộ credentials).
- [x] Viết lớp `Router.php` hỗ trợ GET/POST, tự động bóc tách URI trên môi trường XAMPP subfolder, trang 404 thân thiện.
- [x] Viết lớp `Controller.php` hỗ trợ `render()`, `redirect()` và `escape()` phòng chống tấn công XSS.
- [x] Viết `public/index.php` đóng vai trò Front Controller duy nhất với cơ chế PSR-4 autoloader.
- [x] Thiết kế ERD tổng thể và xây dựng `database/schema.sql` với các bảng nền tảng `roles`, `users`, `user_roles` (InnoDB, utf8mb4, đầy đủ PK, FK, INDEX).
- [x] Khởi tạo các file khung (scaffolding) với ghi chú TODO cụ thể cho TV2 và TV3.
- [x] Viết tài liệu dự án: `README.md`, `database-design.md`, `git-workflow.md`, `sprint-1.md`.
- [x] Thiết lập bộ kiểm thử cú pháp và hoạt động của Core framework.

### B. TV2 - Backend Xác thực & Quản lý Người dùng
- [ ] Task #101: Triển khai phương thức `AuthController@showLoginForm` (kiểm tra session và gọi view đăng nhập).
- [ ] Task #102: Triển khai phương thức `AuthController@login` (nhận POST, validate, đối soát hash Bcrypt với `User::findByUsernameOrEmail`, lưu session đăng nhập và vai trò).
- [ ] Task #103: Triển khai phương thức `AuthController@logout` (hủy session và redirect về trang login).
- [ ] Task #104: Hoàn thiện `app/Models/User.php` với các hàm truy vấn `findById`, `findByUsernameOrEmail`, `getRoles`, `create`.
- [ ] Task #105: Hoàn thiện `app/Models/Role.php` với các hàm truy vấn danh sách vai trò hệ thống.
- [ ] Task #106: Đăng ký các route xác thực (`GET /login`, `POST /login`, `GET /logout`) vào `public/index.php` hoặc hệ thống routes.

### C. TV3 - Frontend & Tích hợp Giao diện Tabler UI
- [ ] Task #201: Tải và tích hợp thư viện Tabler Bootstrap 5 vào `public/assets/tabler/`.
- [ ] Task #202: Hoàn thiện `app/Views/layouts/header.php` với Navbar chuẩn Tabler.
- [ ] Task #203: Hoàn thiện `app/Views/layouts/sidebar.php` hiển thị menu định hướng cho từng vai trò.
- [ ] Task #204: Hoàn thiện `app/Views/layouts/footer.php` và nhúng script Tabler JS.
- [ ] Task #205: Thiết kế giao diện form đăng nhập chuyên nghiệp trong `app/Views/auth/login.php`.
- [ ] Task #206: Thiết kế giao diện tổng quan trong `app/Views/dashboard/index.php` với các widget thống kê.

---

## 3. Tiêu chí Nghiệm thu Sprint 1 (Acceptance Criteria)

Sprint 1 được nghiệm thu đạt yêu cầu khi:
1. **Kiến trúc & Hạ tầng (TV1)**:
   - Toàn bộ file PHP biên dịch không có lỗi cú pháp (`php -l`).
   - Autoloader tự động nạp chính xác các class thuộc namespace `App\Core`, `App\Controllers`, `App\Models`.
   - Router xử lý đúng phương thức GET/POST và trả về HTTP 404 khi truy cập đường dẫn không tồn tại.
   - Kết nối cơ sở dữ liệu PDO thông qua `Database::getConnection()` thành công mà không để lộ chuỗi kết nối trong log lỗi.
   - Truy cập trang chủ `http://localhost/CNPM/internship-management/public/` hiển thị bảng trạng thái hệ thống màu xanh.
2. **Nghiệp vụ Đăng nhập & Xác thực (TV2)**:
   - Người dùng đăng nhập đúng tài khoản/mật khẩu thì chuyển hướng vào Dashboard.
   - Đăng nhập sai tài khoản/mật khẩu hiển thị thông báo lỗi rõ ràng.
   - Đăng xuất thành công thì xóa toàn bộ session và quay về màn hình đăng nhập.
3. **Giao diện Tabler UI (TV3)**:
   - Giao diện đăng nhập và Dashboard sử dụng đúng bộ CSS Tabler, responsive mượt mà trên desktop và mobile.
   - Không bị lỗi 404 hình ảnh, CSS hoặc JavaScript trên console.
