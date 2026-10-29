<?php

namespace App\Core;

use RuntimeException;

class Controller
{
    /**
     * Render một view với dữ liệu truyền vào
     * 
     * @param string $view Đường dẫn view tương đối tính từ thư mục app/Views (ví dụ: 'auth/login', 'dashboard/index')
     * @param array $data Mảng dữ liệu truyền sang view
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new RuntimeException("View không tồn tại: {$viewPath}");
        }

        // Giải nén mảng thành các biến cục bộ trong view
        extract($data);

        require $viewPath;
    }

    /**
     * Chuyển hướng người dùng sang URL mới
     * 
     * @param string $url URL hoặc đường dẫn cần chuyển hướng
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Escape dữ liệu chuỗi đầu ra nhằm phòng chống tấn công XSS
     * 
     * @param mixed $value
     * @return string
     */
    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
