<?php

namespace App\Services;

use App\Core\Database;
use App\Models\TopicAssignment;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * TopicMatchingService - Dịch vụ thực thi thuật toán đối sánh nguyện vọng đề tài
 *
 * Nhiệm vụ nghiệp vụ:
 * - Đối sánh nguyện vọng của sinh viên (NV1, NV2, NV3) với các đề tài đã được duyệt.
 * - Tuân thủ nghiêm ngặt các ràng buộc:
 *   1. Thứ tự ưu tiên nguyện vọng: Xét NV1 -> NV2 -> NV3.
 *   2. Tính hợp lệ đề tài: Đề tài phải thuộc đợt và có trạng thái 'approved'.
 *   3. Không trùng sinh viên: Mỗi sinh viên chỉ được phân công duy nhất 1 đề tài trong cùng một đợt.
 *   4. Không vượt chỉ tiêu đề tài: Số sinh viên phân công <= max_students của đề tài.
 *   5. Không vượt chỉ tiêu giảng viên: Số sinh viên hướng dẫn <= max_students_per_lecturer của giảng viên.
 *   6. Quy tắc cạnh tranh xác định (Deterministic Tie-Breaking):
 *      - Tiêu chí 1: Thứ tự nguyện vọng (NV1 > NV2 > NV3).
 *      - Tiêu chí 2: Điểm GPA / kết quả học tập (nếu có cung cấp).
 *      - Tiêu chí 3: Thời điểm đăng ký nguyện vọng (nộp sớm hơn được ưu tiên).
 *      - Tiêu chí 4: Mã sinh viên (username) tăng dần để bảo đảm tính tất định 100%.
 *   7. Danh sách chưa phân công: Sinh viên không trúng tuyển hoặc hết chỉ tiêu được đưa vào danh sách chưa phân công.
 *   8. Kết quả tự động chỉ là DỰ THẢO (draft), không tự động công bố (published).
 */
