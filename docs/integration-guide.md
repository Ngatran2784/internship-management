# HƯỚNG DẪN TÍCH HỢP VÀ VẬN HÀNH DỰ ÁN (INTEGRATION GUIDE)

- Dự án: **Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên**
- Ngăn xếp kỹ thuật: **PHP 8.2 thuần MVC, MySQL/MariaDB (PDO), Bootstrap 5, Tabler UI**
- Máy chủ phát triển: **XAMPP (Apache cổng 80/443, MySQL cổng 3306)**

---

## 1. Cài đặt và Chạy dự án trên XAMPP

### 1.1. Vị trí thư mục chuẩn
Mã nguồn phải được đặt đúng thư mục htdocs của XAMPP:
```text
C:\xampp\htdocs\CNPM\internship-management
```
URL truy cập tương ứng trên trình duyệt web:
```text
http://localhost/CNPM/internship-management/public/
```

### 1.2. Khởi động dịch vụ XAMPP
1. Mở **XAMPP Control Panel**.
2. Bật dịch vụ **Apache** (Cổng 80, 443).
3. Bật dịch vụ **MySQL** của XAMPP (Cổng mặc định 3306).
   > **CẢNH BÁO QUAN TRỌNG VỀ PORT MYSQL:**  
   > - Chỉ sử dụng MySQL của XAMPP trên cổng 3306.  
   > - Tuyệt đối không sử dụng dịch vụ MySQL riêng biệt khác (ví dụ MySQL80 cổng 3307) để tránh xung đột tài khoản và cơ sở dữ liệu.

### 1.3. Cấu hình tệp kết nối CSDL cục bộ
Nếu chưa có tệp `config/database.php`, sao chép từ tệp mẫu:
```powershell
Copy-Item config\database.example.php config\database.php
```
Nội dung chuẩn của `config/database.php` trên môi trường XAMPP:
```php
<?php
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'internship_management',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
];
```

---

## 2. Quy trình Cập nhật Cơ sở Dữ liệu (Database Migration)

### 2.1. Khởi tạo CSDL ban đầu (nếu là máy mới)
Chạy lệnh tạo CSDL và import cấu trúc gốc:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS internship_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root -D internship_management -e "SOURCE C:/xampp/htdocs/CNPM/internship-management/database/schema.sql;"
```

### 2.2. Áp dụng Migration cho module Đợt thực tập/đồ án
Chạy tệp migration an toàn:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -D internship_management -e "SOURCE C:/xampp/htdocs/CNPM/internship-management/database/migrations/create_internship_periods.sql;"
```

> **Nguyên tắc an toàn dữ liệu:**
> - Tuyệt đối không chạy lệnh `DROP DATABASE`, `DROP TABLE` hoặc `TRUNCATE`.
> - Toàn bộ migration phải sử dụng `CREATE TABLE IF NOT EXISTS` hoặc `ALTER TABLE` an toàn để không làm mất dữ liệu của các thành viên khác.

### 2.3. Tạo dữ liệu mẫu đăng nhập thử nghiệm (Dev Seeding)
Chạy tệp tạo dữ liệu mẫu (có thể chạy nhiều lần, không bị trùng):
```powershell
C:\xampp\php\php.exe tests/seed_dev.php
```
Tài khoản thử nghiệm sau khi seed:
- Quản trị viên: `admin` / Mật khẩu: `Admin@123` (Vai trò: `admin`)
- Sinh viên: `sv001` / Mật khẩu: `Sv@12345` (Vai trò: `student`)

---

## 3. Quy trình Phát triển Song song trên Git

### 3.1. Phân chia nhánh tính năng (Feature Branches)
Tất cả các thành viên làm việc trên các nhánh riêng rẽ tách từ nhánh chính:
- Nhánh Quản lý người dùng (Backend): `feature/user-management`
- Nhánh Giao diện người dùng (Frontend): `feature/frontend-ui`
- Nhánh Quản lý đợt (Core / Architecture): `feature/project-setup`

Lệnh tạo và chuyển sang nhánh riêng:
```powershell
git checkout -b feature/ten-nhanh-chuc-nang
```

### 3.2. Quy tắc Commit
- Commit theo từng đơn vị công việc rõ ràng, kèm thông điệp súc tích tiếng Việt hoặc tiếng Anh:
  - `feat(period): triển khai model và controller quản lý đợt`
  - `feat(user): bổ sung form cập nhật hồ sơ cá nhân`
  - `ui(period): hoàn thiện view danh sách đợt chuẩn tabler`
