# DANH SÁCH CA KIỂM THỬ HỆ THỐNG (TEST CASES SPECIFICATION)
## DỰ ÁN: WEBSITE QUẢN LÝ VÀ THEO DÕI TIẾN ĐỘ THỰC TẬP & ĐỒ ÁN SINH VIÊN

- **Hệ thống:** Website Quản lý Thực tập & Đồ án Sinh viên (Trường Đại học Vinh)
- **Môi trường:** PHP 8.2, MariaDB 10.4 (XAMPP), UTF-8 / utf8mb4
- **Công cụ kiểm thử tự động:** PHP CLI Automated Test Suites

---

## 1. BẢNG TỔNG HỢP CA KIỂM THỬ TOÀN HỆ THỐNG

| Phân hệ chức năng | Số ca nghiệp vụ | Số Assertion Checks | Kết quả | Tệp kịch bản kiểm thử |
| :--- | :---: | :---: | :---: | :--- |
| **1. Xác thực, Đăng nhập & RBAC** | 5 | 5 | **5 PASS** | `tests/test_core_features_acceptance.php` |
| **2. Quản lý Tài khoản & Đợt thực tập** | 4 | 4 | **4 PASS** | `tests/test_core_features_acceptance.php` |
| **3. Đề tài & Đăng ký nguyện vọng** | 4 | 4 | **4 PASS** | `tests/test_core_features_acceptance.php` |
| **4. Phân công & Thuật toán đối sánh** | 8 | 83 | **83 PASS** | `tests/test_assignment_matching.php` |
| **5. Kết quả phân công của Sinh viên** | 3 | 5 | **5 PASS** | `tests/test_student_my_result.php` |
| **6. Quản lý Mốc tiến độ (Milestones)** | 24 | 24 | **24 PASS** | `tests/test_milestone_management.php` |
| **7. Tải lên tệp báo cáo an toàn** | 8 | 13 | **13 PASS** | `tests/test_progress_and_reports.php` |
| **8. Phân quyền truy cập mốc tiến độ** | 6 | 6 | **6 PASS** | `tests/test_progress_and_reports.php` |
| **9. Hạn nộp & Lịch sử duyệt đa phiên bản**| 5 | 5 | **5 PASS** | `tests/test_progress_and_reports.php` |
| **10. Xác thực dữ liệu đầu vào** | 5 | 5 | **5 PASS** | `tests/test_progress_and_reports.php` |
| **11. Theo dõi tích hợp giao diện khác** | 4 | 4 | **4 PENDING** | `tests/test_progress_and_reports.php` |

---

## 2. CHI TIẾT CÁC CA KIỂM THỬ THEO TỪNG PHÂN HỆ

### 2.1. Phân hệ Xác thực & Phân quyền Vai trò
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-AUTH-01** | Đăng nhập tài khoản hợp lệ | Username `admin` và mật khẩu đúng | Đăng nhập thành công, thiết lập session | **PASS** |
| **TC-AUTH-02** | Chặn mật khẩu không khớp | Nhập sai mật khẩu | Từ chối đăng nhập, báo lỗi | **PASS** |
| **TC-AUTH-03** | Đầy đủ 6 vai trò hệ thống | Truy vấn bảng `roles` | Đủ 6 vai trò: admin, pdt, tbm, gv, sv, dn | **PASS** |
| **TC-AUTH-04** | Tài khoản đa vai trò kiêm nhiệm| Tài khoản `tbm_gv` | Có đồng thời vai trò TBM và Giảng viên | **PASS** |
| **TC-AUTH-05** | Chặn sinh viên vào quản lý người dùng | Tài khoản sinh viên truy cập `/users` | Chặn truy cập với HTTP 403 Forbidden | **PASS** |

