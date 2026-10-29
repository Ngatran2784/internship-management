<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * UserController - Phụ trách quản lý thông tin người dùng và phân quyền vai trò
 * 
 * PHÂN CÔNG: Thành viên 2 (Backend & Database)
 * SPRINT: Sprint 1
 */
class UserController extends Controller
{
    /**
     * Hiển thị danh sách người dùng trong hệ thống (Dành cho Quản trị viên)
     * 
     * TODO [TV2 - Task #104]:
     * - Kiểm tra quyền truy cập (chỉ Admin/Quản trị viên được xem).
     * - Lấy danh sách users từ User model có kèm danh sách vai trò từ user_roles.
     * - Gọi $this->render('users/index', ['users' => $users]) để hiển thị.
     */
    public function index(): void
    {
        // TODO: Triển khai kiểm tra phân quyền và truy vấn danh sách người dùng
    }

    /**
     * Xem và cập nhật thông tin hồ sơ cá nhân
     * 
     * TODO [TV2 - Task #105]:
     * - Lấy thông tin user hiện tại từ session.
     * - Cho phép cập nhật: họ tên, số điện thoại, avatar...
     * - Validate dữ liệu cập nhật và gọi Model để lưu trữ.
     */
    public function profile(): void
    {
        // TODO: Triển khai hiển thị và cập nhật hồ sơ cá nhân
    }

    /**
     * Gán vai trò cho người dùng (Chức năng phân quyền)
     * 
     * TODO [TV2 - Task #106]:
     * - Nhận user_id và role_ids từ request POST.
     * - Kiểm tra ràng buộc hợp lệ và gọi User::assignRoles($userId, $roleIds).
     */
    public function assignRoles(): void
    {
        // TODO: Triển khai gán vai trò vào bảng user_roles
    }
}
