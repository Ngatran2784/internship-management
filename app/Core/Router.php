<?php

namespace App\Core;

class Router
{
    private array $routes = [
        'GET'  => [],
        'POST' => []
    ];

    /**
     * Đăng ký route cho phương thức GET
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Đăng ký route cho phương thức POST
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalizePath($path)] = $handler;
    }

    /**
     * Chuẩn hóa đường dẫn URI
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    /**
     * Phân tích Request URI hiện tại phù hợp với môi trường XAMPP subfolder
     */
    public function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Loại bỏ query string nếu có (?foo=bar)
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Tự động loại bỏ tiền tố thư mục trên XAMPP (ví dụ: /CNPM/internship-management/public)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        if ($baseDir !== '/' && $baseDir !== '\\' && strpos($uri, $baseDir) === 0) {
            $uri = substr($uri, strlen($baseDir));
        }

        return $this->normalizePath($uri);
    }

    /**
     * Điều hướng và thực thi route khớp với request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $this->getCurrentUri();

        $handler = $this->routes[$method][$uri] ?? null;

        if ($handler === null) {
            $this->sendNotFound();
            return;
        }

        if (is_callable($handler)) {
            call_user_func($handler);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;

            if (class_exists($class)) {
                $controller = new $class();
                if (method_exists($controller, $action)) {
                    $controller->$action();
                    return;
                }
            }
        }

        $this->sendNotFound();
    }

    /**
     * Trả về phản hồi 404 khi không tìm thấy route
     */
    private function sendNotFound(): void
    {
        http_response_code(404);
        echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>404 - Không tìm thấy trang</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; text-align: center; padding: 60px 20px; background-color: #f8fafc; color: #334155; }
        .box { max-width: 500px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { font-size: 4rem; margin: 0; color: #e11d48; }
        p { font-size: 1.1rem; line-height: 1.6; }
        a { display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #2563eb; color: #fff; text-decoration: none; border-radius: 6px; }
        a:hover { background-color: #1d4ed8; }
    </style>
</head>
<body>
    <div class='box'>
        <h1>404</h1>
        <h2>Đường dẫn không tồn tại</h2>
        <p>Yêu cầu truy cập của bạn không khớp với bất kỳ tuyến đường (route) nào đã đăng ký trong hệ thống.</p>
        <a href='./'>Về trang chủ</a>
    </div>
</body>
</html>";
    }
}
