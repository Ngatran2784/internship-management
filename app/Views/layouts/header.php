<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Hệ thống Quản lý Thực tập & Đồ án' ?></title>
    <!--
        TODO [TV3 - Task #201]:
        - Tích hợp Tabler UI CSS:
          + <link rel="stylesheet" href="/assets/tabler/css/tabler.min.css">
          + <link rel="stylesheet" href="/assets/tabler/css/tabler-icons.min.css">
        - Thiết lập cấu trúc <header class="navbar navbar-expand-md ...">
        - Hiển thị logo, menu trên và thông tin avatar người dùng đang đăng nhập
    -->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; background-color: #f8fafc; }
        .app-container { display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 24px; }
    </style>
</head>
<body>
<div class="app-container">
