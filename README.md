# Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên

Dự án môn học: **Công nghệ Phần mềm (CNPM)**  
Mô hình phát triển: **Agile / Scrum - Sprint 1**  
Nhóm thực hiện: **3 thành viên 

---

## 1. Giới thiệu dự án

Hệ thống được xây dựng nhằm tin học hóa và tối ưu toàn bộ quy trình quản lý thực tập doanh nghiệp và đồ án tốt nghiệp của sinh viên, bao gồm:
- Quản trị người dùng và phân quyền đa vai trò (Quản trị viên, Phòng đào tạo, Trưởng bộ môn, Giảng viên, Sinh viên, Doanh nghiệp).
- Quản lý các đợt thực tập / đồ án theo học kỳ và năm học.
- Quản lý đề tài, đăng ký nguyện vọng và ghép cặp tự động/thủ công.
- Theo dõi tiến độ nộp báo cáo định kỳ, kế hoạch công việc và nhật ký thực tập.
- Đánh giá năng lực theo bộ tiêu chí Rubric minh bạch và đa chiều.
- Báo cáo thống kê trực quan.

---

## 2. Công nghệ sử dụng

- **Ngôn ngữ lập trình:** PHP thuần 8.2 (Không sử dụng framework như Laravel, Symfony, CodeIgniter...).
- **Cơ sở dữ liệu:** MySQL / MariaDB (tích hợp trên XAMPP, cổng mặc định 3306).
- **Tương tác CSDL:** Thư viện chuẩn `PDO` (hỗ trợ Prepared Statements chống SQL Injection).
- **Kiến trúc phần mềm:** Mô hình MVC (Model - View - Controller) tối giản, hướng đối tượng (OOP), chuẩn Autoloader PSR-4.
- **Giao diện người dùng:** HTML5, CSS3, JavaScript, Tabler Bootstrap 5 (tích hợp bởi TV3).
- **Quản lý mã nguồn:** Git & GitHub theo mô hình Git Flow rút gọn.

---

## 3. Cấu trúc thư mục MVC

```text
internship-management/
├── app/
│   ├── Core/                  # Các lớp cốt lõi của framework (TV1)
│   │   ├── Database.php       # Quản lý kết nối PDO MySQL an toàn
│   │   ├── Router.php         # Bộ định tuyến GET/POST & xử lý 404
│   │   └── Controller.php     # Base Controller hỗ trợ render, redirect, escape XSS
│   ├── Controllers/           # Các Controller tiếp nhận nghiệp vụ (TV2)
│   │   ├── AuthController.php # Khung đăng nhập, đăng xuất, phân quyền
│   │   └── UserController.php # Khung quản lý tài khoản và hồ sơ
│   ├── Models/                # Các Model truy vấn dữ liệu (TV2)
│   │   ├── User.php           # Thao tác bảng users
│   │   └── Role.php           # Thao tác bảng roles
│   └── Views/                 # Giao diện người dùng (TV3)
│       ├── layouts/           # Thành phần khung giao diện
│       │   ├── header.php     # Header & nhúng Tabler CSS
│       │   ├── sidebar.php    # Menu điều hướng theo vai trò
│       │   └── footer.php     # Footer & nhúng Tabler JS
│       ├── auth/
│       │   └── login.php      # Giao diện form đăng nhập
│       └── dashboard/
│           └── index.php      # Màn hình Dashboard tổng quan
├── config/
│   ├── database.example.php   # File cấu hình CSDL mẫu
│   └── database.php           # Cấu hình CSDL cục bộ (được bỏ qua bởi .gitignore)
├── database/
│   └── schema.sql             # Kịch bản khởi tạo bảng CSDL (roles, users, user_roles)
├── docs/                      # Tài liệu thiết kế hệ thống
│   ├── database-design.md     # Bản vẽ ERD & mô tả chi tiết các bảng
│   ├── git-workflow.md        # Hướng dẫn phân nhánh Git & quy trình PR
│   └── sprint-1.md            # Phân công công việc & tiêu chí nghiệm thu Sprint 1
├── public/                    # Thư mục gốc công khai của Web Server (Entry Point)
│   ├── assets/                # Chứa file tĩnh (CSS, JS, Fonts, Images của Tabler)
│   ├── .htaccess              # Cấu hình Apache mod_rewrite & tắt directory browsing
│   └── index.php              # Front Controller duy nhất tiếp nhận mọi request
├── tests/                     # Kịch bản kiểm thử tự động
│   └── test_core.php          # Kiểm tra cú pháp PHP, Autoload và Router
├── .gitignore                 # Danh mục loại trừ tệp nhạy cảm và tệp rác
└── README.md                  # Hướng dẫn tổng thể dự án
```

---

## 4. Hướng dẫn cài đặt và chạy trên XAMPP

### Yêu cầu hệ thống:
- XAMPP cài đặt tại `C:\xampp` (PHP 8.2 trở lên).
- Dịch vụ **Apache** và **MySQL XAMPP** (Cổng 3306) đang hoạt động.

### Các bước cài đặt:
1. **Kiểm tra vị trí dự án:**
   Thư mục dự án nằm chính xác tại:
   `C:\xampp\htdocs\CNPM\internship-management`

2. **Cấu hình Cơ sở dữ liệu:**
   - Sao chép file `config/database.example.php` thành `config/database.php`.
   - Mở `config/database.php` và xác nhận thông số kết nối:
     ```php
     return [
         'host'     => '127.0.0.1',
         'port'     => 3306,
         'dbname'   => 'internship_management',
         'username' => 'root',
         'password' => '',
         'charset'  => 'utf8mb4',
     ];
     ```

3. **Khởi tạo Database trên MySQL (khi được phê duyệt):**
   - Mở phpMyAdmin tại `http://localhost/phpmyadmin/` hoặc dùng MySQL CLI.
   - Tạo cơ sở dữ liệu mới: `internship_management` (Collation: `utf8mb4_unicode_ci`).
   - Nhập nội dung từ file `database/schema.sql` để tạo 3 bảng nền tảng `roles`, `users`, `user_roles`.

4. **Truy cập ứng dụng:**
   Mở trình duyệt web và truy cập đường dẫn:
   ```text
   http://localhost/CNPM/internship-management/public/
   ```
   Nếu màn hình hiển thị bảng trạng thái màu xanh **"Sprint 1: Kiến trúc MVC Thuần & Khung Hệ thống"**, hệ thống đã cài đặt thành công!

---

## 5. Phân công vai trò thành viên

| Thành viên | Vai trò | Trách nhiệm chính trong Sprint 1 |
| :--- | :--- | :---: | :--- |
| **TV1 (Nhóm trưởng)** | Software Architect  | Thiết lập kiến trúc MVC, Autoloader, Core Router, Database PDO, Controller, thiết kế ERD, viết schema.sql và hệ thống tài liệu. |
| **TV2** | Backend Developer  | Xây dựng logic xác thực người dùng (`AuthController`), tìm kiếm & phân quyền (`UserController`), hoàn thiện `User` và `Role` Models. |
| **TV3** | Frontend Developer | Tải và tích hợp giao diện Tabler Bootstrap 5, hoàn thiện các View layouts (`header`, `sidebar`, `footer`), view đăng nhập và view dashboard. |
