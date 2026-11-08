<?php

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;

/**
 * Topic Model - Quản lý bảng `topics` (Đề tài thực tập & đồ án tốt nghiệp)
 *
 * Chức năng:
 * - Lưu trữ và quản lý danh mục đề tài do Giảng viên, Bộ môn, Doanh nghiệp hoặc Sinh viên đề xuất.
 * - Liên kết toàn vẹn dữ liệu với đợt thực tập/đồ án (`internship_periods`) và người đề xuất (`users`).
 * - Toàn bộ truy vấn sử dụng PDO Prepared Statements phòng chống SQL Injection.
 *
 * Quy tắc phân quyền & trạng thái:
 * - draft: Bản nháp (người đề xuất có thể chỉnh sửa hoặc xóa).
 * - pending: Chờ duyệt (đã gửi lên Hội đồng / Bộ môn / Phòng đào tạo).
 * - approved: Đã duyệt (đề tài được công khai để sinh viên đăng ký).
 * - rejected: Từ chối (đề tài không được duyệt, có thể trả về cho người đề xuất điều chỉnh).
 */
class Topic
{
    public const STATUS_DRAFT    = 'draft';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_INTERNSHIP         = 'internship';
    public const TYPE_GRADUATION_PROJECT = 'graduation_project';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const ALLOWED_TYPES = [
        self::TYPE_INTERNSHIP,
        self::TYPE_GRADUATION_PROJECT,
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT    => 'Bản nháp',
        self::STATUS_PENDING  => 'Chờ duyệt',
        self::STATUS_APPROVED => 'Đã duyệt',
        self::STATUS_REJECTED => 'Từ chối',
    ];

    public const STATUS_BADGES = [
        self::STATUS_DRAFT    => 'bg-secondary-lt text-secondary',
        self::STATUS_PENDING  => 'bg-warning-lt text-warning',
        self::STATUS_APPROVED => 'bg-success-lt text-success',
        self::STATUS_REJECTED => 'bg-danger-lt text-danger',
    ];

    public const TYPE_LABELS = [
        self::TYPE_INTERNSHIP         => 'Thực tập tốt nghiệp',
        self::TYPE_GRADUATION_PROJECT => 'Đồ án tốt nghiệp',
    ];

    public const TYPE_BADGES = [
        self::TYPE_INTERNSHIP         => 'bg-blue-lt text-blue',
        self::TYPE_GRADUATION_PROJECT => 'bg-purple-lt text-purple',
    ];

