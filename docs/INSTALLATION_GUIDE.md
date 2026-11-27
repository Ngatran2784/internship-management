# HƯỚNG DẪN CÀI ĐẶT VÀ TRIỂN KHAI HỆ THỐNG (INSTALLATION GUIDE)
## DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN

Tài liệu hướng dẫn triển khai hệ thống từ đầu trên môi trường máy chủ cục bộ hoặc máy chủ mới.

---

## 1. YÊU CẦU MÔI TRƯỜNG MÁY CHỦ (SYSTEM PREREQUISITES)

- **Hệ điều hành:** Windows 10/11, macOS hoặc Linux (Ubuntu 20.04+).
- **Môi trường đề xuất:** Gói phần mềm **XAMPP 8.2.x** (hoặc ngăn xếp tương đương LAMP/WAMP):
  * **PHP:** Phiên bản **8.2.0** trở lên.
  * **Các tiện ích mở rộng PHP bắt buộc:** `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `gd`.
  * **Cơ sở dữ liệu:** **MySQL 5.7+** hoặc **MariaDB 10.4+** trên cổng mặc định **3306**.
  * **Web Server:** **Apache 2.4+** kích hoạt module `mod_rewrite`.

---

## 2. CÁC BƯỚC CÀI ĐẶT CHI TIẾT (STEP-BY-STEP SETUP)

### Bước 1: Sao chép mã nguồn dự án
Đặt toàn bộ thư mục dự án vào thư mục `htdocs` của XAMPP:
```text
C:\xampp\htdocs\CNPM\internship-management
```

### Bước 2: Cấu hình kết nối Cơ sở dữ liệu
Nếu chưa có tệp `config/database.php`, sao chép từ tệp mẫu `config/database.example.php`:
```powershell
Copy-Item config\database.example.php config\database.php
```

Mở tệp [config/database.php](file:///c:/xampp/htdocs/CNPM/internship-management/config/database.php) và điều chỉnh thông số kết nối:
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

### Bước 3: Khởi tạo Cơ sở dữ liệu
Mở Terminal / PowerShell và thực thi các câu lệnh sau để tạo database và import toàn bộ cấu trúc:

```powershell
# 1. Tạo Database với bảng mã UTF-8 đầy đủ
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS internship_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import CSDL tổng hợp nền tảng
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root internship_management < database/internship_management.sql

# 3. Chạy Migration tạo các bảng Mốc tiến độ, Nhật ký và Báo cáo
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root internship_management < database/migrations/create_milestones_and_reports_tables.sql

# 4. Nạp Dữ liệu mẫu phục vụ vận hành và kiểm thử
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -u root internship_management < database/seeds/milestones_and_reports_sample_data.sql
```

### Bước 4: Cấp quyền thư mục Uploads
Đảm bảo thư mục lưu trữ tệp báo cáo sinh viên tồn tại và có quyền ghi (Read/Write):
```powershell
# Tạo thư mục uploads/reports nếu chưa có
New-Item -ItemType Directory -Force -Path public\uploads\reports
```

---

## 3. DANH SÁCH TÀI KHOẢN MẪU (DEFAULT DEMO ACCOUNTS)

Mật khẩu mặc định cho toàn bộ các tài khoản mẫu dưới đây là: `password` (hoặc `Admin@123` cho tài khoản `admin`):

| Vai trò | Tên đăng nhập | Email | Mật khẩu |
| :--- | :--- | :--- | :--- |
| **Quản trị hệ thống** | `admin` | `admin@vinhuni.edu.vn` | `Admin@123` hoặc `password` |
| **Phòng Đào tạo** | `pdt001` | `pdt001@vinhuni.edu.vn` | `password` |
| **Trưởng bộ môn** | `tbm001` | `tbm001@vinhuni.edu.vn` | `password` |
| **TBM kiêm Giảng viên** | `tbm_gv` | `tbm_gv@vinhuni.edu.vn` | `password` |
| **Giảng viên hướng dẫn** | `gv001` | `gv001@vinhuni.edu.vn` | `password` |
| **Sinh viên** | `sv001` | `sv001@vinhuni.edu.vn` | `password` |
| **Doanh nghiệp** | `dn001` | `contact@fpt.com` | `password` |

---

## 4. XÁC MINH CÀI ĐẶT BẰNG BỘ KIỂM THỬ TỰ ĐỘNG (AUTOMATED VERIFICATION)

Sau khi cài đặt xong, bạn có thể chạy các bộ kiểm thử tự động để bảo đảm toàn bộ hệ thống hoạt động hoàn hảo:

```powershell
# 1. Kiểm thử xác minh di chuyển CSDL sạch
php tests/test_clean_migration.php

# 2. Kiểm thử toàn diện chức năng cốt lõi (21 ca kiểm thử)
php tests/test_core_features_acceptance.php

# 3. Kiểm thử tiến độ, upload báo cáo, phân quyền và hạn nộp (33 checks)
php tests/test_progress_and_reports.php

# 4. Kiểm thử thuật toán đối sánh phân công đề tài (83 checks)
php tests/test_assignment_matching.php
```

---

## 5. XỬ LÝ SỰ CỐ PHỔ BIẾN (TROUBLESHOOTING)

1. **Lỗi `SQLSTATE[HY000] [2002] Connection refused`:**
   - Nguyên nhân: Dịch vụ MySQL của XAMPP chưa được bật.
   - Khắc phục: Mở XAMPP Control Panel và bấm **Start** tại mục MySQL (Cổng 3306).
2. **Lỗi `404 Not Found` khi bấm vào các liên kết con:**
   - Nguyên nhân: Apache chưa bật `mod_rewrite` hoặc tệp `.htaccess` bị thiếu.
   - Khắc phục: Kiểm tra tệp [public/.htaccess](file:///c:/xampp/htdocs/CNPM/internship-management/public/.htaccess) và đảm bảo `AllowOverride All` được cấu hình trong `httpd.conf` của Apache.
3. **Lỗi phông chữ tiếng Việt có dấu khi import CSDL qua lệnh:**
   - Nguyên nhân: Client MySQL dùng sai bảng mã console.
   - Khắc phục: Luôn đính kèm cờ `--default-character-set=utf8mb4` khi thực thi lệnh import qua CLI.
