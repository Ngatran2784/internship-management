<?php

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

/**
 * InternshipPeriod Model - Quản lý bảng `internship_periods`
 *
 * Hỗ trợ quản lý cả Đợt thực tập và Đợt đồ án tốt nghiệp sinh viên.
 * Toàn bộ câu truy vấn sử dụng PDO Prepared Statements nhằm phòng chống SQL Injection.
 */
class InternshipPeriod
{
    public const TYPE_INTERNSHIP         = 'internship';
    public const TYPE_GRADUATION_PROJECT = 'graduation_project';

    public const STATUS_DRAFT  = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public const ALLOWED_TYPES = [
        self::TYPE_INTERNSHIP,
        self::TYPE_GRADUATION_PROJECT,
    ];

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_CLOSED,
    ];

    public const TYPE_LABELS = [
        self::TYPE_INTERNSHIP         => 'Thực tập tốt nghiệp',
        self::TYPE_GRADUATION_PROJECT => 'Đồ án tốt nghiệp',
    ];

    public const STATUS_LABELS = [
        self::STATUS_DRAFT  => 'Bản nháp',
        self::STATUS_ACTIVE => 'Đang diễn ra',
        self::STATUS_CLOSED => 'Đã kết thúc',
    ];

    /**
     * Lấy danh sách đợt thực tập/đồ án có hỗ trợ bộ lọc và tìm kiếm
     *
     * @param array $filters ['type' => string, 'status' => string, 'keyword' => string]
     * @return array Danh sách các đợt sắp xếp theo start_date giảm dần, id giảm dần
     */
    public static function all(array $filters = []): array
    {
        $pdo = Database::getConnection();

        $sql = 'SELECT * FROM `internship_periods` WHERE 1=1';
        $params = [];

        if (!empty($filters['type']) && in_array($filters['type'], self::ALLOWED_TYPES, true)) {
            $sql .= ' AND `type` = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::ALLOWED_STATUSES, true)) {
            $sql .= ' AND `status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= ' AND (`code` LIKE :kw_code OR `name` LIKE :kw_name)';
            $kw = '%' . trim((string)$filters['keyword']) . '%';
            $params['kw_code'] = $kw;
            $params['kw_name'] = $kw;
        }

        $sql .= ' ORDER BY `start_date` DESC, `id` DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm đợt theo khóa chính ID
     */
    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM `internship_periods` WHERE `id` = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Tìm đợt theo mã code định danh duy nhất
     */
    public static function findByCode(string $code): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM `internship_periods` WHERE `code` = :code LIMIT 1');
        $stmt->execute(['code' => trim($code)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Kiểm tra xem mã đợt đã tồn tại trong CSDL hay chưa (trừ ID hiện tại khi cập nhật)
     */
    public static function existsCode(string $code, ?int $excludeId = null): bool
    {
        $pdo = Database::getConnection();
        $sql = 'SELECT COUNT(*) FROM `internship_periods` WHERE `code` = :code';
        $params = ['code' => trim($code)];

        if ($excludeId !== null) {
            $sql .= ' AND `id` != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Kiểm tra tính hợp lệ của dữ liệu đầu vào theo quy chuẩn nghiệp vụ
     *
     * @param array $data Dữ liệu người dùng gửi lên
     * @param int|null $currentId ID bản ghi khi chỉnh sửa (nếu có)
     * @return array Danh sách thông báo lỗi (trống nếu hợp lệ)
     */
    public static function validate(array $data, ?int $currentId = null): array
    {
        $errors = [];

        // 1. Kiểm tra mã đợt (code)
        $code = isset($data['code']) ? trim((string)$data['code']) : '';
        if ($code === '') {
            $errors[] = 'Mã đợt không được để trống.';
        } elseif (mb_strlen($code) < 3 || mb_strlen($code) > 50) {
            $errors[] = 'Mã đợt phải từ 3 đến 50 ký tự.';
        } elseif (!preg_match('/^[A-Za-z0-9_\-\.]+$/', $code)) {
            $errors[] = 'Mã đợt chỉ được chứa chữ cái không dấu, số, dấu gạch nối (-), gạch dưới (_) hoặc dấu chấm.';
        } elseif (self::existsCode($code, $currentId)) {
            $errors[] = "Mã đợt '{$code}' đã tồn tại trong hệ thống. Vui lòng chọn mã khác.";
        }

        // 2. Kiểm tra tên đợt (name)
        $name = isset($data['name']) ? trim((string)$data['name']) : '';
        if ($name === '') {
            $errors[] = 'Tên đợt không được để trống.';
        } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
            $errors[] = 'Tên đợt phải từ 3 đến 150 ký tự.';
        }

        // 3. Kiểm tra loại đợt (type)
        $type = isset($data['type']) ? trim((string)$data['type']) : '';
        if ($type === '' || !in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = 'Loại đợt không hợp lệ. Chỉ chấp nhận Thực tập (internship) hoặc Đồ án tốt nghiệp (graduation_project).';
        }

        // 4. Kiểm tra ngày bắt đầu (start_date) và ngày kết thúc (end_date)
        $startDateStr = isset($data['start_date']) ? trim((string)$data['start_date']) : '';
        $endDateStr   = isset($data['end_date']) ? trim((string)$data['end_date']) : '';

        $startDate = self::parseDate($startDateStr);
        $endDate   = self::parseDate($endDateStr);

        if ($startDate === null) {
            $errors[] = 'Ngày bắt đầu không hợp lệ (định dạng chuẩn YYYY-MM-DD).';
        }

        if ($endDate === null) {
            $errors[] = 'Ngày kết thúc không hợp lệ (định dạng chuẩn YYYY-MM-DD).';
        }

        if ($startDate !== null && $endDate !== null) {
            if ($endDate < $startDate) {
                $errors[] = 'Ngày kết thúc không được trước ngày bắt đầu.';
            }
        }

        // 5. Kiểm tra trạng thái (status) nếu có truyền
        if (isset($data['status'])) {
            $status = trim((string)$data['status']);
            if (!in_array($status, self::ALLOWED_STATUSES, true)) {
                $errors[] = 'Trạng thái đợt không hợp lệ. Chỉ chấp nhận: draft, active, closed.';
            }
        }

        return $errors;
    }

    /**
     * Tạo mới đợt thực tập/đồ án
     *
     * @param array $data ['code', 'name', 'type', 'start_date', 'end_date', 'status' (tùy chọn), 'description' (tùy chọn)]
     * @return int ID đợt vừa tạo
     * @throws InvalidArgumentException khi dữ liệu không hợp lệ
     */
    public static function create(array $data): int
    {
        $errors = self::validate($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO `internship_periods`
                (`code`, `name`, `type`, `start_date`, `end_date`, `status`, `description`)
             VALUES
                (:code, :name, :type, :start_date, :end_date, :status, :description)'
        );

        $description = isset($data['description']) && trim((string)$data['description']) !== ''
            ? trim((string)$data['description'])
            : null;

        $status = isset($data['status']) && in_array($data['status'], self::ALLOWED_STATUSES, true)
            ? $data['status']
            : self::STATUS_DRAFT;

        $stmt->execute([
            'code'        => strtoupper(trim((string)$data['code'])),
            'name'        => trim((string)$data['name']),
            'type'        => trim((string)$data['type']),
            'start_date'  => trim((string)$data['start_date']),
            'end_date'    => trim((string)$data['end_date']),
            'status'      => $status,
            'description' => $description,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Cập nhật thông tin đợt
     *
     * @param int $id ID đợt cần cập nhật
     * @param array $data Thông tin cần cập nhật
     * @return bool
     * @throws InvalidArgumentException khi không tìm thấy đợt hoặc dữ liệu không hợp lệ
     */
    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Đợt thực tập/đồ án không tồn tại.');
        }

        $errors = self::validate($data, $id);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'UPDATE `internship_periods`
                SET `code` = :code,
                    `name` = :name,
                    `type` = :type,
                    `start_date` = :start_date,
                    `end_date` = :end_date,
                    `status` = :status,
                    `description` = :description
              WHERE `id` = :id'
        );

        $description = isset($data['description']) && trim((string)$data['description']) !== ''
            ? trim((string)$data['description'])
            : null;

        $status = isset($data['status']) && in_array($data['status'], self::ALLOWED_STATUSES, true)
            ? $data['status']
            : $existing['status'];

        return $stmt->execute([
            'id'          => $id,
            'code'        => strtoupper(trim((string)$data['code'])),
            'name'        => trim((string)$data['name']),
            'type'        => trim((string)$data['type']),
            'start_date'  => trim((string)$data['start_date']),
            'end_date'    => trim((string)$data['end_date']),
            'status'      => $status,
            'description' => $description,
        ]);
    }

    /**
     * Cập nhật trạng thái đợt theo quy tắc chuyển đổi trạng thái
     *
     * Quy tắc trạng thái:
     * - draft -> active: Mở đợt cho sinh viên/giảng viên bắt đầu tham gia
     * - active -> closed: Đóng đợt khi kết thúc kỳ, khóa toàn bộ thao tác nộp/đăng ký
     * - closed -> active: Mở lại đợt trong trường hợp cần xử lý phúc khảo hoặc bổ sung
     * - active -> draft: Trả về bản nháp để điều chỉnh thông tin nếu cần
     *
     * @param int $id ID đợt
     * @param string $newStatus Trạng thái mới (draft, active, closed)
     * @return bool
     * @throws InvalidArgumentException khi trạng thái không hợp lệ hoặc không tìm thấy đợt
     */
    public static function updateStatus(int $id, string $newStatus): bool
    {
        $period = self::findById($id);
        if ($period === null) {
            throw new InvalidArgumentException('Đợt thực tập/đồ án không tồn tại.');
        }

        if (!in_array($newStatus, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException("Trạng thái mới '{$newStatus}' không hợp lệ.");
        }

        if ($period['status'] === $newStatus) {
            return true; // Trạng thái không thay đổi, coi như thành công
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE `internship_periods` SET `status` = :status WHERE `id` = :id');

        return $stmt->execute([
            'id'     => $id,
            'status' => $newStatus,
        ]);
    }

    /**
     * Chuyển đổi chuỗi ngày thành đối tượng DateTimeImmutable nếu hợp lệ
     */
    private static function parseDate(string $dateStr): ?DateTimeImmutable
    {
        if ($dateStr === '') {
            return null;
        }

        $d = DateTimeImmutable::createFromFormat('Y-m-d', $dateStr);
        if ($d === false) {
            return null;
        }

        // Đảm bảo không bị parse tràn ngày (ví dụ 2026-02-31 thành 2026-03-03)
        return $d->format('Y-m-d') === $dateStr ? $d : null;
    }
}