    /**
     * Lấy danh sách đề tài kèm bộ lọc và tìm kiếm
     *
     * @param array $filters [
     *     'period_id'   => int|null,
     *     'status'      => string|null,
     *     'type'        => string|null,
     *     'proposer_id' => int|null,
     *     'keyword'     => string|null
     * ]
     * @return array Danh sách đề tài kèm thông tin đợt và người đề xuất
     */
    public static function all(array $filters = []): array
    {
        $pdo = Database::getConnection();
        [$where, $params] = self::buildWhere($filters);

        $sql = 'SELECT
                    t.*,
                    p.code AS period_code,
                    p.name AS period_name,
                    p.status AS period_status,
                    p.type AS period_type,
                    u.username AS proposer_username,
                    u.full_name AS proposer_name,
                    u.email AS proposer_email
                FROM `topics` t
                INNER JOIN `internship_periods` p ON t.period_id = p.id
                INNER JOIN `users` u ON t.proposer_id = u.id'
            . $where . ' ORDER BY t.created_at DESC, t.id DESC';

        if (isset($filters['limit'])) {
            $limit = max(1, min(100, (int)$filters['limit']));
            $offset = max(0, (int)($filters['offset'] ?? 0));
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy một trang đề tài và tổng số kết quả sau lọc.
     */
    public static function paginate(array $filters, int $page, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        [$where, $params] = self::buildWhere($filters);
        $pdo = Database::getConnection();

        $count = $pdo->prepare(
            'SELECT COUNT(*) FROM `topics` t
             INNER JOIN `internship_periods` p ON t.period_id = p.id
             INNER JOIN `users` u ON t.proposer_id = u.id' . $where
        );
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);

        $items = self::all(array_merge($filters, [
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => $pages,
        ];
    }

    /**
     * Đếm đề tài đã duyệt theo loại; loại đề tài và trạng thái là hai trường riêng.
     *
     * @return array<string, int>
     */
    public static function approvedCountsByType(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT `type`, COUNT(*) AS `total`
               FROM `topics`
              WHERE `status` = :status
           GROUP BY `type`'
        );
        $stmt->execute(['status' => self::STATUS_APPROVED]);

        $counts = array_fill_keys(self::ALLOWED_TYPES, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[$row['type']] = (int)$row['total'];
        }

        return $counts;
    }

    /**
     * Tạo phần điều kiện dùng chung cho danh sách và đếm phân trang.
     */
    private static function buildWhere(array $filters): array
    {
        $where = ' WHERE 1=1';
        $params = [];

        if (!empty($filters['period_id']) && (int)$filters['period_id'] > 0) {
            $where .= ' AND t.period_id = :period_id';
            $params['period_id'] = (int)$filters['period_id'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            $where .= ' AND t.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['type']) && in_array($filters['type'], self::ALLOWED_TYPES, true)) {
            $where .= ' AND t.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['proposer_id']) && (int)$filters['proposer_id'] > 0) {
            $where .= ' AND t.proposer_id = :proposer_id';
            $params['proposer_id'] = (int)$filters['proposer_id'];
        }

        if (!empty($filters['keyword'])) {
            $kw = '%' . trim((string)$filters['keyword']) . '%';
            $where .= ' AND (t.code LIKE :kw_code OR t.name LIKE :kw_name OR t.description LIKE :kw_desc OR u.full_name LIKE :kw_user)';
            $params['kw_code'] = $kw;
            $params['kw_name'] = $kw;
            $params['kw_desc'] = $kw;
            $params['kw_user'] = $kw;
        }

        if (!empty($filters['deny_visibility'])) {
            $where .= ' AND 1=0';
        } elseif (!empty($filters['student_visible'])) {
            $where .= ' AND t.status = :student_status AND p.status = :student_period_status';
            $params['student_status'] = self::STATUS_APPROVED;
            $params['student_period_status'] = InternshipPeriod::STATUS_ACTIVE;
        } elseif (isset($filters['visible_to']) && (int)$filters['visible_to'] > 0) {
            $where .= ' AND (t.status = :visible_status OR t.proposer_id = :visible_user_id)';
            $params['visible_status'] = self::STATUS_APPROVED;
            $params['visible_user_id'] = (int)$filters['visible_to'];
        }

        return [$where, $params];
    }

    /**
     * Kiểm tra quyền xem một đề tài theo contract phân quyền topics.
     */
    public static function canView(array $topic, int $userId, array $userRoles): bool
    {
        if (in_array('admin', $userRoles, true) || in_array('training_dept', $userRoles, true)) {
            return true;
        }

        if (in_array('student', $userRoles, true)
            && !array_intersect(['department_head', 'lecturer', 'enterprise'], $userRoles)) {
            return $topic['status'] === self::STATUS_APPROVED
                && ($topic['period_status'] ?? null) === InternshipPeriod::STATUS_ACTIVE;
        }

        if (array_intersect(['department_head', 'lecturer', 'enterprise'], $userRoles)) {
            return $topic['status'] === self::STATUS_APPROVED || (int)$topic['proposer_id'] === $userId;
        }

        return false;
    }

    /**
     * Tìm chi tiết đề tài theo ID khóa chính
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();

        $sql = 'SELECT 
                    t.*,
                    p.code AS period_code,
                    p.name AS period_name,
                    p.status AS period_status,
                    p.type AS period_type,
                    u.username AS proposer_username,
                    u.full_name AS proposer_name,
                    u.email AS proposer_email
                FROM `topics` t
                INNER JOIN `internship_periods` p ON t.period_id = p.id
                INNER JOIN `users` u ON t.proposer_id = u.id
                WHERE t.id = :id
                LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Tìm đề tài theo mã đề tài định danh (không phân biệt chữ hoa/thường)
     */
    public static function findByCode(string $code, ?int $excludeId = null): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $pdo = Database::getConnection();
        $sql = 'SELECT * FROM `topics` WHERE UPPER(TRIM(`code`)) = UPPER(TRIM(:code))';
        $params = ['code' => $code];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND `id` <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Kiểm tra mã đề tài đã tồn tại trong hệ thống hay chưa
     */
    public static function existsCode(string $code, ?int $excludeId = null): bool
    {
        return self::findByCode($code, $excludeId) !== null;
    }

    /**
     * Kiểm tra tính hợp lệ của dữ liệu đề tài
     *
     * @param array $data Dữ liệu đầu vào
     * @param bool $isUpdate Đang thực hiện cập nhật hay tạo mới
     * @param int|null $currentId ID đề tài hiện tại nếu là cập nhật
     * @return array Danh sách lỗi (trống nếu hoàn toàn hợp lệ)
     */
    public static function validate(array $data, bool $isUpdate = false, ?int $currentId = null): array
    {
        $errors = [];

        // 1. Mã đề tài (code)
        $code = isset($data['code']) ? trim((string)$data['code']) : '';
        if ($code === '') {
            $errors['code'] = 'Mã đề tài là bắt buộc và không được để trống.';
        } elseif (mb_strlen($code) < 2 || mb_strlen($code) > 50) {
            $errors['code'] = 'Mã đề tài phải có độ dài từ 2 đến 50 ký tự.';
        } elseif (!preg_match('/^[A-Za-z0-9_\-\.]+$/', $code)) {
            $errors['code'] = 'Mã đề tài chỉ được chứa chữ cái, chữ số, dấu gạch dưới, gạch ngang và dấu chấm.';
        } elseif (self::existsCode($code, $currentId)) {
            $errors['code'] = "Mã đề tài '{$code}' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.";
        }

        // 2. Tên đề tài (name)
        $name = isset($data['name']) ? trim((string)$data['name']) : '';
        if ($name === '') {
            $errors['name'] = 'Tên đề tài là bắt buộc và không được để trống.';
        } elseif (mb_strlen($name) < 5 || mb_strlen($name) > 255) {
            $errors['name'] = 'Tên đề tài phải có độ dài từ 5 đến 255 ký tự.';
        }

        // 3. Loại đề tài (type)
        $type = isset($data['type']) ? trim((string)$data['type']) : '';
        if ($type === '' || !in_array($type, self::ALLOWED_TYPES, true)) {
            $errors['type'] = 'Loại đề tài không hợp lệ. Chỉ chấp nhận Thực tập tốt nghiệp hoặc Đồ án tốt nghiệp.';
        }

        // 4. Đợt thực tập / đồ án liên kết (period_id)
        $periodId = isset($data['period_id']) ? filter_var($data['period_id'], FILTER_VALIDATE_INT) : false;
        if ($periodId === false || $periodId <= 0) {
            $errors['period_id'] = 'Vui lòng chọn đợt thực tập / đồ án liên kết hợp lệ.';
        } else {
            $pdo = Database::getConnection();
            $periodStmt = $pdo->prepare('SELECT id, status, type FROM `internship_periods` WHERE id = :pid LIMIT 1');
            $periodStmt->execute(['pid' => $periodId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);

            if (!$period) {
                $errors['period_id'] = 'Đợt thực tập / đồ án được chọn không tồn tại trong hệ thống.';
            }
        }

        // 5. Người đề xuất (proposer_id)
        if (!$isUpdate || isset($data['proposer_id'])) {
            $proposerId = isset($data['proposer_id']) ? filter_var($data['proposer_id'], FILTER_VALIDATE_INT) : false;
            if ($proposerId === false || $proposerId <= 0) {
                $errors['proposer_id'] = 'Người đề xuất đề tài không hợp lệ.';
            } else {
                $pdo = Database::getConnection();
                $userStmt = $pdo->prepare('SELECT id, status FROM `users` WHERE id = :uid LIMIT 1');
                $userStmt->execute(['uid' => $proposerId]);
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $errors['proposer_id'] = 'Tài khoản người đề xuất không tồn tại trong hệ thống.';
                }
            }
        }

        // 6. Số lượng sinh viên tối đa (max_students)
        if (isset($data['max_students']) && $data['max_students'] !== '') {
            $maxStudents = filter_var($data['max_students'], FILTER_VALIDATE_INT);
            if ($maxStudents === false || $maxStudents < 1 || $maxStudents > 10) {
                $errors['max_students'] = 'Số lượng sinh viên tối đa phải là số nguyên từ 1 đến 10.';
            }
        }

        // 7. Trạng thái (status)
        if (isset($data['status']) && $data['status'] !== '') {
            if (!in_array($data['status'], self::ALLOWED_STATUSES, true)) {
                $errors['status'] = 'Trạng thái đề tài không hợp lệ.';
            }
        }

        return $errors;
    }

    /**
     * Thêm mới đề tài vào cơ sở dữ liệu
     *
     * @param array $data
     * @return int ID của đề tài vừa tạo
     * @throws InvalidArgumentException khi dữ liệu không hợp lệ
     */
    public static function create(array $data): int
    {
        $errors = self::validate($data, false);
        if (!empty($errors)) {
            $firstError = reset($errors);
            throw new InvalidArgumentException($firstError);
        }

        $code        = strtoupper(trim((string)$data['code']));
        $name        = trim((string)$data['name']);
        $description = isset($data['description']) ? trim((string)$data['description']) : null;
        if ($description === '') {
            $description = null;
        }
        $type        = trim((string)$data['type']);
        $periodId    = (int)$data['period_id'];
        $proposerId  = (int)$data['proposer_id'];
        $maxStudents = isset($data['max_students']) && (int)$data['max_students'] > 0 ? (int)$data['max_students'] : 1;
        $status      = isset($data['status']) && in_array($data['status'], self::ALLOWED_STATUSES, true)
            ? $data['status']
            : self::STATUS_DRAFT;

        $pdo = Database::getConnection();
        $sql = 'INSERT INTO `topics` (
                    `code`, `name`, `description`, `type`, `period_id`, `proposer_id`, `max_students`, `status`, `created_at`, `updated_at`
                ) VALUES (
                    :code, :name, :description, :type, :period_id, :proposer_id, :max_students, :status, NOW(), NOW()
                )';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'code'         => $code,
            'name'         => $name,
            'description'  => $description,
            'type'         => $type,
            'period_id'    => $periodId,
            'proposer_id'  => $proposerId,
            'max_students' => $maxStudents,
            'status'       => $status,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Cập nhật thông tin đề tài
     *
     * @param int $id
     * @param array $data
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function update(int $id, array $data): bool
    {
        $topic = self::findById($id);
        if ($topic === null) {
            throw new InvalidArgumentException("Đề tài với ID #{$id} không tồn tại.");
        }

        // Kế thừa giá trị cũ cho các trường không được truyền vào khi sửa
        $merged = [
            'code'         => $data['code'] ?? $topic['code'],
            'name'         => $data['name'] ?? $topic['name'],
            'description'  => array_key_exists('description', $data) ? $data['description'] : $topic['description'],
            'type'         => $data['type'] ?? $topic['type'],
            'period_id'    => $data['period_id'] ?? $topic['period_id'],
            'proposer_id'  => $data['proposer_id'] ?? $topic['proposer_id'],
            'max_students' => $data['max_students'] ?? $topic['max_students'],
            'status'       => $data['status'] ?? $topic['status'],
        ];

        $errors = self::validate($merged, true, $id);
        if (!empty($errors)) {
            $firstError = reset($errors);
            throw new InvalidArgumentException($firstError);
        }

        $code        = strtoupper(trim((string)$merged['code']));
        $name        = trim((string)$merged['name']);
        $description = isset($merged['description']) ? trim((string)$merged['description']) : null;
        if ($description === '') {
            $description = null;
        }
        $type        = trim((string)$merged['type']);
        $periodId    = (int)$merged['period_id'];
        $maxStudents = (int)$merged['max_students'];
        $status      = $merged['status'];

        $pdo = Database::getConnection();
        $sql = 'UPDATE `topics` SET
                    `code`         = :code,
                    `name`         = :name,
                    `description`  = :description,
                    `type`         = :type,
                    `period_id`    = :period_id,
                    `max_students` = :max_students,
                    `status`       = :status,
                    `updated_at`   = NOW()
                WHERE `id` = :id';

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            'id'           => $id,
            'code'         => $code,
            'name'         => $name,
            'description'  => $description,
            'type'         => $type,
            'period_id'    => $periodId,
            'max_students' => $maxStudents,
            'status'       => $status,
        ]);
    }

    /**
     * Xóa đề tài ở trạng thái draft hoặc rejected
     *
     * @param int $id
     * @return bool
     * @throws InvalidArgumentException
     */
    public static function delete(int $id): bool
    {
        $topic = self::findById($id);
        if ($topic === null) {
            throw new InvalidArgumentException("Đề tài với ID #{$id} không tồn tại.");
        }

        if (!in_array($topic['status'], [self::STATUS_DRAFT, self::STATUS_REJECTED], true)) {
            $statusName = self::STATUS_LABELS[$topic['status']] ?? $topic['status'];
            throw new InvalidArgumentException("Không thể xóa đề tài đang ở trạng thái '{$statusName}'. Chỉ có thể xóa đề tài ở trạng thái 'Bản nháp' hoặc 'Từ chối'.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM `topics` WHERE `id` = :id AND `status` IN (:st1, :st2)');
        return $stmt->execute([
            'id'  => $id,
            'st1' => self::STATUS_DRAFT,
            'st2' => self::STATUS_REJECTED,
        ]);
    }

    /**
     * Kiểm tra người dùng có quyền chỉnh sửa đề tài này không
     *
     * Quy tắc:
     * - Admin và Phòng đào tạo: Toàn quyền chỉnh sửa mọi đề tài.
     * - Người đề xuất (Giảng viên/Doanh nghiệp/Bộ môn): Được sửa đề tài của chính mình khi ở trạng thái draft hoặc rejected.
     * - Khi đề tài đã pending (đang chờ duyệt) hoặc approved (đã duyệt): Người đề xuất không được tự ý sửa (chỉ Admin/PĐT).
     */
    public static function canEdit(array $topic, int $userId, array $userRoles): bool
    {
        if (in_array('admin', $userRoles, true) || in_array('training_dept', $userRoles, true)) {
            return true;
        }

        if ((int)$topic['proposer_id'] === $userId) {
            return in_array($topic['status'], [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
        }

        return false;
    }
}
