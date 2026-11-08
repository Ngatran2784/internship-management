# TÀI LIỆU BÀN GIAO: XÉT DUYỆT ĐỀ TÀI

Phạm vi: gửi duyệt, duyệt, từ chối đề tài và lịch sử xét duyệt. Cơ chế đăng nhập, CSRF, flash message dùng lại `AuthController` hiện có.

Mã nguồn:
- Migration: `database/migrations/create_topic_approvals_table.sql`
- Model: `app/Models/TopicApproval.php`
- Controller: `app/Controllers/TopicApprovalController.php`
- Route: `public/index.php`
- Kiểm thử: `tests/test_topic_approval.php`

## 1. Luồng trạng thái đề tài (`topics.status`)

```
draft ──gửi duyệt──► pending ──duyệt──► approved
rejected ──gửi duyệt lại──► pending     pending ──từ chối──► rejected
```

| Thao tác | Trạng thái trước | Trạng thái sau |
| :--- | :--- | :--- |
| Gửi duyệt | `draft`, `rejected` | `pending` |
| Duyệt | `pending` | `approved` |
| Từ chối | `pending` | `rejected` |

Mỗi lần duyệt/từ chối được ghi vào bảng `topic_approvals`. Không thể duyệt lặp: đề tài không còn `pending` thì mọi thao tác duyệt/từ chối đều bị từ chối. Không gửi duyệt và không xét duyệt đề tài thuộc đợt đã kết thúc (`closed`).

## 2. Bảng `topic_approvals`

| Cột | Kiểu | Ý nghĩa |
| :--- | :--- | :--- |
| `id` | INT UNSIGNED, PK | Khóa chính |
| `topic_id` | INT UNSIGNED, FK -> `topics.id` | Đề tài được xét duyệt |
| `reviewer_id` | INT UNSIGNED, FK -> `users.id` | Người xử lý |
| `decision` | ENUM(`approved`, `rejected`) | Quyết định |
| `comments` | TEXT, NULL | Nhận xét (bắt buộc khi từ chối, tối đa 1000 ký tự) |
| `created_at` | DATETIME | Thời điểm xử lý |

## 3. Route

| Phương thức | Đường dẫn | Action | Quyền | Tham số |
| :--- | :--- | :--- | :--- | :--- |
| GET | `/topics/pending` | `TopicApprovalController::pending` | `admin`, `training_dept`, `department_head` | `?period_id=&type=&keyword=` |
| GET | `/topics/history` | `TopicApprovalController::history` | Người đề xuất, `admin`, `training_dept`, `department_head` | `?id=` |
| POST | `/topics/submit` | `TopicApprovalController::submit` | Người đề xuất, `admin`, `training_dept` | `_csrf`, `id` |
| POST | `/topics/approve` | `TopicApprovalController::approve` | `admin`, `training_dept`, `department_head` | `_csrf`, `id`, `comments` (tùy chọn) |
| POST | `/topics/reject` | `TopicApprovalController::reject` | `admin`, `training_dept`, `department_head` | `_csrf`, `id`, `comments` (bắt buộc) |

Mọi POST phải có `_csrf`. Không dùng GET để đổi trạng thái.

Sau khi xử lý xong, Controller chuyển hướng và đặt flash message:
- `submit` -> `/topics/detail?id={id}`
- `approve`, `reject` -> `/topics/pending`
- Lỗi -> quay lại trang tương ứng kèm flash `error`.

Nếu client gửi `Accept: application/json` hoặc `X-Requested-With: XMLHttpRequest`, các POST trả JSON:
- Thành công: `{ "ok": true, "message": "...", "id": 5, "status": "approved" }`
- Lỗi: `{ "ok": false, "error": "..." }` với HTTP 400/403/404/422/500.

## 4. Dữ liệu Controller truyền sang View

View nằm trong `app/Views/topics/`. Nếu View chưa có, Controller trả JSON UTF-8 của đúng mảng dưới đây.

### `topics/pending.php`
```php
[
    'topics'       => array,   // đề tài đang pending, kèm period_name, proposer_name...
    'periods'      => array,   // đổ vào select bộ lọc
    'filters'      => array,   // ['period_id', 'type', 'keyword', 'status' => 'pending']
    'statusLabels' => array,
    'typeLabels'   => array,
    'statusBadges' => array,
    'typeBadges'   => array,
    'success'      => ?string,
    'error'        => ?string,
    'csrfToken'    => string,
]
```
Mỗi dòng có nút Duyệt (form POST `/topics/approve`) và nút Từ chối (form POST `/topics/reject`, có ô nhập `comments` bắt buộc), cùng trường ẩn `id` và `_csrf`.

### `topics/history.php`
```php
[
    'topic'          => array,   // thông tin đề tài
    'approvals'      => array,   // mới nhất trước: id, decision, comments, created_at, reviewer_name, reviewer_username
    'decisionLabels' => array,   // ['approved' => 'Đã duyệt', 'rejected' => 'Từ chối']
    'statusLabels'   => array,
    'statusBadges'   => array,
    'success'        => ?string,
    'error'          => ?string,
    'csrfToken'      => string,
]
```

### Gợi ý cho `topics/show.php` (đã có)
- Nút **Gửi duyệt** (form POST `/topics/submit`): hiện khi `topic.status` là `draft` hoặc `rejected` và người dùng là người đề xuất, `admin` hoặc `training_dept`.
- Liên kết **Lịch sử xét duyệt** tới `/topics/history?id={id}`.

## 5. Điểm cần thống nhất
- Vai trò được xét duyệt (`admin`, `training_dept`, `department_head`) và việc ghi lịch sử chỉ cho thao tác duyệt/từ chối.
- Form sửa đề tài (`/topics/update`) hiện cho `admin`/`training_dept` đặt thẳng `status = approved` mà không ghi `topic_approvals`.
- Nghiệp vụ đăng ký đề tài của sinh viên chưa triển khai, chờ thống nhất bảng dữ liệu và quy tắc.
