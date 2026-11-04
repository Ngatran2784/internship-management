# HỢP ĐỒNG GIAO TIẾP & QUY CHUẨN BACKEND QUẢN LÝ NGƯỜI DÙNG

- Tài liệu dành cho: **Nhà phát triển Backend phân hệ Người dùng**
- Phân hệ: **Quản lý Tài khoản & Phân quyền Người dùng (User Management)**
- Môi trường: **PHP thuần MVC, MySQL/MariaDB (XAMPP Port 3306), PDO**

---

## 1. Cấu trúc Bảng CSDL Thực tế

Phân hệ sử dụng 3 bảng nền tảng đã được khởi tạo trong CSDL:

### 1.1. Bảng `users`
```sql
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `avatar` VARCHAR(255) NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_status` (`status`)
);
```

### 1.2. Bảng `roles`
Chứa 6 vai trò cố định: `admin`, `training_dept`, `department_head`, `lecturer`, `student`, `enterprise`.
```sql
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 1.3. Bảng `user_roles`
```sql
CREATE TABLE `user_roles` (
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role_id`),
    CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);
```

---

## 2. Quy chuẩn Dữ liệu và Nghiệp vụ Backend

### 2.1. Các trường được phép thêm / sửa
- **Khi tạo mới tài khoản (`User::create`):**
  - Bắt buộc: `username`, `email`, `password`, `full_name`.
  - Tùy chọn: `phone` (hoặc null), `status` (mặc định 1), `role_ids` (mảng ID vai trò).
- **Khi chỉnh sửa thông tin:**
  - Được phép sửa: `full_name`, `email`, `phone`, `status`.
  - Đổi mật khẩu: chỉ khi người dùng nhập mật khẩu mới (nếu để trống thì giữ nguyên hash cũ).
  - Khóa tài khoản: cập nhật `status = 0` (tài khoản không thể đăng nhập).
  - Không cho phép tự do đổi `username` sau khi đã kích hoạt nghiệp vụ (để đảm bảo tính toàn vẹn vết dữ liệu).

### 2.2. Quy tắc Validation
- **`username`:**
  - Bắt buộc, độ dài từ 3 đến 50 ký tự.
  - Định dạng: `/^[a-zA-Z0-9_\-\.]+$/` (chữ cái, chữ số, gạch ngang, gạch dưới, chấm).
  - Không trùng với tài khoản đã có (kiểm tra `UNIQUE`).
- **`email`:**
  - Bắt buộc, độ dài tối đa 100 ký tự, định dạng email chuẩn qua `filter_var($email, FILTER_VALIDATE_EMAIL)`.
  - Không trùng với tài khoản khác trong hệ thống.
- **`password`:**
  - Khi tạo mới: bắt buộc, tối thiểu 6 ký tự.
  - Bắt buộc băm mật khẩu bằng hàm chuẩn PHP:
    ```php
    $hashedPassword = password_hash($rawPassword, PASSWORD_BCRYPT);
    ```
  - Tuyệt đối không dùng MD5, SHA1 hay lưu mật khẩu dạng văn bản thô (plain text).
- **`phone`:**
  - Tùy chọn, nếu có nhập phải kiểm tra theo định dạng số điện thoại hợp lệ: `/^[0-9+\-\s().]{8,20}$/`.
- **`status`:**
  - Chỉ nhận giá trị số nguyên: `1` (Hoạt động) hoặc `0` (Tạm khóa).

### 2.3. Quy tắc Phân quyền và Gán vai trò
- Một người dùng có thể có 1 hoặc nhiều vai trò cùng lúc (ví dụ: Giảng viên kiêm Trưởng bộ môn).
- Thao tác gán vai trò sử dụng hàm `User::assignRoles(int $userId, array $roleIds)`.
- **Nguyên tắc bảo vệ an toàn hệ thống (Anti-lockout Rule):**
  - Tuyệt đối không cho phép tài khoản Admin tự gỡ vai trò `admin` của chính mình hoặc tự khóa tài khoản của chính mình trong phiên làm việc hiện tại.

---

## 3. Danh sách Tuyến đường (Routes) Chuẩn hóa

Để đảm bảo tương thích 100% với bộ định tuyến hiện tại (`App\Core\Router`), danh sách route của module Người dùng được chuẩn hóa như sau:

| Phương thức | Đường dẫn Route | Tham số Query / Form | Quyền yêu cầu | Chức năng |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/users` | `?role=&keyword=` | `admin` | Hiển thị danh sách người dùng kèm bộ lọc |
| `GET` | `/users/create` | Không | `admin` | Hiển thị form tạo mới tài khoản |
| `POST` | `/users` hoặc `/users/create` | Form POST (`_csrf`, `username`, `email`, `password`, `full_name`, `phone`, `role_ids[]`) | `admin` | Lưu tài khoản mới vào CSDL |
| `GET` | `/users/edit` | `?id={userId}` | `admin` | Hiển thị form cập nhật thông tin tài khoản |
| `POST` | `/users/update` | Form POST (`_csrf`, `id`, `full_name`, `email`, `phone`, `password`, `status`) | `admin` | Cập nhật thông tin tài khoản |
| `POST` | `/users/status` | Form POST (`_csrf`, `id`, `status`) | `admin` | Khóa hoặc mở khóa tài khoản (Active/Locked) |
| `POST` | `/users/roles` | Form POST (`_csrf`, `user_id`, `role_ids[]`) | `admin` | Gán danh sách vai trò cho người dùng |
| `GET` | `/profile` | Không | Đã đăng nhập (`any`) | Xem trang thông tin cá nhân của chính mình |
| `POST` | `/profile` | Form POST (`_csrf`, `full_name`, `phone`) | Đã đăng nhập (`any`) | Cập nhật thông tin cá nhân của chính mình |

