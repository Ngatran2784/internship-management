# HỢP ĐỒNG GIAO DIỆN & QUY CHUẨN FRONTEND TABLER UI

- Tài liệu dành cho: **Nhà phát triển Giao diện (Frontend Developer)**
- Dự án: **Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên**
- Thư viện giao diện: **Tabler UI (Bootstrap 5.3), Tabler Icons**
- Đường dẫn tài nguyên tĩnh: `public/assets/tabler/`

---

## 1. Danh sách các trang View cần xây dựng

Toàn bộ các view được đặt trong thư mục `app/Views/`:

### 1.1. Phân hệ Quản lý Đợt thực tập & Đồ án (`app/Views/periods/`)
1. **`app/Views/periods/index.php`**: Danh sách đợt thực tập/đồ án, bộ lọc loại đợt, trạng thái, ô tìm kiếm và các nút thao tác.
2. **`app/Views/periods/create.php`**: Form tạo mới đợt thực tập hoặc đồ án.
3. **`app/Views/periods/edit.php`**: Form chỉnh sửa thông tin đợt.
4. **`app/Views/periods/show.php`**: Trang xem chi tiết một đợt, tóm tắt thông tin, mốc thời gian và nút chuyển trạng thái nhanh.

### 1.2. Phân hệ Quản lý Người dùng (`app/Views/users/`)
1. **`app/Views/users/index.php`**: Bảng danh sách người dùng hệ thống, hiển thị vai trò (badges), trạng thái (hoạt động/khóa), nút mở modal phân vai trò.
2. **`app/Views/users/create.php`**: Form thêm tài khoản mới (MSSV, MSGVs, email, vai trò).
3. **`app/Views/users/edit.php`**: Form chỉnh sửa thông tin tài khoản, đổi trạng thái khóa/mở.
4. **`app/Views/users/profile.php`**: Trang hồ sơ cá nhân của người dùng đang đăng nhập.

---

## 2. Bản đồ Tuyến đường (Routes) và Tên trường Biểu mẫu (Form Fields)

### 2.1. Phân hệ Quản lý Đợt (`periods`)

| Trang View | Route gọi GET | Route gửi POST | Các trường form (`name`) |
| :--- | :--- | :--- | :--- |
| `periods/index` | `GET /periods` | `POST /periods/status` (Đổi nhanh trạng thái) | `_csrf`, `id`, `status` |
| `periods/create`| `GET /periods/create` | `POST /periods/create` hoặc `POST /periods` | `_csrf`, `code`, `name`, `type`, `start_date`, `end_date`, `status`, `description` |
| `periods/edit`  | `GET /periods/edit?id={id}` | `POST /periods/update` hoặc `POST /periods/edit` | `_csrf`, `id`, `code`, `name`, `type`, `start_date`, `end_date`, `status`, `description` |
| `periods/show`  | `GET /periods/detail?id={id}` | `POST /periods/status` | `_csrf`, `id`, `status`, `redirect` (giá trị: `detail`) |

#### Chi tiết tên trường và kiểu dữ liệu form Đợt:
- `_csrf`: `hidden` (Bắt buộc cho mọi form POST).
- `id`: `hidden` (Chỉ có trong form Edit/Status).
- `code`: `text`, bắt buộc, chữ in hoa, không dấu, ví dụ: `HK1-2026-TT`.
- `name`: `text`, bắt buộc, ví dụ: `Đợt Thực tập tốt nghiệp HK1 2026-2027`.
- `type`: `select`, bắt buộc. Giá trị:
  - `internship`: Thực tập tốt nghiệp
  - `graduation_project`: Đồ án tốt nghiệp
- `start_date`: `date` (`YYYY-MM-DD`), bắt buộc.
- `end_date`: `date` (`YYYY-MM-DD`), bắt buộc, $\ge$ `start_date`.
- `status`: `select` hoặc `radio`, bắt buộc. Giá trị:
  - `draft`: Bản nháp
  - `active`: Đang diễn ra
  - `closed`: Đã kết thúc
- `description`: `textarea`, tùy chọn, mô tả quy định, tiêu chí hoặc mốc thời gian.

---

### 2.2. Phân hệ Quản lý Người dùng (`users`)

| Trang View | Route gọi GET | Route gửi POST | Các trường form (`name`) |
| :--- | :--- | :--- | :--- |
| `users/index` | `GET /users` | `POST /users/roles` (Modal gán vai trò)<br>`POST /users/status` (Khóa/Mở) | `_csrf`, `user_id`, `role_ids[]`<br>`_csrf`, `id`, `status` |
| `users/create`| `GET /users/create` | `POST /users/create` | `_csrf`, `username`, `email`, `password`, `full_name`, `phone`, `role_ids[]` |
| `users/edit`  | `GET /users/edit?id={id}` | `POST /users/update` | `_csrf`, `id`, `full_name`, `email`, `phone`, `password` (tùy chọn), `status` |
| `users/profile`| `GET /profile` | `POST /profile` | `_csrf`, `full_name`, `phone` |

---

## 3. Quy chuẩn Khung giao diện Tabler UI

Mọi trang View nội dung phải được bao bọc bởi cấu trúc chuẩn của Tabler và nhúng các thành phần layout dùng chung:

