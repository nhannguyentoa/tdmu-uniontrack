<?php

namespace App\Services\Ai;

/**
 * Dữ liệu gửi cho AI khi đánh giá một tiêu chí của một tổ. Không chứa tên/mã đoàn viên:
 * danh sách tham gia chỉ được tóm tắt bằng số lượng.
 *
 * Mỗi hoạt động là mảng gồm: id, code, name, type, role (chu_tri|phoi_hop|tham_gia), status, progress,
 * start, end, location, goal, expected_quantity, actual_quantity, participants_total,
 * participants_attended, evidences (danh sách [name, type, description]).
 *
 * Mỗi tệp đính kèm là mảng gồm: disk, path, mime, name.
 */
final class EvaluationContext
{
    /**
     * @param  array<int, array<string, mixed>>  $activities
     * @param  array<int, array<string, mixed>>  $attachments
     */
    public function __construct(
        public readonly string $criterionContent,
        public readonly float $maxScore,
        public readonly ?string $department,
        public readonly string $academicYear,
        public readonly string $groupName,
        public readonly ?float $selfScore,
        public readonly ?string $selfNote,
        public readonly array $activities,
        public readonly array $attachments = [],
    ) {
    }
}
