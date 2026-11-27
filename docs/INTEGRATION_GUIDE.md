# HƯỚNG DẪN TÍCH HỢP VÀ PHÁT TRIỂN HỆ THỐNG (INTEGRATION GUIDE)
## DÀNH CHO CÁC LẬP TRÌNH VIÊN PHÁT TRIỂN PHẦN MỀM

- **Dự án:** Website Quản lý và Theo dõi Tiến độ Thực tập & Đồ án Sinh viên
- **Môi trường:** PHP 8.2 | MariaDB 10.4 | PDO | Tabler UI
- **Nguyên tắc phát triển:** Không thay đổi các lớp lõi (`app/Core/`), sử dụng Prepared Statements và hỗ trợ giao dịch lồng.

---

## 1. CÁC THÀNH PHẦN NGHIỆP VỤ CÓ SẴN ĐỂ TÁI SỬ DỤNG (REUSABLE COMPONENTS)

### 1.1. Module Mốc tiến độ (Milestones & Timeline)
- **Model:** `App\Models\Milestone`
  * `Milestone::all(?int $periodId = null, ?string $status = null, ?string $keyword = null): array`: Lấy danh sách mốc có lọc và tìm kiếm.
  * `Milestone::findById(int $id): ?array`: Xem chi tiết một mốc thời gian kèm người tạo và đợt áp dụng.
  * `Milestone::getStudentMilestones(int $studentId, ?int $periodId = null): array`: Lấy mốc kèm trạng thái bài nộp của sinh viên (`student_progress_state`: `'completed'`, `'submitted'`, `'overdue'`, `'in_progress'`, `'pending'`).
  * `Milestone::getProgressStats(int $periodId, ?int $studentId = null): array`: Thống kê tỷ lệ hoàn thành % chung của đợt và riêng của sinh viên.
  * `Milestone::syncOverdueStatus(int $periodId): int`: Đồng bộ mốc quá hạn sang trạng thái `overdue`.
- **REST API Endpoint:**
  ```http
  GET /api/milestones/progress?period_id={period_id}&student_id={student_id}
  ```
  Trả về JSON chứa dữ liệu thống kê phục vụ dựng Widget và Timeline UI:
  ```json
  {
    "ok": true,
    "period_id": 11,
    "student_id": 6,
    "statistics": {
      "total_milestones": 4,
      "completed_milestones": 2,
      "completion_rate": 50.0,
      "student_rate": 25.0
    },
    "milestones": [ ... ]
  }
  ```

---

### 1.2. Module Nhật ký thực tập (Internship Logs)
- **Model:** `App\Models\InternshipLog`
  * `InternshipLog::getByStudent(int $studentId, int $periodId): array`: Lấy toàn bộ nhật ký theo tuần của sinh viên.
  * `InternshipLog::getByLecturer(int $lecturerId, int $periodId, ?string $status = null): array`: Lấy danh sách nhật ký nộp cho GVHD duyệt.
  * `InternshipLog::create(array $data): int`: Tạo mới nhật ký tuần (tự kiểm tra chống nộp trùng tuần qua ràng buộc duy nhất).
  * `InternshipLog::confirm(int $id, int $lecturerId, ?string $comment = null): bool`: Người hướng dẫn xác nhận nhật ký.
  * `InternshipLog::reject(int $id, int $lecturerId, ?string $comment = null): bool`: Người hướng dẫn yêu cầu sinh viên viết lại.

---

### 1.3. Module Báo cáo & Đa phiên bản (Reports & Submissions)
- **Model:** `App\Models\InternshipReport`
  * `InternshipReport::submit(array $reportData, array $fileInfo): array`: Tự động so sánh hạn chót `milestones.due_date` để gắn cờ `is_late = 1` nếu nộp trễ; tự động tăng số phiên bản (`v1, v2...`), bảo lưu nguyên vẹn tệp và bản ghi cũ.
  * `InternshipReport::getSubmissions(int $reportId): array`: Lấy toàn bộ lịch sử các lần nộp.
  * `InternshipReport::getReviews(int $reportId): array`: Lấy lịch sử đánh giá của người hướng dẫn.
  * `InternshipReport::getByStudentAndMilestone(int $studentId, int $milestoneId): ?array`: Kiểm tra báo cáo theo mốc.