### 2.2. Phân hệ Quản lý Tài khoản & Đợt thực tập / Đồ án
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-USER-01** | Tạo tài khoản & gán vai trò | Nhập thông tin và gán vai trò student | Bản ghi được tạo, liên kết bảng user_roles | **PASS** |
| **TC-USER-02** | Khóa và mở khóa tài khoản | Cập nhật `status = 0` rồi `status = 1` | Bản ghi cập nhật chính xác trạng thái | **PASS** |
| **TC-PER-01** | Tạo đợt thực tập mới | Nhập mã đợt, tên, ngày hợp lệ | Đợt được tạo thành công với trạng thái active | **PASS** |
| **TC-PER-02** | Sinh viên xem đợt đang mở | Truy vấn đợt active trong thời hạn | Trả về đợt đang mở và thời hạn đăng ký | **PASS** |

### 2.3. Phân hệ Đề xuất, Duyệt đề tài & Đăng ký Nguyện vọng
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-TOP-01** | Giảng viên đề xuất đề tài | Tạo đề tài và gọi `submit()` | Đề tài chuyển sang trạng thái pending | **PASS** |
| **TC-TOP-02** | Trưởng bộ môn phê duyệt | Gọi `review()` với quyết định approved | Đề tài chuyển sang approved, lưu lịch sử | **PASS** |
| **TC-TOP-03** | Doanh nghiệp đề xuất đề tài | Doanh nghiệp tạo đề tài tiếp nhận | Đề tài được tạo và phê duyệt thành công | **PASS** |
| **TC-WSH-01** | Sinh viên đăng ký nguyện vọng | Sinh viên đăng ký NV1 và NV2 | Lưu đúng 2 nguyện vọng theo thứ tự ưu tiên | **PASS** |

### 2.4. Phân hệ Phân công & Thuật toán Đối sánh Nguyện vọng
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-ASG-01** | Chạy thuật toán đối sánh tự động | Gọi `TopicMatchingService::matchPeriod` | Ghép cặp thành công, lưu trạng thái draft | **PASS** |
| **TC-ASG-02** | Phân công thủ công sinh viên | Quản lý gán sinh viên vào đề tài | Phân công thành công khi đề tài còn chỉ tiêu | **PASS** |
| **TC-ASG-03** | Bảo mật kết quả bản dự thảo | Sinh viên tra cứu khi đợt còn draft | Kết quả trả về rỗng (null), bảo mật thông tin | **PASS** |
| **TC-ASG-04** | Công bố kết quả phân công | Phòng Đào tạo gọi `publishPeriodAssignments` | Toàn bộ phân công chuyển sang published | **PASS** |
| **TC-ASG-05** | Sinh viên xem kết quả chính thức | Sinh viên tra cứu sau khi công bố | Thấy rõ đề tài chính thức và GV hướng dẫn | **PASS** |
| **TC-ASG-06** | Giảng viên xem danh sách hướng dẫn | Giảng viên tra cứu sinh viên của mình | Lọc chính xác danh sách sinh viên được giao | **PASS** |
| **TC-ASG-07** | Doanh nghiệp xem sinh viên tiếp nhận | Doanh nghiệp tra cứu sinh viên của đơn vị | Xem đúng danh sách sinh viên thực tập | **PASS** |
| **TC-ASG-08** | Điều chỉnh / Hủy phân công | Gọi `cancelAssignment` | Hủy thành công để điều chỉnh lại nguyện vọng | **PASS** |

