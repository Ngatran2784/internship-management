# HỢP ĐỒNG KỸ THUẬT VÀ TÀI LIỆU BÀN GIAO MODULE QUẢN LÝ ĐỀ TÀI (TOPICS)

- **Dự án:** Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên - Trường Đại học Vinh
- **Phân hệ chức năng:** Quản lý Đề tài thực tập & Đồ án tốt nghiệp
- **Phạm vi kỹ thuật:** Cơ sở dữ liệu và Backend xử lý nghiệp vụ
- **Các phân hệ tích hợp liên quan:** Phân hệ Phê duyệt đề tài, Phân hệ Doanh nghiệp, Phân hệ Giao diện Frontend
- **Tài liệu tham chiếu:** `docs/topic-module-contract.md`
- **Mã nguồn liên quan:**
  - CSDL Migration: `database/migrations/create_topics_table.sql`
  - Model: `app/Models/Topic.php`
  - Controller: `app/Controllers/TopicController.php`
  - Router: `public/index.php`
  - Bộ kiểm thử tự động: `tests/test_topics.php` (46 tests PASS)

---

## 1. Cấu trúc Cơ sở Dữ liệu (Bảng `topics`)

Bảng `topics` lưu trữ danh mục đề tài thực tập tốt nghiệp và đồ án tốt nghiệp trong hệ thống.

