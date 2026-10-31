# QUY TRÌNH QUẢN LÝ MÃ NGUỒN VỚI GIT & GITHUB (GIT WORKFLOW)

Dự án: **Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên**  
Mô hình phát triển: **Agile / Scrum - Phân nhánh Git Flow**

---

## 1. Mô hình phân nhánh (Branching Strategy)

Hệ thống áp dụng mô hình Git Flow tối giản, phân định rõ ràng giữa mã nguồn production, mã nguồn tích hợp kiểm thử và các nhánh tính năng:

```mermaid
gitGraph
    commit id: "Initial Commit"
    branch develop
    checkout develop
    commit id: "Init develop"
    
    branch feature/project-setup
    checkout feature/project-setup
    commit id: "Core MVC, DB, Router"
    checkout develop
    merge feature/project-setup id: "PR #1 (Core)"
    
    branch feature/auth-backend
    checkout feature/auth-backend
    commit id: "Auth & User logic"
    
    branch feature/tabler-frontend
    checkout feature/tabler-frontend
    commit id: "Tabler UI layouts"
    
    checkout develop
    merge feature/auth-backend id: "PR #2 (Auth)"
    merge feature/tabler-frontend id: "PR #3 (UI)"
    
    checkout main
    merge develop id: "Release Sprint 1" tag: "v1.0.0-sprint1"
```

### Chi tiết các nhánh chính:
1. `main`: Nhánh ổn định cao nhất (Production-ready). Chỉ nhận merge từ `develop` sau khi đã nghiệm thu toàn bộ Sprint và được kiểm duyệt.
2. `develop`: Nhánh tích hợp chính của nhóm. Mọi tính năng sau khi hoàn thành sẽ tạo Pull Request vào đây.

### Các nhánh tính năng mẫu (Feature Branches):
- `feature/project-setup`: Khởi tạo kiến trúc MVC thuần, Router, Database PDO, Base Controller, cấu trúc CSDL và tài liệu.
- `feature/auth-backend`: Triển khai nghiệp vụ xác thực đăng nhập/đăng xuất, Model User, Role, session và mã hóa Bcrypt.
- `feature/tabler-frontend`: Tích hợp giao diện Tabler Bootstrap 5, layout Header/Sidebar/Footer, màn hình đăng nhập và Dashboard.

---

## 2. Quy trình làm việc hàng ngày (Daily Workflow)

### Bước 1: Khởi tạo repo và checkout nhánh làm việc
```bash
# 1. Clone repository về máy (nếu chưa clone)
git clone <URL_REPO>
cd internship-management

# 2. Cập nhật nhánh develop mới nhất
git checkout develop
git pull origin develop

# 3. Tạo và chuyển sang nhánh tính năng
git checkout -b feature/auth-backend
# hoặc:
git checkout -b feature/tabler-frontend
```

### Bước 2: Commit mã nguồn theo quy chuẩn (Conventional Commits)
Thông điệp commit phải ngắn gọn, rõ ràng theo cấu trúc:  
`<loại>(<phạm vi>): <mô tả ngắn bằng tiếng Việt hoặc tiếng Anh>`

Ví dụ:
- `feat(core): implement PDO Database connection and Router`
- `feat(auth): implement login with password_verify and session`
- `style(ui): integrate Tabler bootstrap 5 navbar and sidebar`
- `fix(router): resolve subfolder path parsing issue on XAMPP`
- `docs(db): update ERD documentation for multi-role design`

### Bước 3: Đẩy nhánh lên GitHub và tạo Pull Request (PR)
```bash
# Đẩy nhánh tính năng lên remote
git push origin feature/auth-backend
```
1. Truy cập giao diện GitHub của Repository.
2. Tạo **New Pull Request** với base branch là `develop` và compare branch là `feature/...`.
3. Điền mô tả chi tiết:
   - Các tính năng đã hoàn thành.
   - Các file đã thay đổi.
   - Hướng dẫn test để người review kiểm tra.
4. Gán **Reviewer** để kiểm tra mã nguồn độc lập.

### Bước 4: Review và Phê duyệt Merge
- Người kiểm duyệt kiểm tra syntax, logic an toàn và tiêu chí nghiệm thu.
- Nếu đạt, thực hiện Merge PR vào nhánh `develop`.
- Tuyệt đối không tự ý merge hoặc force push (`git push -f`) lên các nhánh `main` và `develop`.