- **Dịch vụ Tải lên tệp an toàn:** `App\Services\ReportUploadService`
  ```php
  use App\Services\ReportUploadService;

  // 1. Kiểm tra tính hợp lệ của tệp (dung lượng < 20MB, đuôi .pdf, .docx, .zip, MIME type thực)
  $errors = ReportUploadService::validateFile($_FILES['report_file'] ?? []);
  if (!empty($errors)) {
      // Xử lý báo lỗi
  }

  // 2. Lưu tệp vào thư mục máy chủ với tên băm SHA-256 an toàn
  $fileInfo = ReportUploadService::processAndStore(
      $_FILES['report_file'],
      $periodId,
      $studentId,
      $milestoneId,
      $nextVersion
  );
  ```

---

### 1.4. Module Nhận xét & Đánh giá (Report Reviews)
- **Model:** `App\Models\ReportReview`
  * `ReportReview::create(array $data): int`: Lưu đánh giá (`decision`: `'approved'`, `'rejected'`, `'resubmit_required'`, `'comment_only'`), lưu điểm số (`score`), yêu cầu chỉnh sửa (`edit_requirements`), đồng thời tự động cập nhật trạng thái `internship_reports.status` và điểm chốt.
  * `ReportReview::getByReport(int $reportId): array`: Lấy lịch sử đánh giá.

---

### 1.5. Module Phân công & Thuật toán đối sánh (Matching Engine)
- **Service:** `App\Services\TopicMatchingService`
  * `matchPeriod(int $periodId, array $options = []): array`: Thực thi thuật toán ghép cặp tự động đối sánh nguyện vọng theo điểm GPA và thời gian đăng ký.
- **Model:** `App\Models\TopicAssignment`
  * `TopicAssignment::assignManual(...)`: Phân công thủ công sinh viên vào đề tài.
  * `TopicAssignment::publishPeriodAssignments(int $periodId, int $publishedBy): int`: Công bố kết quả chính thức cho toàn đợt.
  * `TopicAssignment::getByStudent(int $studentId, int $periodId, bool $requirePublished = true): ?array`: Sinh viên tra cứu kết quả phân công.

---

## 2. QUY CHUẨN XÂY DỰNG TÍNH NĂNG MỚI (DEVELOPMENT GUIDELINES)

### 2.1. Quy trình tạo Controller mới
1. Đặt Controller trong thư mục `app/Controllers/` và kế thừa từ `App\Core\Controller`.
2. Kiểm tra quyền truy cập ở đầu mỗi action qua `AuthController`:
   ```php
   public function index(): void
   {
       AuthController::requireRole('lecturer', 'department_head');
       // ... xử lý nghiệp vụ ...
       $this->view('reviews/index', [
           'title' => 'Đánh giá báo cáo thực tập',
           'items' => $items,
       ]);
   }
   ```
3. Đăng ký route trong [public/index.php](file:///c:/xampp/htdocs/CNPM/internship-management/public/index.php):
   ```php
   $router->get('/reviews', [ReviewController::class, 'index']);
   $router->post('/reviews/store', [ReviewController::class, 'store']);
   ```

### 2.2. Quy trình dựng Giao diện (Views)
1. Đặt View trong thư mục con tương ứng tại `app/Views/` (ví dụ: `app/Views/reviews/index.php`).
2. Sử dụng layout chuẩn Tabler UI:
   - Nhúng layout dùng chung hoặc bọc trong thẻ container của hệ thống.
   - Luôn sử dụng hàm mã hóa HTML an toàn `$escape(...)` khi xuất dữ liệu người dùng nhằm phòng chống XSS.
   - Đặt cờ `$activePage` hoặc kiểm tra `$currentRoute` để menu Sidebar tô sáng chính xác mục đang mở.

### 2.3. Quy định xử lý Giao dịch CSDL (Transaction Safety)
Khi thực hiện chuỗi nhiều câu lệnh ghi dữ liệu liên quan, luôn sử dụng kiểm tra giao dịch:
```php
$pdo = Database::getConnection();
$isOwnTransaction = false;

if (!$pdo->inTransaction()) {
    $pdo->beginTransaction();
    $isOwnTransaction = true;
}

try {
    // Thực thi các câu lệnh INSERT / UPDATE...
    if ($isOwnTransaction) {
        $pdo->commit();
    }
} catch (\Throwable $e) {
    if ($isOwnTransaction) {
        $pdo->rollBack();
    }
    throw $e;
}
```
