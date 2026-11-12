# TÀI LIỆU BÀN GIAO: PHÂN CÔNG THỦ CÔNG, XÁC NHẬN VÀ CÔNG BỐ

Dùng bảng `topic_assignments` theo `database/migrations/create_topic_assignments_table.sql`. Cơ chế đăng nhập, CSRF, flash message dùng lại `AuthController`.

Mã nguồn: `app/Controllers/TopicAssignmentController.php`, `app/Models/TopicAssignment.php`, route trong `public/index.php`, kiểm thử `tests/test_topic_assignments.php`.

## 1. Trạng thái

| `status` | Nhãn hiển thị | Ý nghĩa |
| :--- | :--- | :--- |
| `draft` | Dự thảo | Do thuật toán hoặc cán bộ tạo, CHƯA hiển thị cho sinh viên, giảng viên |
| `published` | Đã công bố | Kết quả chính thức |
| `cancelled` | Đã hủy | Phân công đã hủy, không tính chỉ tiêu |

Chuyển trạng thái hợp lệ: `draft` -> `published` (công bố), `draft` -> `cancelled` (hủy). Phân công đã công bố không hủy trực tiếp được. "Xác nhận" và "công bố" là một thao tác duy nhất (`/assignments/publish`): hệ thống kiểm tra lại toàn bộ điều kiện rồi chuyển cả đợt sang `published`.

## 2. Quyền

- Quản lý (phân công, hủy, kiểm tra, công bố, xem bản dự thảo): `admin`, `training_dept`, `department_head`, `faculty_staff`.
- Sinh viên: chỉ thấy kết quả ĐÃ CÔNG BỐ của chính mình.
- Giảng viên: chỉ thấy kết quả ĐÃ CÔNG BỐ do mình hướng dẫn.
- Vai trò khác: không thấy kết quả nào.

## 3. Route

| Phương thức | Đường dẫn | Action | Tham số |
| :--- | :--- | :--- | :--- |
| GET | `/assignments` | `index` | `?period_id=` |
| GET | `/assignments/manual` | `manual` | `?period_id=` |
| POST | `/assignments/manual` | `assignManual` | `_csrf`, `period_id`, `student_id`, `topic_id`, `lecturer_id`, `notes` (tùy chọn, tối đa 200 ký tự), `max_per_lecturer` (tùy chọn) |
| POST | `/assignments/cancel` | `cancel` | `_csrf`, `id`, `period_id` (để quay lại), `reason` (tùy chọn) |
| GET | `/assignments/check` | `check` | `?period_id=&max_per_lecturer=` |
| POST | `/assignments/publish` | `publish` | `_csrf`, `period_id`, `max_per_lecturer` (tùy chọn) |

Route có sẵn (thuật toán đối sánh): `POST /assignments/auto-match`, `GET /assignments/preview`.

Mọi POST phải có `_csrf`. Nếu client gửi `Accept: application/json` hoặc `X-Requested-With: XMLHttpRequest` thì trả JSON `{ "ok": true|false, ... }` kèm mã HTTP (400, 403, 405, 409, 422, 500); ngược lại đặt flash và chuyển hướng.

Chuyển hướng sau khi xử lý:
- Lưu phân công thành công -> `/assignments?period_id={id}`; lỗi -> `/assignments/manual?period_id={id}`.
- Hủy, công bố -> `/assignments?period_id={id}` (thành công hoặc lỗi).

## 4. Dữ liệu truyền sang View

Khi View chưa có, Controller hiển thị trang "đang chờ tích hợp giao diện".

### `assignments/index` (đã có)
`periods`, `currentPeriod`, `assignments`, `unassigned`, `statistics`, `isManager`, `statusLabels`, `statusBadges`, `methodLabels`, `methodBadges`, `csrf_token`, `success`, `error`.
Người không có quyền quản lý nhận `unassigned = []`, `statistics = []` và `assignments` chỉ gồm bản ghi đã công bố của chính họ.

### `assignments/manual` (mới)
```
periods, currentPeriod,
students   : [{student_id, student_code, student_name, wishes_count}]            // chưa có phân công
topics     : [{id, code, name, proposer_id, proposer_name, max_students, assigned_count}] // chỉ đề tài đã duyệt
lecturers  : [{id, username, full_name, email, assigned_count}]
drafts     : danh sách phân công dự thảo của đợt (cùng cấu trúc với `assignments`)
statusLabels, methodLabels, csrf_token, success, error
```
Form POST `/assignments/manual`: `period_id`, `student_id` (select), `topic_id` (select), `lecturer_id` (select), `notes`, `_csrf`. Nút Hủy trên dòng dự thảo: form POST `/assignments/cancel` với `id`, `period_id`, `_csrf`.

### `assignments/check` (mới)
`currentPeriod`, `draft_count`, `conflicts` (mảng chuỗi), `can_publish`. Nút Công bố chỉ bật khi `can_publish = true`; form POST `/assignments/publish` với `period_id`, `_csrf`.

## 5. Thông báo (flash)

| Tình huống | Thông báo |
| :--- | :--- |
| Lưu thành công | Đã lưu phân công thủ công (Dự thảo). |
| Hủy thành công | Đã hủy phân công. |
| Công bố thành công | Đã xác nhận và công bố {n} phân công của đợt. |
| Sai quyền | Bạn không có quyền ... (HTTP 403) |
| CSRF sai | Mã bảo mật (CSRF) không hợp lệ hoặc phiên làm việc đã hết hạn. (HTTP 403) |
| Thiếu dữ liệu | Vui lòng chọn sinh viên / đề tài / giảng viên hướng dẫn. (HTTP 422) |
| Trùng sinh viên | Sinh viên đã được phân công trong đợt này ... Hãy hủy phân công cũ trước khi phân công lại. (HTTP 422) |
| Đề tài chưa duyệt | Chỉ được phân công vào đề tài đã được phê duyệt. (HTTP 422) |
| Hết chỉ tiêu | Đề tài đã đủ chỉ tiêu sinh viên (a/b). / Giảng viên hướng dẫn đã đạt giới hạn chỉ tiêu (a/b). (HTTP 409) |
| Còn xung đột khi công bố | Không thể công bố vì còn xung đột: ... (HTTP 422) |
| Công bố lặp | Không có bản dự thảo nào để công bố (có thể đã được công bố trước đó). (HTTP 409) |
| Hủy bản đã công bố | Chỉ hủy được phân công ở trạng thái Dự thảo. ... (HTTP 422) |

## 6. Điểm phụ thuộc
- Giao diện `assignments/manual`, `assignments/check` chưa có; các View phụ thuộc biến ở mục 4.
- Chỉ tiêu mỗi giảng viên mặc định theo `TopicMatchingService::DEFAULT_MAX_STUDENTS_PER_LECTURER`; có thể đổi bằng trường `max_per_lecturer`.
- Không thêm bảng, cột hay thay đổi thuật toán đối sánh. Công bố không động đến bảng nguyện vọng `topic_wishes`.