```sql
CREATE TABLE IF NOT EXISTS `topics` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Khóa chính tự tăng',
    `code` VARCHAR(50) NOT NULL COMMENT 'Mã đề tài định danh duy nhất (vd: DT-2026-001, DA-2026-002)',
    `name` VARCHAR(255) NOT NULL COMMENT 'Tên hiển thị của đề tài',
    `description` TEXT NULL COMMENT 'Mô tả chi tiết nội dung, mục tiêu, yêu cầu công nghệ của đề tài',
    `type` ENUM('internship', 'graduation_project') NOT NULL COMMENT 'Loại đề tài: Thực tập hoặc Đồ án tốt nghiệp',
    `period_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến đợt (internship_periods.id)',
    `proposer_id` INT UNSIGNED NOT NULL COMMENT 'Khóa ngoại tham chiếu đến người đề xuất (users.id)',
    `max_students` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Số lượng sinh viên tối đa có thể tham gia',
    `status` ENUM('draft', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái đề tài',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời điểm tạo đề tài',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời điểm cập nhật cuối',
    
    UNIQUE KEY `uk_topics_code` (`code`),
    INDEX `idx_topics_period_id` (`period_id`),
    INDEX `idx_topics_proposer_id` (`proposer_id`),
    INDEX `idx_topics_status` (`status`),
    INDEX `idx_topics_type` (`type`),
    INDEX `idx_topics_created_at` (`created_at`),
    
    CONSTRAINT `fk_topics_period` FOREIGN KEY (`period_id`)
        REFERENCES `internship_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_topics_proposer` FOREIGN KEY (`proposer_id`)
        REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Chi tiết các trường dữ liệu:

| Tên cột | Kiểu dữ liệu | Ràng buộc | Ý nghĩa |
| :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | `PRIMARY KEY, AUTO_INCREMENT` | Định danh nội bộ đề tài |
| `code` | `VARCHAR(50)` | `NOT NULL, UNIQUE` | Mã đề tài (in hoa, không dấu cách, ví dụ `DT-2026-001`) |
| `name` | `VARCHAR(255)` | `NOT NULL` | Tên đầy đủ của đề tài (5 - 255 ký tự) |
| `description` | `TEXT` | `NULL` | Nội dung, mục tiêu và yêu cầu kỹ thuật |
| `type` | `ENUM` | `'internship', 'graduation_project'` | Phân loại: Thực tập tốt nghiệp / Đồ án tốt nghiệp |
| `period_id` | `INT UNSIGNED` | `NOT NULL, FK -> internship_periods(id)` | Thuộc đợt thực tập/đồ án nào |
| `proposer_id` | `INT UNSIGNED` | `NOT NULL, FK -> users(id)` | Giảng viên / Người đề xuất đề tài |
| `max_students` | `INT UNSIGNED` | `NOT NULL DEFAULT 1` | Giới hạn số sinh viên tối đa (1 - 10) |
| `status` | `ENUM` | `'draft', 'pending', 'approved', 'rejected'` | Vòng đời phê duyệt |
| `created_at` | `DATETIME` | `NOT NULL DEFAULT CURRENT_TIMESTAMP` | Thời điểm tạo |
| `updated_at` | `DATETIME` | `NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE` | Thời điểm cập nhật cuối |

---

## 2. Vòng đời Trạng thái Đề tài (Topic Statuses)

| Mã trạng thái (`status`) | Nhãn tiếng Việt | Class Badge (Tabler) | Mô tả nghiệp vụ |
| :--- | :--- | :--- | :--- |
| `draft` | **Bản nháp** | `bg-secondary-lt text-secondary` | Đề tài đang được soạn thảo. Người đề xuất được sửa, xóa hoặc gửi duyệt. |
| `pending` | **Chờ duyệt** | `bg-warning-lt text-warning` | Đề tài đã gửi lên Hội đồng/Trưởng bộ môn/PĐT. Người đề xuất không được tự ý sửa. |
| `approved` | **Đã duyệt** | `bg-success-lt text-success` | Đề tài đã được thông qua chính thức, sinh viên có thể xem và đăng ký. |
| `rejected` | **Từ chối** | `bg-danger-lt text-danger` | Đề tài bị từ chối phê duyệt. Người đề xuất có thể chỉnh sửa lại để gửi lại. |

---

## 3. Quy tắc Phân quyền (RBAC)

1. **Xem danh sách (`GET /topics`) & Xem chi tiết (`GET /topics/detail?id=...`):**
   - `admin`, `training_dept`: Xem được toàn bộ đề tài ở tất cả các trạng thái và đợt.
   - `department_head`, `lecturer`, `enterprise`: Xem được tất cả đề tài đã `approved`, cộng với các đề tài do chính mình tạo ra (`proposer_id == current_user`).
   - `student`: Chỉ xem được đề tài đã `approved` trong các đợt đang mở (active).

2. **Tạo / Đề xuất đề tài (`GET /topics/create`, `POST /topics/create`):**
   - `admin`, `training_dept`, `department_head`, `lecturer`, `enterprise`, `student`: Được phép gửi đề xuất.
   - Người đề xuất tự động gắn `proposer_id = $_SESSION['user_id']`. Riêng `admin` có quyền chọn người đề xuất khác qua `$_POST['proposer_id']`.

3. **Chỉnh sửa đề tài (`GET /topics/edit?id=...`, `POST /topics/update`):**
   - `admin`, `training_dept`: Toàn quyền sửa mọi đề tài ở bất kỳ trạng thái nào.
   - Người đề xuất: Chỉ được sửa đề tài của chính mình khi trạng thái là `draft` hoặc `rejected`.
   - Khi đề tài đã `pending` hoặc `approved`, người đề xuất bị khóa quyền sửa (phòng chống sửa đổi sau khi đã được thẩm định).

4. **Xóa đề tài (`Topic::delete($id)`):**
   - Chỉ cho phép xóa khi đề tài ở trạng thái `draft` hoặc `rejected`.
   - Tuyệt đối không cho phép xóa đề tài đã `approved` hoặc `pending`.

---

## 4. Danh sách Route đã Đăng ký trong `public/index.php`

| Phương thức | URI Route | Controller Action | Quyền truy cập | Mô tả |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/topics` | `TopicController::index` | Đã đăng nhập | Danh sách đề tài kèm bộ lọc & tìm kiếm |
| `GET` | `/topics/create` | `TopicController::create` | Giảng viên, BM, DN, Admin | Hiển thị form tạo / đề xuất đề tài |
| `POST` | `/topics/create` | `TopicController::store` | Giảng viên, BM, DN, Admin | Lưu đề tài mới (CSRF bắt buộc) |
| `POST` | `/topics` | `TopicController::store` | Giảng viên, BM, DN, Admin | Alias lưu đề tài mới |
| `GET` | `/topics/detail` | `TopicController::show` | Đã đăng nhập | Xem chi tiết đề tài (`?id=...`) |
| `GET` | `/topics/{id}` | `TopicController::show` | Đã đăng nhập | URL thân thiện xem chi tiết đề tài |
| `GET` | `/topics/edit` | `TopicController::edit` | Người đề xuất, Admin | Hiển thị form chỉnh sửa đề tài (`?id=...`) |
| `POST` | `/topics/update` | `TopicController::update` | Người đề xuất, Admin | Cập nhật đề tài (CSRF bắt buộc) |
| `POST` | `/topics/edit` | `TopicController::update` | Người đề xuất, Admin | Alias cập nhật đề tài |

---

## 5. Dữ liệu Controller truyền sang View (Data Contract)

Kiến trúc Controller được thiết kế theo phương thức `respond(string $view, array $data)`:
- Nếu file View `.php` tương ứng đã tồn tại trong `app/Views/topics/`: Tự động render View với dữ liệu được giải nén (`extract($data)`).
- Nếu file View chưa được tạo (Frontend đang phát triển): Tự động xuất chuỗi JSON UTF-8 tương ứng với HTTP status code chuẩn.

### A. View `topics/index.php` (Danh sách đề tài)
```php
[
    'topics'            => array,   // Mảng danh sách đề tài kèm period_name, proposer_name...
    'periods'           => array,   // Danh sách các đợt để đổ vào thẻ <select> bộ lọc
    'filters'           => array,   // ['period_id' => ?, 'status' => ?, 'type' => ?, 'keyword' => ?]
    'statusLabels'      => array,   // Topic::STATUS_LABELS
    'typeLabels'        => array,   // Topic::TYPE_LABELS
    'statusBadges'      => array,   // Topic::STATUS_BADGES
    'typeBadges'        => array,   // Topic::TYPE_BADGES
    'isAdminOrTraining' => bool,    // Quyền quản trị viên
    'currentUserId'     => int,     // ID người dùng hiện tại
    'currentUserRoles'  => array,   // Mảng các vai trò hiện tại
    'success'           => ?string, // Thông báo thành công flash
    'error'             => ?string, // Thông báo lỗi flash
    'csrfToken'         => string,  // Token CSRF
]
```

### B. View `topics/show.php` (Chi tiết đề tài)
```php
[
    'topic'        => array,   // Thông tin chi tiết đề tài (kèm period_code, proposer_name, email...)
    'canEdit'      => bool,    // Người dùng hiện tại có nút Sửa hay không
    'statusLabels' => array,
    'typeLabels'   => array,
    'statusBadges' => array,
    'typeBadges'   => array,
    'success'      => ?string,
    'error'        => ?string,
    'csrfToken'    => string,
]
```

### C. View `topics/create.php` & `topics/edit.php` (Form tạo & Form sửa)
```php
[
    'topic'        => array,   // (Chỉ có ở form edit) Dữ liệu đề tài hiện tại
    'periods'      => array,   // Danh sách các đợt để chọn liên kết
    'old'          => array,   // Dữ liệu người dùng vừa nhập khi bị lỗi validation
    'errors'       => array,   // Mảng lỗi theo trường ['name' => '...', 'code' => '...']
    'statusLabels' => array,
    'typeLabels'   => array,
    'csrfToken'    => string,  // Bắt buộc đặt vào input hidden: <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
]
```

---

## 6. Quy ước Xử lý Lỗi và Thông báo (Flash Messages)

- **Thông báo thành công:** Lưu qua `AuthController::setFlash('success', '...')`, tự động hiển thị trong alert xanh lục trên View hoặc qua JSON `{ ok: true, message: '...' }`.
- **Thông báo lỗi:** Lưu qua `AuthController::setFlash('error', '...')`, tự động hiển thị trong alert màu đỏ trên View hoặc qua JSON `{ ok: false, error: '...' }` với HTTP code thích hợp (400, 403, 404, 422, 500).
- **Lưu lại form khi lỗi:** Controller tự động lưu lại `$_POST` vào `$_SESSION['topic_old']` và các lỗi vào `$_SESSION['topic_errors']` để form không bị mất dữ liệu khi nhập lại.

---

## 7. Yêu cầu Tích hợp cho Phân hệ Phê duyệt & Doanh nghiệp

1. **Phân hệ Phê duyệt đề tài (Topic Approval Workflow):**
   - Bổ sung các action trong `TopicApprovalController` hoặc mở rộng `TopicController`:
     - `POST /topics/approve`: Chuyển trạng thái sang `approved`.
     - `POST /topics/reject`: Chuyển trạng thái sang `rejected` kèm lý do từ chối (ghi nhận phản hồi cho giảng viên).
     - `POST /topics/submit`: Người đề xuất chuyển trạng thái từ `draft` sang `pending`.
   - Khi triển khai lưu vết lịch sử phê duyệt: Tạo bảng `topic_approvals` (hoặc `topic_reviews`) liên kết `topic_id`, `reviewer_id`, `decision`, `comments`, `created_at`.
2. **Phân hệ Doanh nghiệp:**
   - Khi doanh nghiệp đăng ký tài khoản và đề xuất đề tài thực tập:
     - Gán `type = 'internship'`.
     - Khóa ngoại `proposer_id` trỏ trực tiếp đến `users(id)` của tài khoản doanh nghiệp.
     - Sau này có thể liên kết thêm thông tin công ty qua bảng hồ sơ doanh nghiệp (`enterprise_profiles`).

---

## 8. Yêu cầu Xây dựng Giao diện Người dùng (Frontend Views)

1. **Thư mục View:** `app/Views/topics/`
   - `index.php`: Bảng danh sách đề tài chuẩn Tabler:
     - Bộ lọc bên trên: Theo Đợt (`select`), Trạng thái (`select`), Loại đề tài (`select`), Ô tìm kiếm từ khóa (`input`).
     - Bảng dữ liệu: Cột Mã đề tài, Tên đề tài, Loại đề tài (badge), Đợt liên kết, Người đề xuất, Trạng thái (badge), Thao tác (Chi tiết, Sửa nếu `canEdit`).
     - Nút "Đề xuất đề tài mới" góc trên bên phải.
   - `create.php`: Form thêm đề tài mới với các input: Mã đề tài, Tên đề tài, Loại đề tài, Chọn đợt, Số lượng SV tối đa, Mô tả chi tiết.
   - `edit.php`: Form chỉnh sửa đề tài tương tự `create.php`, tự động điền sẵn dữ liệu.
   - `show.php`: Trang xem chi tiết đề tài: hiển thị đầy đủ thông tin, mô tả, thông tin đợt, người đề xuất và nút quay lại hoặc nút sửa.
2. **Tuân thủ Giao diện:**
   - Giữ nguyên màu chủ đạo xanh navy Đại học Vinh (`#002B55`, `#003B71`) và logo trường tại Header/Sidebar.
   - Sử dụng các lớp CSS Tabler có sẵn (`card`, `table`, `badge`, `form-control`, `btn-primary`, v.v.).