- Tuyệt đối không đặt tên nhánh, commit hay route chứa `s2`, `sprint2`, `sprint_2`.
- Không đưa thông tin cá nhân, điểm số hay phân công nội bộ vào commit message.

### 3.3. Các tệp tin dùng chung dễ xảy ra xung đột (Shared Conflict Hotspots)
Khi làm việc song song, các thành viên cần đặc biệt lưu ý và phối hợp khi sửa đổi 3 tệp tin dùng chung sau:
1. **`public/index.php`**: Nơi đăng ký tuyến đường (routes).
   - *Quy tắc phối hợp:* Chỉ thêm route mới vào đúng khu vực phân hệ của mình, không sửa đổi hoặc sắp xếp lại các route của thành viên khác.
2. **`app/Views/layouts/sidebar.php`**: Menu điều hướng bên trái.
   - *Quy tắc phối hợp:* Khi mở khóa menu mới, chỉ chuyển từ `<span class="nav-link disabled">` thành `<a class="nav-link" href="<?= $escape($baseUrl) ?>/duong-dan">`. Không thay đổi cấu trúc phân quyền của các menu khác.
3. **`database/schema.sql`**: Tài liệu DDL toàn hệ thống.
   - *Quy tắc phối hợp:* Không sửa đổi trực tiếp các bảng cũ, chỉ bổ sung lệnh `CREATE TABLE IF NOT EXISTS` mới ở cuối tệp sau khi đã tạo migration thành công.

---

## 4. Quy trình Tích hợp Backend và Frontend

### 4.1. Cơ chế phản hồi linh hoạt (Dual Respond Mechanism)
Cả `UserController` và `InternshipPeriodController` đều đã được tích hợp cơ chế `respond($view, $data)`:
- **Khi View chưa tồn tại (Frontend đang code dở):** Controller tự động xuất kết quả dạng **JSON** kèm mã trạng thái HTTP chuẩn (200, 201, 404, 422). Nhờ đó, Backend có thể kiểm thử toàn bộ API và dữ liệu mà không cần đợi Frontend hoàn thành.
- **Khi View đã được tạo:** Controller tự động gọi `$this->render($view, $data)` để xuất ra trang HTML giao diện Tabler cho người dùng.

### 4.2. Trình tự ghép nối tính năng:
1. **Bước 1:** Backend hoàn thiện Model và Controller, chạy kiểm thử tự động đạt 100% PASS.
2. **Bước 2:** Frontend căn cứ vào tài liệu `docs/frontend-contract.md` để tạo tệp View tương ứng (ví dụ: `app/Views/periods/index.php`).
3. **Bước 3:** Kiểm tra hiển thị thực tế trên trình duyệt: dữ liệu CSDL được nạp đúng vào các bảng, form submit thành công, thông báo flash hiển thị chuẩn xác.
4. **Bước 4:** Kiểm tra an toàn: CSRF token có mặt trong mọi form POST, ký tự đầu ra được escape đầy đủ chống XSS.

---

## 5. Danh mục Kiểm tra trước khi Hợp nhất (Merge Checklist)

Trước khi gửi Pull Request hoặc yêu cầu hợp nhất vào nhánh chính, thành viên bắt buộc kiểm tra các tiêu chí sau:

- [ ] **1. Cú pháp PHP:** Chạy `php -l` trên toàn bộ tệp mới hoặc đã sửa đổi, không có lỗi Parse error.
- [ ] **2. Kiểm thử tự động:**
  - `php tests/test_core.php` $\to$ Đạt **20/20 PASS**.
  - `php tests/test_auth.php` $\to$ Đạt **44/44 PASS**.
  - `php tests/test_internship_periods.php` $\to$ Đạt **26/26 PASS**.
- [ ] **3. An toàn CSDL:** Migration không chứa lệnh DROP/TRUNCATE; không làm mất dữ liệu cũ.
- [ ] **4. Bảo mật CSRF:** Mọi form POST đều có trường ẩn `_csrf` và được Controller xác thực qua `AuthController::verifyCsrf()`.
- [ ] **5. Phân quyền:** Các chức năng quản trị đều có `AuthController::requireRole(...)` tại tầng Backend.
- [ ] **6. Không phá vỡ Sprint 1:** Đăng nhập, đăng xuất, phân quyền admin/student và giao diện Dashboard cũ vẫn hoạt động trơn tru.
