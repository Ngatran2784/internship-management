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

### 2.4. Áp dụng migration phân công đề tài
Nếu `/assignments` báo `Table 'internship_management.topic_assignments' doesn't exist`,
cơ sở dữ liệu ứng dụng đang kết nối chưa có bảng kết quả phân công. Sau khi các bảng
`users`, `internship_periods` và `topics` đã được tạo, chạy migration bổ sung:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -D internship_management -e "SOURCE C:/xampp/htdocs/internship-management/database/migrations/create_topic_assignments_table.sql;"
```

Migration dùng `CREATE TABLE IF NOT EXISTS`, không xóa dữ liệu. Kiểm tra kết quả bằng:
```powershell
C:\xampp\mysql\bin\mysql.exe -u root -D internship_management -e "SHOW TABLES LIKE 'topic_assignments';"
```
Chỉ chạy lệnh trên đúng MySQL/port và database đang khai báo trong `config/database.php`
(thông báo lỗi hiện tại ghi database `internship_management`). Nếu máy dùng đường dẫn cài
dự án khác, thay đường dẫn sau `SOURCE` bằng đường dẫn migration thực tế. Trước khi chạy,
xác nhận các bảng `users`, `internship_periods` và `topics` đã tồn tại và sao lưu database.
Nếu `topic_assignments` đã tồn tại, kiểm tra `SHOW CREATE TABLE topic_assignments;` thay vì
coi `CREATE TABLE IF NOT EXISTS` là cách sửa một bảng có cấu trúc sai. Không chạy lại
`schema.sql` hoặc SQL tổng chỉ để xử lý thiếu riêng bảng này.

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

### 4.3. Hợp đồng tích hợp Sprint 4 — Phân công
Route hiện có trong `public/index.php`:
- `GET /assignments` → `TopicAssignmentController::index` (cần đăng nhập; giao diện dành
  cho `admin`, `training_dept`, `department_head`, `faculty_staff`, nhưng hiện Backend chưa
  chặn nhóm vai trò khác trước khi truy vấn/xuất dữ liệu).
- `POST /assignments/auto-match` → `TopicAssignmentController::autoMatch` (cùng nhóm quyền,
  yêu cầu `_csrf`; chạy đối sánh và lưu bản dự thảo).
- `GET /assignments/preview` → mô phỏng đối sánh, trả JSON cho nhóm quyền quản lý.

Lưu ý phân quyền cần xử lý ở Backend: `index()` hiện chỉ gán `isManager` cho View nhưng
vẫn truy vấn các bản ghi và đưa chúng vào phản hồi JSON của request đăng nhập không thuộc
nhóm quản lý. Cảnh báo/ẩn bảng trong View không phải kiểm soát truy cập; Backend cần từ
chối trước khi truy vấn hoặc trả dữ liệu cho vai trò không có quyền.

`GET /assignments` truyền các biến `periods`, `currentPeriod`, `assignments`, `unassigned`,
`statistics`, `approvedTopics`, `filters`, `isManager`, `statusLabels`, `statusBadges`,
`methodLabels`, `methodBadges`, `csrf_token`, `success` và `error`. Mỗi dòng `assignments`
có `student_code`, `student_name`, `topic_name`, `lecturer_name`, `matched_wish_priority`,
`assignment_method`, `status` cùng các mã/chi tiết liên quan; `unassigned` có
`student_code`, `student_name`, `wishes_count`. Các biến này đủ để hiển thị người trúng
NV1/NV2/NV3, nhưng **không** đủ để liệt kê cả ba nguyện vọng và tên các đề tài đã chọn.
`TopicWish` lưu `priority`, `topic_id`, `topic_code`, `topic_name`; cần Backend cung cấp
danh sách các nguyện vọng theo `period_id` và `student_id` cho toàn bộ sinh viên trong
đợt (ví dụ map `wishesByStudent[student_id][priority]`). Không được suy ra NV1–NV3 từ
`wishes_count` hoặc chỉ từ `matched_wish_priority`.

Các màn hình chưa có route/controller để kết nối:
- Tạo thủ công: cần GET nạp `periods`, sinh viên hợp lệ, đề tài đã duyệt/còn chỉ tiêu,
  giảng viên hợp lệ, `csrf_token`; POST phải xác thực quyền/CSRF và trả lại `errors`/`old`
  hoặc flash khi thất bại. Hiện chưa có `GET/POST /assignments/create` (hoặc route tương đương).
- Xác nhận/công bố: cần route POST có quyền quản lý, CSRF và kết quả thao tác. Model có
  `publishPeriodAssignments`, nhưng hiện không có controller action/route, nên giao diện
  không được tự gửi tới phương thức Model.
- Sinh viên hướng dẫn: cần route/controller chỉ lấy `TopicAssignment::getByLecturer` với
  `lecturerId` lấy từ session, không lấy từ query; phương thức hiện tại yêu cầu `periodId`,
  nên route cần giới hạn một đợt hoặc Backend cần tổng hợp kết quả qua các đợt được yêu cầu
  trong View. Cần nối mã/tên đợt và yêu cầu quyền `lecturer`. View tự lọc `lecturer_id` để
  tránh render nhầm, nhưng không thay thế kiểm tra phân quyền và giới hạn truy vấn ở Backend.
- Kết quả của sinh viên: Model có `TopicAssignment::getByStudent($studentId, $periodId, true)`
  trả về bản ghi đã công bố cùng tên đề tài, giảng viên, đợt; tuy nhiên chưa có route/action.
  Khi tích hợp, Backend phải lấy `$studentId` từ session, bắt buộc vai trò `student` và gọi
  với `onlyPublished=true`. Chưa tạo view `student_result.php` để tránh một màn hình không
  có điểm vào/luồng nghiệp vụ thực tế.

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

---

## 6. Hợp đồng Dữ liệu Sprint 4: Phân hệ Phân công Đề tài & Giảng viên (Data Contract)

### 6.1. Bản đồ Tuyến đường (Route Mapping)

#### A. Tuyến đường Backend đã triển khai hoàn chỉnh:
| Phương thức | Tuyến đường (Route) | Quyền hạn (RBAC) | Chức năng chính |
| :--- | :--- | :--- | :--- |
| `GET` | `/assignments` | Quản trị / Quản lý / Bộ môn | Xem danh sách phân công theo đợt, hỗ trợ lọc theo đợt, trạng thái, giảng viên, từ khóa. Phản hồi Tabler HTML hoặc JSON. |
| `GET` | `/assignments/preview` | Quản trị / Quản lý / Bộ môn | Chạy mô phỏng (Dry-run) thuật toán đối sánh tự động không ghi vào CSDL. Phản hồi JSON. |
| `POST` | `/assignments/auto-match` | Quản trị / Quản lý / Bộ môn | Chạy thuật toán đối sánh tự động 3 vòng NV1 $\to$ NV2 $\to$ NV3 và ghi bản ghi `status = 'draft'` vào CSDL. |

#### B. Tuyến đường cần bổ sung trong giai đoạn hoàn thiện phân hệ:
| Phương thức | Tuyến đường (Route) | Quyền hạn (RBAC) | Chức năng dự kiến |
| :--- | :--- | :--- | :--- |
| `GET` | `/assignments/create` | Quản trị / Quản lý / Bộ môn | Form giao diện phân công thủ công cho sinh viên chưa có đề tài. |
| `POST` | `/assignments/manual` | Quản trị / Quản lý / Bộ môn | Lưu phân công thủ công qua `TopicAssignment::assignManual()`. |
| `POST` | `/assignments/publish` | Quản trị / Quản lý / Bộ môn | Công bố kết quả phân công cho sinh viên và giảng viên xem (`status = 'published'`). |
| `POST` | `/assignments/unpublish` | Quản trị / Quản lý / Bộ môn | Rút lại công bố kết quả (`status = 'draft'`) để điều chỉnh tiếp. |
| `POST` | `/assignments/cancel` | Quản trị / Quản lý / Bộ môn | Hủy phân công của một sinh viên (`status = 'cancelled'`). |
| `GET` | `/assignments/my-result` | Sinh viên (`student`) | Sinh viên xem đề tài và giảng viên hướng dẫn của mình (chỉ khi đã công bố). |
| `GET` | `/assignments/my-students`| Giảng viên (`lecturer`) | Giảng viên xem danh sách sinh viên mình hướng dẫn trong đợt (chỉ khi đã công bố). |

---

### 6.2. Quy chuẩn Tên cột trong Cơ sở Dữ liệu

| Bảng CSDL | Tên cột thực tế | Kiểu dữ liệu | Ý nghĩa nghiệp vụ | Lưu ý kỹ thuật |
| :--- | :--- | :--- | :--- | :--- |
| **`topic_assignments`** | **`lecturer_id`** | `INT UNSIGNED NOT NULL` | Giảng viên hướng dẫn chính thức | FK $\to$ `users(id)`. **Hệ thống KHÔNG sử dụng `supervisor_id`**. |
| **`topic_assignments`** | `student_id` | `INT UNSIGNED NOT NULL` | Sinh viên được phân công | FK $\to$ `users(id)`. |
| **`topic_assignments`** | `topic_id` | `INT UNSIGNED NOT NULL` | Đề tài được phân công | FK $\to$ `topics(id)`. |
| **`topic_assignments`** | `period_id` | `INT UNSIGNED NOT NULL` | Đợt thực tập / đồ án | FK $\to$ `internship_periods(id)`. |
| **`topic_assignments`** | `status` | `ENUM('draft','published','cancelled')` | Trạng thái phân công | Mặc định `draft`. |
| **`topic_assignments`** | `assignment_method` | `ENUM('auto','manual')` | Phương thức phân công | Tự động hoặc thủ công. |
| **`topics`** | **`proposer_id`** | `INT UNSIGNED NOT NULL` | Người đề xuất / tác giả đề tài | FK $\to$ `users(id)`. **Bảng `topics` KHÔNG có cột `lecturer_id` hay `supervisor_id`**. |
| **`topics`** | `max_students` | `INT UNSIGNED NOT NULL` | Chỉ tiêu sinh viên tối đa của đề tài | Ràng buộc giới hạn khi phân công. |
| **`roles`** | `name` | `VARCHAR(50)` | Mã định danh vai trò người dùng | Role giảng viên là **`lecturer`** (không có role `supervisor`). |

> [!WARNING]
> Tuyệt đối không viết truy vấn `SELECT lecturer_id FROM topics` vì cột này không tồn tại trên bảng `topics`.  
> Khi cần thông tin người hướng dẫn và chỗ còn trống của đề tài, luôn gọi hàm chuẩn: `TopicAssignment::getApprovedTopicsWithSlots($periodId)` hoặc `Topic::getApprovedWithSlots($periodId)`.

---

### 6.3. Tên các Biến dữ liệu Backend truyền cho Giao diện (View Data)

Tại `TopicAssignmentController::index()` (`GET /assignments`), dữ liệu truyền sang View bao gồm:
- **`$periods`** (`array`): Danh sách toàn bộ các đợt thực tập/đồ án.
- **`$currentPeriod`** (`int|null`): ID của đợt đang được chọn để xem.
- **`$assignments`** (`array`): Danh sách phân công trong đợt đã qua bộ lọc. Mỗi phần tử chứa:
  - `id`, `period_id`, `student_id`, `topic_id`, `lecturer_id`
  - `student_code`, `student_name`, `student_email`, `student_phone`
  - `topic_code`, `topic_name`, `topic_type`, `topic_max_students`
  - `lecturer_name`, `lecturer_email`
  - `matched_wish_priority`, `assignment_method`, `status`, `notes`, `assigned_at`
- **`$unassigned`** (`array`): Danh sách sinh viên đã nộp nguyện vọng nhưng chưa được phân công.
- **`$approvedTopics`** (`array`): Danh sách đề tài đã duyệt kèm số chỗ còn lại:
  - `topic_id`, `topic_code`, `topic_name`, `max_students`, `assigned_count`, `remaining_slots`
  - `lecturer_id`, `lecturer_name`, `proposer_id`, `proposer_name`, `is_proposer_default`
- **`$statistics`** (`array`): Thống kê tổng số SV, phân công, NV1/NV2/NV3, tỷ lệ hoàn thành.
- **`$filters`** (`array`): Trạng thái bộ lọc hiện tại:
  - `['status' => ..., 'keyword' => ..., 'lecturer_id' => ...]`
- **`$statusLabels`**, **`$statusBadges`**, **`$methodLabels`**, **`$methodBadges`** (`array`): Nhãn và class CSS badge Bootstrap 5 Tabler.
- **`$isManager`** (`bool`): Cờ quyền quản trị để ẩn/hiện các nút thao tác (Phân công tự động, Công bố, Phân công thủ công).
- **`$csrf_token`** (`string`): Token bảo mật CSRF dùng cho các biểu mẫu POST.
- **`$success`**, **`$error`** (`string|null`): Thông báo flash.

---

### 6.4. Quy tắc Bảo mật và Hiển thị Kết quả theo Vai trò

1. **Giao diện Sinh viên (`student`):**
   - Sinh viên chỉ được phép xem kết quả của chính mình (`student_id = $_SESSION['user_id']`).
   - **Chỉ hiển thị khi `status = 'published'`**: Nếu đợt phân công đang ở trạng thái dự thảo (`draft`), màn hình sinh viên phải hiển thị thông báo "Kết quả phân công đang được xử lý, chưa công bố chính thức". Tuyệt đối không lộ danh sách dự thảo ra giao diện sinh viên.
   - Khi gọi Model: sử dụng `TopicAssignment::getByStudent($studentId, $periodId, true)`.
2. **Giao diện Giảng viên (`lecturer`):**
   - Giảng viên chỉ được xem danh sách sinh viên mình hướng dẫn (`lecturer_id = $_SESSION['user_id']`).
   - Khi gọi Model: sử dụng `TopicAssignment::getByLecturer($lecturerId, $periodId, true)` (chỉ lấy sinh viên đã được công bố chính thức).
3. **Giao diện Cán bộ quản lý / Trưởng bộ môn / Quản trị viên (`admin`, `training_dept`, `department_head`):**
   - Được xem toàn bộ kết quả ở cả 3 trạng thái: `draft`, `published`, `cancelled`.
   - Có quyền thực hiện các thao tác chạy phân công tự động, điều chỉnh thủ công, công bố hoặc rút công bố.

---

### 6.5. Các Vấn đề Nghiệp vụ cần Xác nhận

1. **Quy trình Xác nhận trước khi Công bố:**
   - *Hiện trạng:* CSDL hỗ trợ 2 giai đoạn (`draft` $\to$ `published`).
   - *Cần xác nhận:* Nhà trường có yêu cầu bắt buộc thêm bước trung gian `confirmed` (Trưởng bộ môn / Ban chủ nhiệm Khoa ký duyệt trước khi Phòng đào tạo công bố) hay không? Nếu có, sẽ bổ sung giá trị `'confirmed'` vào `status ENUM` của bảng `topic_assignments`.
2. **Tiêu chí Xếp hạng Ưu tiên khi Trùng nguyện vọng:**
   - *Hiện trạng:* Thuật toán đối sánh tự động sắp xếp ưu tiên theo 3 cấp: (1) Điểm số / GPA giảm dần $\to$ (2) Thời gian nộp đơn sớm hơn $\to$ (3) Mã số sinh viên tăng dần (để bảo đảm tính tất định).
   - *Cần xác nhận:* Cần văn bản phê duyệt chính thức từ Hội đồng đào tạo về trọng số điểm tích lũy hoặc quy định xét ưu tiên đặc biệt (đối tượng chính sách, sinh viên tài năng).
3. **Quy định Giảng viên Hướng dẫn cho Đề tài do Doanh nghiệp / Sinh viên đề xuất:**
   - Khi đề tài do Doanh nghiệp hoặc Sinh viên tự đề xuất (`proposer_id` không mang vai trò `lecturer`), Cán bộ quản lý bắt buộc phải chỉ định một Giảng viên thuộc Khoa làm Giảng viên hướng dẫn chính thức (`lecturer_id`). Model `TopicAssignment::assignManual()` đã hỗ trợ đầy đủ việc tách biệt người đề xuất và giảng viên hướng dẫn.
