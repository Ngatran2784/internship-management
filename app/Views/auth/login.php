<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Thực tập & Đồ án</title>
    <!--
        TODO [TV3 - Task #204]:
        - Thiết kế trang Đăng nhập theo phong cách Tabler Auth Single Page:
          + Sử dụng class: page, page-center, container-tight, card, card-md
          + Thêm logo trường/khoa, form-label, form-control, input-group-flat...
          + Hỗ trợ hiển thị thông báo lỗi từ flash session do TV2 truyền sang.
    -->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 32px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #206bc4; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 style="text-align: center; margin-top: 0; color: #1e293b;">Đăng nhập</h2>
        <p style="text-align: center; color: #64748b; font-size: 0.85rem; margin-bottom: 24px;">Hệ thống Quản lý Thực tập & Đồ án</p>
        
        <!-- Khung form mẫu (TV3 sẽ hoàn thiện với Tabler Bootstrap 5 components) -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Tên đăng nhập hoặc Email</label>
                <input type="text" id="username" name="username" placeholder="Nhập tài khoản..." required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
