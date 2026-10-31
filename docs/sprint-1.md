# KẾ HOẠCH & TIÊU CHÍ NGHIỆM THU SPRINT 1

Dự án: **Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên**  
Mô hình phát triển: **Agile / Scrum - Sprint 1: Nền tảng Kiến trúc, Xác thực & Giao diện Tabler**

---

## 1. Phân chia Module & Quyền sở hữu Tệp tin (Module Ownership)

Để tránh xung đột mã nguồn khi phát triển song song, dự án phân chia ranh giới module rõ ràng:

| Module chức năng | Trách nhiệm chính | Phạm vi tệp tin quản lý |
| :--- | :--- | :--- |
| **Core Architecture & Engine** | Khung MVC, Autoloader, Router, Database PDO, Schema DDL | - `app/Core/*` (`Database.php`, `Router.php`, `Controller.php`)<br>- `public/index.php`, `public/.htaccess`<br>- `config/database.example.php`, `.gitignore`<br>- `database/schema.sql`<br>- `README.md`, `docs/*` |
| **Backend & Authentication** | Logic xác thực, phân quyền RBAC, kiểm tra phiên, Models | - `app/Controllers/AuthController.php`<br>- `app/Controllers/UserController.php`<br>- `app/Models/User.php`<br>- `app/Models/Role.php` |
| **Frontend UI & Tabler Integration** | Tích hợp Tabler Bootstrap 5, Layouts, Dashboard, Login UI | - `public/assets/*` (CSS, JS, Fonts của Tabler)<br>- `app/Views/layouts/header.php`<br>- `app/Views/layouts/sidebar.php`<br>- `app/Views/layouts/footer.php`<br>- `app/Views/auth/login.php`<br>- `app/Views/dashboard/index.php` |

---

## 2. Chi tiết công việc thực hiện trong Sprint 1

### A. Nền tảng Kiến trúc & Cơ sở Dữ liệu
- [x] Thiết lập cấu trúc thư mục MVC chuẩn cho PHP thuần 8.2.
- [x] Viết lớp `Database.php` kết nối MySQL bằng PDO (an toàn mật khẩu, chuẩn UTF-8 mb4, bắt exception không làm lộ credentials).
- [x] Viết lớp `Router.php` hỗ trợ GET/POST, tự động bóc tách URI trên môi trường XAMPP subfolder, trang 404 thân thiện.
- [x] Viết lớp `Controller.php` hỗ trợ `render()`, `redirect()` và `escape()` phòng chống tấn công XSS.
- [x] Viết `public/index.php` đóng vai trò Front Controller duy nhất với cơ chế PSR-4 autoloader.
- [x] Thiết kế ERD tổng thể và xây dựng `database/schema.sql` với các bảng nền tảng `roles`, `users`, `user_roles` (InnoDB, utf8mb4, đầy đủ PK, FK, INDEX).
- [x] Viết tài liệu dự án: `README.md`, `database-design.md`, `git-workflow.md`, `sprint-1.md`.
- [x] Thiết lập bộ kiểm thử cú pháp và hoạt động của Core framework.

### B. Backend Xác thực & Phân quyền
- [x] Triển khai phương thức `AuthController@showLoginForm` (kiểm tra session và gọi view đăng nhập).
- [x] Triển khai phương thức `AuthController@login` (nhận POST, validate, đối soát hash Bcrypt với `User::findByUsernameOrEmail`, bảo vệ CSRF token, lưu session đăng nhập và vai trò).
- [x] Triển khai phương thức `AuthController@logout` (hủy session và redirect an toàn về trang login).
- [x] Hoàn thiện `app/Models/User.php` với các hàm truy vấn `findById`, `findByUsernameOrEmail`, `getRoles`, `create`, `updateProfile`.
- [x] Hoàn thiện `app/Models/Role.php` với các hàm truy vấn danh sách vai trò hệ thống.
- [x] Đăng ký các route xác thực (`GET /login`, `POST /login`, `POST /logout`, `GET /dashboard`) vào `public/index.php`.

### C. Frontend & Tích hợp Giao diện Tabler UI
- [x] Tải và tích hợp thư viện Tabler Bootstrap 5 vào `public/assets/tabler/`.
- [x] Hoàn thiện `app/Views/layouts/header.php` với Navbar chuẩn Tabler và đồng bộ Session.
- [x] Hoàn thiện `app/Views/layouts/sidebar.php` hiển thị menu định hướng phân quyền chuyên biệt cho Quản trị viên và Sinh viên.
- [x] Hoàn thiện `app/Views/layouts/footer.php` và nhúng script Tabler JS nội bộ.
- [x] Thiết kế giao diện form đăng nhập chuyên nghiệp trong `app/Views/auth/login.php` với trường token ẩn `_csrf`.
- [x] Thiết kế giao diện tổng quan trong `app/Views/dashboard/index.php` với khối chào mừng cá nhân hóa theo vai trò.

---

## 3. Tiêu chí Nghiệm thu Sprint 1 (Acceptance Criteria)

Sprint 1 được nghiệm thu đạt yêu cầu khi:
1. **Kiến trúc & Hạ tầng**:
   - Toàn bộ file PHP biên dịch không có lỗi cú pháp (`php -l`).
   - Autoloader tự động nạp chính xác các class thuộc namespace `App\Core`, `App\Controllers`, `App\Models`.
   - Router xử lý đúng phương thức GET/POST và trả về HTTP 404 khi truy cập đường dẫn không tồn tại.
   - Kết nối cơ sở dữ liệu PDO thông qua `Database::getConnection()` thành công mà không để lộ chuỗi kết nối trong log lỗi.
   - Khách chưa đăng nhập truy cập `/` hoặc `/dashboard` được chuyển hướng 302 an toàn về `/login`.
2. **Nghiệp vụ Đăng nhập & Xác thực**:
   - Đăng nhập đúng tài khoản/mật khẩu chuyển hướng vào `/dashboard`.
   - Đăng nhập sai tài khoản/mật khẩu hiển thị thông báo lỗi an toàn (timing-safe).
   - Đăng xuất bằng `POST /logout` hủy session hoàn toàn.
3. **Giao diện Tabler UI & Phân quyền**:
   - Giao diện đăng nhập và Dashboard sử dụng chuẩn CSS Tabler, responsive mượt mà trên desktop và mobile.
   - Quản trị viên thấy 8 menu quản trị; Sinh viên chỉ thấy 7 menu sinh viên, tuyệt đối không thấy menu quản trị.
   - Không bị lỗi 404 tài nguyên CSS/JS.