> **Quy ước kỹ thuật bắt buộc:**
> 1. Toàn bộ các thao tác `POST` phải được kiểm tra token CSRF qua `AuthController::verifyCsrf()`.
> 2. Tuyệt đối không dùng phương thức `GET` để xóa hoặc thay đổi trạng thái người dùng.
> 3. Kiểm tra quyền tại đầu mỗi phương thức Controller bằng `AuthController::requireRole('admin')` hoặc `AuthController::requireLogin()`.

---

## 4. Cấu trúc Dữ liệu Controller truyền sang View

Khi render View, Controller phải truyền đúng cấu trúc biến đã thống nhất để Frontend hiển thị:

### 4.1. Trang danh sách người dùng (`users/index`)
```php
$this->respond('users/index', [
    'users'     => User::all(), // Mảng danh sách user kèm roles_list (chuỗi) hoặc roles (mảng)
    'roles'     => Role::all(), // Danh mục 6 vai trò phục vụ dropdown/modal
    'filters'   => ['role' => 'student', 'keyword' => ''],
    'success'   => AuthController::pullFlash('success'),
    'error'     => AuthController::pullFlash('error'),
    'csrfToken' => AuthController::csrfToken(),
]);
```

*Lưu ý bảo mật: Mảng `$users` trả ra View tuyệt đối không bao giờ được chứa cột `password`.*

### 4.2. Trang tạo mới (`users/create`) và chỉnh sửa (`users/edit`)
```php
$this->respond('users/edit', [
    'user'      => $user,               // Thông tin người dùng (id, username, email, full_name, phone, status)
    'roles'     => Role::all(),         // Danh mục vai trò để tick chọn checkbox/select2
    'userRoles' => User::getRoles($id), // Mảng tên các vai trò đang có, vd: ['lecturer', 'department_head']
    'errors'    => $errors,             // Mảng chuỗi thông báo lỗi validation nếu có
    'old'       => $oldData,            // Dữ liệu cũ người dùng vừa nhập khi submit lỗi
    'csrfToken' => AuthController::csrfToken(),
]);
```

---

## 5. Quy tắc phối hợp CSDL (Database Coordination)

1. **Không tự ý chạy lệnh phá hủy:** Tuyệt đối không viết lệnh `DROP TABLE`, `TRUNCATE` hay chỉnh sửa cột của bảng `users`, `roles`, `user_roles` khi chưa có sự thống nhất chung của nhóm.
2. **Nếu cần bổ sung cột:** Tạo tệp migration mới đặt trong thư mục `database/migrations/` với định dạng `ALTER TABLE ... ADD COLUMN ...`.
3. **Mã lỗi và phản hồi:** Các lỗi nghiệp vụ phải được đặt vào Session Flash bằng `AuthController::setFlash('error', $message)` và chuyển hướng bằng `AuthController::go('/users')`.
