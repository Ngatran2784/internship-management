<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * AuthController - Phụ trách xác thực người dùng (Login, Logout, Session)
 * 
 * PHÂN CÔNG: Thành viên 2 (Backend & Database)
 * SPRINT: Sprint 1
 */
class AuthController extends Controller
{
    /**
     * Hiển thị giao diện đăng nhập
     * 
     * TODO [TV2 - Task #101]:
     * - Kiểm tra nếu người dùng đã đăng nhập (session có user_id), redirect về '/dashboard'.
     * - Gọi $this->render('auth/login', $data) để hiển thị form đăng nhập do TV3 thiết kế.
     */
    public function showLoginForm(): void
    {
        // TODO: Triển khai kiểm tra session và render view login
    }

    /**
     * Xử lý xác thực đăng nhập người dùng (POST)
     * 
     * TODO [TV2 - Task #102]:
     * - Nhận dữ liệu POST: username/email và password.
     * - Validate dữ liệu đầu vào (không được để trống, đúng định dạng).
     * - Sử dụng User Model (App\Models\User) để tìm user theo username hoặc email.
     * - Kiểm tra mật khẩu hash bằng password_verify($password, $user['password']).
     * - Nếu hợp lệ: lưu thông tin định danh và vai trò vào $_SESSION, redirect sang '/dashboard'.
     * - Nếu thất bại: ghi nhận flash message lỗi và redirect về '/login'.
     */
    public function login(): void
    {
        // TODO: Triển khai logic xử lý đăng nhập và gán session
    }

    /**
     * Xử lý đăng xuất khỏi hệ thống
     * 
     * TODO [TV2 - Task #103]:
     * - Xóa dữ liệu session của người dùng (session_unset(), session_destroy()).
     * - Redirect người dùng về trang đăng nhập '/login'.
     */
    public function logout(): void
    {
        // TODO: Triển khai hủy session đăng nhập
    }
}