```php
<?php
$pageTitle = 'Danh sách Đợt thực tập & Đồ án';
$activePage = 'periods'; // để kích hoạt mục tương ứng trên sidebar
require dirname(__DIR__) . '/layouts/header.php';
require dirname(__DIR__) . '/layouts/sidebar.php';
?>

<div class="page-wrapper">
    <!-- Tiêu đề trang & Thanh công cụ (Page Header) -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= $escape($baseUrl) ?>/dashboard">Bảng điều khiển</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Đợt thực tập &amp; Đồ án</li>
                        </ol>
                    </nav>
                    <h2 class="page-title">Quản lý Đợt thực tập và Đồ án</h2>
                </div>
                <!-- Nút thao tác (chỉ hiển thị với Admin/Phòng đào tạo) -->
                <?php if (in_array('admin', $_SESSION['roles'] ?? [], true) || in_array('training_dept', $_SESSION['roles'] ?? [], true)): ?>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="<?= $escape($baseUrl) ?>/periods/create" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus me-1"></i> Tạo đợt mới
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Nội dung chính (Page Body) -->
    <div class="page-body">
        <div class="container-xl">
            <!-- Thông báo Flash (Success / Error) -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-check fs-2 me-2"></i></div>
                        <div><?= $escape($success) ?></div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-alert-circle fs-2 me-2"></i></div>
                        <div><?= $escape($error) ?></div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                </div>
            <?php endif; ?>

            <!-- Nội dung Card của bảng hoặc biểu mẫu đặt tại đây -->
            <div class="card">
                ...
            </div>
        </div>
    </div>

    <?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
</div>
```

---

## 4. Quy ước Màu sắc và Badges trạng thái Tabler

### 4.1. Trạng thái Đợt (`status`):
- `draft` (Bản nháp): `<span class="badge bg-secondary-lt">Bản nháp</span>` hoặc `<span class="badge bg-secondary">Nháp</span>`
- `active` (Đang diễn ra): `<span class="badge bg-success-lt text-success fw-bold"><i class="ti ti-point-filled me-1"></i>Đang diễn ra</span>`
- `closed` (Đã kết thúc): `<span class="badge bg-danger-lt">Đã kết thúc</span>`

### 4.2. Loại Đợt (`type`):
- `internship` (Thực tập tốt nghiệp): `<span class="badge bg-azure text-azure-fg"><i class="ti ti-briefcase me-1"></i>Thực tập</span>`
- `graduation_project` (Đồ án tốt nghiệp): `<span class="badge bg-purple text-purple-fg"><i class="ti ti-school me-1"></i>Đồ án</span>`

### 4.3. Trạng thái Người dùng (`status`):
- `1` (Hoạt động): `<span class="badge bg-success text-white">Hoạt động</span>`
- `0` (Bị khóa): `<span class="badge bg-danger text-white">Đã khóa</span>`

### 4.4. Nhãn Vai trò người dùng (`roles`):
- `admin`: `<span class="badge bg-red text-white">Quản trị viên</span>`
- `training_dept`: `<span class="badge bg-blue text-white">Phòng đào tạo</span>`
- `department_head`: `<span class="badge bg-teal text-white">Trưởng bộ môn</span>`
- `lecturer`: `<span class="badge bg-green text-white">Giảng viên</span>`
- `student`: `<span class="badge bg-yellow text-white">Sinh viên</span>`
- `enterprise`: `<span class="badge bg-orange text-white">Doanh nghiệp</span>`

---

## 5. Dữ liệu Mẫu (Mock Data) để Dựng giao diện độc lập

Frontend có thể dùng trực tiếp mảng dữ liệu mẫu dưới đây để hoàn thiện giao diện trước khi ghép với CSDL thực:

```php
// Dữ liệu mẫu cho trang periods/index:
$mockPeriods = [
    [
        'id'          => 1,
        'code'        => 'HK1-2026-TT',
        'name'        => 'Đợt Thực tập tốt nghiệp Học kỳ 1 năm học 2026-2027',
        'type'        => 'internship',
        'start_date'  => '2026-09-01',
        'end_date'    => '2026-12-31',
        'status'      => 'active',
        'description' => 'Áp dụng cho sinh viên năm 4 ngành CNTT.',
        'created_at'  => '2026-08-15 08:30:00'
    ],
    [
        'id'          => 2,
        'code'        => 'HK2-2026-DA',
        'name'        => 'Đợt Đồ án tốt nghiệp Học kỳ 2 năm học 2026-2027',
        'type'        => 'graduation_project',
        'start_date'  => '2027-01-15',
        'end_date'    => '2027-05-30',
        'status'      => 'draft',
        'description' => 'Chuẩn bị danh mục đề tài và danh sách hội đồng.',
        'created_at'  => '2026-10-01 14:00:00'
    ]
];
```

---

## 6. Quy chuẩn Bảo mật và Form Submission

1. **CSRF Token:** Mọi thẻ `<form method="POST">` bắt buộc phải có input ẩn:
   ```html
   <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
   ```
2. **XSS Prevention:** Mọi biến xuất ra màn hình bắt buộc dùng hàm `$escape(...)`:
   ```html
   <h3 class="card-title"><?= $escape($period['name']) ?></h3>
   ```
3. **Chuyển trạng thái an toàn:**
   Không dùng thẻ `<a href="...">` cho thao tác đổi trạng thái hoặc xóa. Luôn dùng nút submit trong form POST:
   ```html
   <form method="POST" action="<?= $escape($baseUrl) ?>/periods/status" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn thay đổi trạng thái đợt này?');">
       <input type="hidden" name="_csrf" value="<?= $escape($csrfToken) ?>">
       <input type="hidden" name="id" value="<?= (int)$period['id'] ?>">
       <input type="hidden" name="status" value="closed">
       <button type="submit" class="btn btn-outline-danger btn-sm">
           <i class="ti ti-lock me-1"></i> Đóng đợt
       </button>
   </form>
   ```