class TopicMatchingService
{
    /**
     * Chỉ tiêu sinh viên tối đa mặc định cho mỗi giảng viên trong một đợt
     */
    public const DEFAULT_MAX_STUDENTS_PER_LECTURER = 5;

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
    }

    /**
     * Thực thi thuật toán đối sánh nguyện vọng cho một đợt thực tập / đồ án
     *
     * @param int   $periodId Đợt thực tập / đồ án cần phân công
     * @param array $options  Các tham số cấu hình:
     *                        - 'max_per_lecturer' (int): Giới hạn chỉ tiêu giảng viên (mặc định 5)
     *                        - 'dry_run' (bool): Nếu true, chỉ mô phỏng, KHÔNG ghi CSDL (mặc định false)
     *                        - 'student_scores' (array): Bảng điểm GPA sinh viên [student_id => float]
     *                        - 'assigned_by' (int|null): ID người kích hoạt chạy thuật toán
     * @return array Kết quả đối sánh chi tiết
     * @throws InvalidArgumentException|RuntimeException
     */
    public function matchPeriod(int $periodId, array $options = []): array
    {
        $maxPerLecturer = isset($options['max_per_lecturer']) ? max(1, (int)$options['max_per_lecturer']) : self::DEFAULT_MAX_STUDENTS_PER_LECTURER;
        $dryRun         = !empty($options['dry_run']);
        $studentScores  = $options['student_scores'] ?? [];
        $assignedBy     = $options['assigned_by'] ?? null;

        // 1. KIỂM TRA ĐIỀU KIỆN TIÊN QUYẾT (PRECONDITIONS)
        $stmt = $this->pdo->prepare("SELECT id, code, name, status FROM internship_periods WHERE id = :id");
        $stmt->execute([':id' => $periodId]);
        $period = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$period) {
            throw new InvalidArgumentException("Đợt thực tập / đồ án với ID {$periodId} không tồn tại.");
        }

        // Không cho phép tự động ghi đè nếu đợt đã có kết quả phân công được công bố
        if (!$dryRun && TopicAssignment::hasPublishedAssignments($periodId)) {
            throw new RuntimeException("Đợt này đã có kết quả phân công chính thức được công bố. Không thể chạy lại thuật toán tự động khi chưa rút lại công bố.");
        }

        // 2. NẠP DANH SÁCH ĐỀ TÀI ĐÃ DUYỆT TRONG ĐỢT
        $stmt = $this->pdo->prepare("
            SELECT 
                t.id,
                t.code,
                t.name,
                t.max_students,
                t.proposer_id AS lecturer_id,
                u.full_name AS lecturer_name,
                u.email AS lecturer_email
            FROM topics t
            JOIN users u ON t.proposer_id = u.id
            WHERE t.period_id = :period_id 
              AND t.status = 'approved'
            ORDER BY t.id ASC
        ");
        $stmt->execute([':period_id' => $periodId]);
        $approvedTopics = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Khởi tạo trạng thái chỉ tiêu đề tài và giảng viên
        $topicSlots = [];
        $lecturerSlots = [];

        foreach ($approvedTopics as $t) {
            $tId = (int)$t['id'];
            $lId = (int)$t['lecturer_id'];

            $topicSlots[$tId] = [
                'topic_id'     => $tId,
                'code'         => $t['code'],
                'name'         => $t['name'],
                'max_students' => (int)$t['max_students'],
                'lecturer_id'  => $lId,
                'assigned'     => [], // Danh sách sinh viên trúng tuyển
            ];

            if (!isset($lecturerSlots[$lId])) {
                $lecturerSlots[$lId] = [
                    'lecturer_id'   => $lId,
                    'lecturer_name' => $t['lecturer_name'],
                    'capacity'      => $maxPerLecturer,
                    'assigned'      => [],
                ];
            }
        }

        // Đếm trước các phân công thủ công hiện có (để không vượt chỉ tiêu khi gán tự động)
        $existingManual = $this->pdo->prepare("
            SELECT student_id, topic_id, lecturer_id 
            FROM topic_assignments 
            WHERE period_id = :period_id 
              AND assignment_method = 'manual' 
              AND status != 'cancelled'
        ");
        $existingManual->execute([':period_id' => $periodId]);
        $manualRows = $existingManual->fetchAll(PDO::FETCH_ASSOC);

        $alreadyAssignedStudentIds = [];
        foreach ($manualRows as $m) {
            $sId = (int)$m['student_id'];
            $tId = (int)$m['topic_id'];
            $lId = (int)$m['lecturer_id'];

            $alreadyAssignedStudentIds[$sId] = true;

            if (isset($topicSlots[$tId])) {
                $topicSlots[$tId]['assigned'][] = $sId;
            }
            if (isset($lecturerSlots[$lId])) {
                $lecturerSlots[$lId]['assigned'][] = $sId;
            }
        }

        // 3. NẠP DANH SÁCH NGUYỆN VỌNG CỦA SINH VIÊN TRONG ĐỢT
        $stmt = $this->pdo->prepare("
            SELECT 
                w.id AS wish_id,
                w.student_id,
                w.topic_id,
                w.priority,
                w.note,
                w.created_at AS wish_created_at,
                u.username AS student_code,
                u.full_name AS student_name,
                u.email AS student_email,
                t.status AS topic_status
            FROM topic_wishes w
            JOIN users u ON w.student_id = u.id
            LEFT JOIN topics t ON w.topic_id = t.id
            WHERE w.period_id = :period_id
            ORDER BY w.priority ASC, w.created_at ASC, u.username ASC
        ");
        $stmt->execute([':period_id' => $periodId]);
        $allWishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gom nhóm sinh viên và nguyện vọng
        $studentWishes = [];
        $studentMeta   = [];

        foreach ($allWishes as $w) {
            $sId = (int)$w['student_id'];
            $prio = (int)$w['priority'];

            $studentMeta[$sId] = [
                'student_id'   => $sId,
                'student_code' => $w['student_code'],
                'student_name' => $w['student_name'],
                'student_email'=> $w['student_email'],
                'score'        => (float)($studentScores[$sId] ?? 0.0),
            ];

            $studentWishes[$sId][$prio] = [
                'wish_id'    => (int)$w['wish_id'],
                'topic_id'   => (int)$w['topic_id'],
                'priority'   => $prio,
                'note'       => $w['note'],
                'created_at' => $w['wish_created_at'],
            ];
        }

        // 4. THUẬT TOÁN ĐỐI SÁNH ĐA VÒNG THEO THỨ TỰ ƯU TIÊN (ROUNDS 1 -> 2 -> 3)
        $assignedResults   = []; // [student_id => assignment_record]
        $rejectionReasons  = []; // [student_id => [wish_priority => reason]]

        for ($priority = 1; $priority <= 3; $priority++) {
            // Gom danh sách các thí sinh nộp vào từng đề tài ở vòng xét hiện tại
            $candidatesByTopic = [];

            foreach ($studentWishes as $sId => $wishes) {
                // Bỏ qua nếu sinh viên đã được phân công ở vòng trước hoặc đã phân công thủ công
                if (isset($assignedResults[$sId]) || isset($alreadyAssignedStudentIds[$sId])) {
                    continue;
                }

                // Nếu sinh viên có nguyện vọng ở mức ưu tiên này
                if (isset($wishes[$priority])) {
                    $wish = $wishes[$priority];
                    $tId  = (int)$wish['topic_id'];

                    if (!isset($topicSlots[$tId])) {
                        $rejectionReasons[$sId][$priority] = "Đề tài không tồn tại hoặc chưa được duyệt";
                        continue;
                    }

                    $candidatesByTopic[$tId][] = [
                        'student_id'   => $sId,
                        'wish_id'      => $wish['wish_id'],
                        'priority'     => $priority,
                        'topic_id'     => $tId,
                        'lecturer_id'  => $topicSlots[$tId]['lecturer_id'],
                        'created_at'   => $wish['created_at'],
                        'score'        => $studentMeta[$sId]['score'],
                        'student_code' => $studentMeta[$sId]['student_code'],
                        'student_name' => $studentMeta[$sId]['student_name'],
                        'note'         => $wish['note'],
                    ];
                }
            }

            // Với mỗi đề tài, xét duyệt các thí sinh đăng ký theo quy tắc giải quyết cạnh tranh
            foreach ($candidatesByTopic as $tId => $candidates) {
                // Sắp xếp các thí sinh cạnh tranh theo QUY TẮC ƯU TIÊN XÁC ĐỊNH (TIE-BREAKING):
                // 1. Điểm số (GPA) giảm dần
                // 2. Thời điểm nộp nguyện vọng tăng dần (nộp trước xếp trước)
                // 3. Mã sinh viên tăng dần (bảo đảm tất định)
                usort($candidates, function (array $a, array $b): int {
                    // Tiêu chí 1: Điểm GPA (nếu có điểm khác nhau)
                    if ($a['score'] !== $b['score']) {
                        return $a['score'] > $b['score'] ? -1 : 1;
                    }
                    // Tiêu chí 2: Thời điểm nộp
                    if ($a['created_at'] !== $b['created_at']) {
                        return strcmp($a['created_at'], $b['created_at']);
                    }
                    // Tiêu chí 3: Mã sinh viên (alphabetical)
                    return strcmp($a['student_code'], $b['student_code']);
                });

                // Xét lần lượt từng thí sinh đã sắp xếp
                foreach ($candidates as $cand) {
                    $sId = $cand['student_id'];
                    $lId = $cand['lecturer_id'];

                    // Kiểm tra chỉ tiêu đề tài
                    $topicRemaining = $topicSlots[$tId]['max_students'] - count($topicSlots[$tId]['assigned']);
                    if ($topicRemaining <= 0) {
                        $rejectionReasons[$sId][$priority] = "Đề tài đã hết chỉ tiêu ({$topicSlots[$tId]['max_students']})";
                        continue;
                    }

                    // Kiểm tra chỉ tiêu giảng viên
                    $lecturerRemaining = $lecturerSlots[$lId]['capacity'] - count($lecturerSlots[$lId]['assigned']);
                    if ($lecturerRemaining <= 0) {
                        $rejectionReasons[$sId][$priority] = "Giảng viên hướng dẫn đã đạt giới hạn chỉ tiêu ({$lecturerSlots[$lId]['capacity']})";
                        continue;
                    }

                    // ĐỦ ĐIỀU KIỆN TRÚNG TUYỂN: Gán sinh viên vào đề tài
                    $topicSlots[$tId]['assigned'][] = $sId;
                    $lecturerSlots[$lId]['assigned'][] = $sId;

                    $assignedResults[$sId] = [
                        'period_id'             => $periodId,
                        'student_id'            => $sId,
                        'student_code'          => $cand['student_code'],
                        'student_name'          => $cand['student_name'],
                        'topic_id'              => $tId,
                        'topic_code'            => $topicSlots[$tId]['code'],
                        'topic_name'            => $topicSlots[$tId]['name'],
                        'lecturer_id'           => $lId,
                        'lecturer_name'         => $lecturerSlots[$lId]['lecturer_name'],
                        'matched_wish_priority' => $priority,
                        'assignment_method'     => TopicAssignment::METHOD_AUTO,
                        'status'                => TopicAssignment::STATUS_DRAFT,
                        'notes'                 => "Trúng tuyển theo nguyện vọng {$priority}",
                    ];
                }
            }
        }

        // 5. TẬP HỢP DANH SÁCH CHƯA PHÂN CÔNG (UNASSIGNED STUDENTS)
        $unassignedList = [];
        foreach ($studentMeta as $sId => $meta) {
            if (!isset($assignedResults[$sId]) && !isset($alreadyAssignedStudentIds[$sId])) {
                $reasons = $rejectionReasons[$sId] ?? [];
                $reasonText = [];
                foreach ([1, 2, 3] as $p) {
                    if (isset($reasons[$p])) {
                        $reasonText[] = "NV{$p}: " . $reasons[$p];
                    } elseif (isset($studentWishes[$sId][$p])) {
                        $reasonText[] = "NV{$p}: Không trúng tuyển";
                    }
                }

                $unassignedList[] = [
                    'student_id'   => $sId,
                    'student_code' => $meta['student_code'],
                    'student_name' => $meta['student_name'],
                    'student_email'=> $meta['student_email'],
                    'score'        => $meta['score'],
                    'wishes_count' => count($studentWishes[$sId] ?? []),
                    'reason'       => !empty($reasonText) ? implode('; ', $reasonText) : 'Không có nguyện vọng hợp lệ',
                ];
            }
        }

        // 6. LƯU CSDL NẾU KHÔNG PHẢI DRY-RUN
        $savedCount = 0;
        if (!$dryRun) {
            $manageTransaction = !$this->pdo->inTransaction();
            if ($manageTransaction) {
                $this->pdo->beginTransaction();
            }
            try {
                // Khóa đợt và các đề tài của đợt để serialize quá trình ghi phân công tự động (Pessimistic Row-Locking)
                $stmtLockPeriod = $this->pdo->prepare("SELECT id FROM internship_periods WHERE id = :id FOR UPDATE");
                $stmtLockPeriod->execute([':id' => $periodId]);

                $stmtLockTopics = $this->pdo->prepare("SELECT id FROM topics WHERE period_id = :id AND status = 'approved' FOR UPDATE");
                $stmtLockTopics->execute([':id' => $periodId]);

                // Xóa các phân công tự động dự thảo cũ của đợt này
                TopicAssignment::deleteDraftAutoAssignments($periodId);

                // Chèn các bản ghi phân công tự động mới
                $insertSql = "
                    INSERT INTO topic_assignments 
                        (period_id, student_id, topic_id, lecturer_id, matched_wish_priority, assignment_method, status, assigned_by, notes, assigned_at)
                    VALUES 
                        (:period_id, :student_id, :topic_id, :lecturer_id, :matched_priority, :method, :status, :assigned_by, :notes, NOW())
                ";
                $stmt = $this->pdo->prepare($insertSql);

                foreach ($assignedResults as $item) {
                    $stmt->execute([
                        ':period_id'        => $periodId,
                        ':student_id'       => $item['student_id'],
                        ':topic_id'         => $item['topic_id'],
                        ':lecturer_id'      => $item['lecturer_id'],
                        ':matched_priority' => $item['matched_wish_priority'],
                        ':method'           => TopicAssignment::METHOD_AUTO,
                        ':status'           => TopicAssignment::STATUS_DRAFT,
                        ':assigned_by'      => $assignedBy,
                        ':notes'            => $item['notes'],
                    ]);
                    $savedCount++;
                }

                if ($manageTransaction) {
                    $this->pdo->commit();
                }
            } catch (Throwable $e) {
                if ($manageTransaction && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw new RuntimeException("Lỗi lưu kết quả phân công: " . $e->getMessage(), 0, $e);
            }
        }

        // 7. THỐNG KÊ VÀ TRẢ VỀ KẾT QUẢ
        $prioCounts = [1 => 0, 2 => 0, 3 => 0];
        foreach ($assignedResults as $item) {
            $p = $item['matched_wish_priority'];
            if (isset($prioCounts[$p])) {
                $prioCounts[$p]++;
            }
        }

        return [
            'success'                   => true,
            'period'                    => [
                'id'     => $period['id'],
                'code'   => $period['code'],
                'name'   => $period['name'],
                'status' => $period['status'],
            ],
            'is_dry_run'                => $dryRun,
            'max_students_per_lecturer' => $maxPerLecturer,
            'total_students_registered' => count($studentMeta),
            'total_assigned'            => count($assignedResults),
            'total_unassigned'          => count($unassignedList),
            'saved_records_count'       => $savedCount,
            'matched_by_priority'       => $prioCounts,
            'topic_capacity_usage'      => array_map(function (array $slot): array {
                return [
                    'topic_id'     => $slot['topic_id'],
                    'code'         => $slot['code'],
                    'name'         => $slot['name'],
                    'capacity'     => $slot['max_students'],
                    'assigned'     => count($slot['assigned']),
                    'remaining'    => max(0, $slot['max_students'] - count($slot['assigned'])),
                ];
            }, $topicSlots),
            'lecturer_capacity_usage'   => array_map(function (array $slot): array {
                return [
                    'lecturer_id'   => $slot['lecturer_id'],
                    'lecturer_name' => $slot['lecturer_name'],
                    'capacity'      => $slot['capacity'],
                    'assigned'      => count($slot['assigned']),
                    'remaining'     => max(0, $slot['capacity'] - count($slot['assigned'])),
                ];
            }, $lecturerSlots),
            'assigned'                  => array_values($assignedResults),
            'unassigned'                => $unassignedList,
            'executed_at'               => date('Y-m-d H:i:s'),
        ];
    }
}