### 2.5. Phân hệ Quản lý Mốc tiến độ (Milestones)
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-MS-01** | Xem toàn bộ danh sách mốc tiến độ | Gọi `Milestone::all()` | Trả về danh sách mốc hợp lệ | **PASS** |
| **TC-MS-02** | Lọc mốc tiến độ theo đợt | Gọi `Milestone::all(11)` | Chỉ trả về mốc của Đợt 11 | **PASS** |
| **TC-MS-03** | Lọc mốc tiến độ theo trạng thái | Lọc theo status = 'completed' | Trả về các mốc đã hoàn thành | **PASS** |
| **TC-MS-04** | Tìm kiếm mốc theo từ khóa | Tìm kiếm từ khóa 'đề cương' | Tìm thấy mốc chứa từ khóa | **PASS** |
| **TC-MS-05** | Chặn tạo mốc khi bỏ trống tiêu đề | `title = ''` | Báo lỗi tiêu đề bắt buộc | **PASS** |
| **TC-MS-06** | Chặn hạn nộp sớm hơn ngày bắt đầu | `start_date > due_date` | Báo lỗi logic thời hạn | **PASS** |
| **TC-MS-07** | Tạo mới mốc tiến độ hợp lệ | Nhập đầy đủ thông tin hợp lệ | Lưu thành công vào CSDL | **PASS** |
| **TC-MS-08** | Cập nhật thông tin mốc tiến độ | Sửa tiêu đề, mô tả | Bản ghi được cập nhật chính xác | **PASS** |
| **TC-MS-09** | Tự động đồng bộ mốc quá hạn | Quét mốc có `due_date < NOW()` | Tự động chuyển trạng thái sang overdue | **PASS** |
| **TC-MS-10** | Chặn xóa mốc khi đã có báo cáo nộp | Gọi `delete()` với mốc đã có bài nộp | Ném ngoại lệ, từ chối xóa để bảo vệ dữ liệu | **PASS** |
| **TC-MS-11** | Xóa mốc hợp lệ khi chưa có báo cáo | Gọi `delete()` với mốc rỗng | Xóa thành công khỏi CSDL | **PASS** |
| **TC-MS-12** | Thống kê tiến độ % hoàn thành | Gọi `Milestone::getProgressStats` | Trả về tỷ lệ % chung và riêng của SV | **PASS** |

### 2.6. Phân hệ Tải lên Tệp Báo cáo & Đa phiên bản
| Mã ca | Tên ca kiểm thử | Dữ liệu kiểm thử | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-UP-01** | Chấp nhận tệp định dạng hợp lệ | File .pdf, .docx, .zip | Xác thực hợp lệ, cho phép lưu trữ | **PASS** |
| **TC-UP-02** | Chặn tệp không hợp lệ / độc hại | File .exe, .php, .jpg | Báo lỗi từ chối tệp không đúng định dạng | **PASS** |
| **TC-UP-03** | Chặn tệp vượt dung lượng | File 25MB (> 20MB) | Báo lỗi vượt quá dung lượng cho phép | **PASS** |
| **TC-UP-04** | Báo lỗi khi không chọn tệp | Gửi form rỗng | Báo lỗi yêu cầu chọn tệp | **PASS** |
| **TC-UP-05** | Quản lý đa phiên bản không ghi đè | Nộp v1 rồi nộp lại v2 | Cả v1 và v2 đều lưu nguyên vẹn trong CSDL | **PASS** |

### 2.7. Phân hệ Hạn nộp & Lịch sử Đánh giá
| Mã ca | Tên ca kiểm thử | Điều kiện / Dữ liệu đầu vào | Kết quả mong đợi | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **TC-DL-01** | Nộp trước hạn chót | Thời điểm nộp < `due_date` | Hệ thống tự động ghi nhận `is_late = 0` | **PASS** |
| **TC-DL-02** | Nộp sau hạn chót | Thời điểm nộp > `due_date` | Vẫn cho phép nộp nhưng gắn cờ `is_late = 1` | **PASS** |
| **TC-DL-03** | Lưu yêu cầu nộp lại | Quyết định `resubmit_required` | Báo cáo chính chuyển sang resubmit_required | **PASS** |
| **TC-DL-04** | Lưu nhận xét và điểm số | Nhập nhận xét và điểm 9.0 | Báo cáo chuyển sang approved, cập nhật điểm | **PASS** |

---

## 3. HƯỚNG DẪN THỰC THI TOÀN BỘ BỘ KIỂM THỬ

Lập trình viên có thể chạy kiểm thử tự động toàn bộ hệ thống bằng các lệnh CLI:

```bash
# 1. Kiểm thử xác minh di chuyển CSDL sạch
php tests/test_clean_migration.php

# 2. Kiểm thử tổng hợp các chức năng cốt lõi
php tests/test_core_features_acceptance.php

# 3. Kiểm thử tiến độ, upload báo cáo, phân quyền và hạn nộp
php tests/test_progress_and_reports.php

# 4. Kiểm thử thuật toán phân công đề tài
php tests/test_assignment_matching.php

# 5. Kiểm thử màn hình kết quả sinh viên và sidebar
php tests/test_student_my_result.php
```
