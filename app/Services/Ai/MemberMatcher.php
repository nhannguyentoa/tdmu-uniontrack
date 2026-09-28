<?php

namespace App\Services\Ai;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Đối khớp tên đọc được từ ảnh với đoàn viên trong hệ thống. Chạy hoàn toàn cục bộ: danh sách đoàn viên
 * KHÔNG được gửi cho dịch vụ AI, chỉ ảnh danh sách mới rời khỏi máy chủ.
 */
final class MemberMatcher
{
    public const EXACT = 'exact';          // trùng mã hoặc trùng tên (không phân biệt dấu/hoa thường)
    public const FUZZY = 'fuzzy';          // tên gần giống, tự chọn nhưng cần kiểm tra
    public const AMBIGUOUS = 'ambiguous';  // nhiều đoàn viên trùng hoặc gần giống tên, người dùng phải chọn
    public const UNMATCHED = 'unmatched';  // không tìm thấy
    public const DUPLICATE = 'duplicate';  // đã có trong hoạt động hoặc trùng dòng khác

    /**
     * @param  Collection<int, Member>  $roster  đoàn viên đang hoạt động
     * @param  list<int>  $preferredGroupIds  các tổ chủ trì/phối hợp của hoạt động (ưu tiên khi trùng tên)
     * @param  list<int>  $alreadyInActivity  id đoàn viên đã có trong danh sách tham gia
     */
    public function __construct(
        private readonly Collection $roster,
        private readonly array $preferredGroupIds = [],
        private readonly array $alreadyInActivity = [],
        private readonly float $threshold = 0.85,
    ) {
    }

    /**
     * @param  list<array{name: string, code: ?string, confidence: ?float, note: ?string}>  $rows
     * @return list<array<string, mixed>>
     */
    public function match(array $rows): array
    {
        $normalized = $this->roster->mapWithKeys(fn (Member $m) => [$m->id => self::normalize($m->full_name)]);
        $byCode = $this->roster->filter(fn (Member $m) => $m->code)->mapWithKeys(fn (Member $m) => [self::normalizeCode($m->code) => $m->id]);

        $taken = [];
        $result = [];

        foreach ($rows as $row) {
            $resolved = $this->resolve($row, $normalized, $byCode);

            if ($resolved['member_id'] !== null) {
                if (in_array($resolved['member_id'], $this->alreadyInActivity, true)) {
                    $resolved['status'] = self::DUPLICATE;
                    $resolved['reason'] = 'Đã có trong danh sách tham gia của hoạt động.';
                } elseif (isset($taken[$resolved['member_id']])) {
                    $resolved['status'] = self::DUPLICATE;
                    $resolved['reason'] = 'Trùng với một dòng khác trong danh sách.';
                } else {
                    $taken[$resolved['member_id']] = true;
                }
            }

            $result[] = $row + $resolved;
        }

        return $result;
    }

    /**
     * @param  array{name: string, code: ?string}  $row
     * @param  Collection<int, string>  $normalized
     * @param  Collection<string, int>  $byCode
     * @return array{status: string, member_id: ?int, candidates: list<int>, similarity: ?float, reason: ?string}
     */
    private function resolve(array $row, Collection $normalized, Collection $byCode): array
    {
        if (! empty($row['code']) && $byCode->has(self::normalizeCode($row['code']))) {
            return $this->result(self::EXACT, (int) $byCode->get(self::normalizeCode($row['code'])), [], 1.0, 'Khớp theo mã đoàn viên.');
        }

        $name = self::normalize($row['name']);
        if ($name === '') {
            return $this->result(self::UNMATCHED, null, [], null, 'Không đọc được tên.');
        }

        $scores = $normalized->map(fn (string $candidate) => self::similarity($name, $candidate))->sortDesc();
        $best = $scores->first();

        if ($best === null || $best < 0.6) {
            return $this->result(self::UNMATCHED, null, [], $best, 'Không có đoàn viên nào có tên gần giống.');
        }

        // Ứng viên đồng hạng nhất (thường là trùng tên hoàn toàn): ưu tiên người thuộc tổ của hoạt động.
        $top = $scores->filter(fn (float $s) => abs($s - $best) < 0.0001)->keys()->map(fn ($id) => (int) $id)->all();
        $preferred = array_values(array_filter($top, fn (int $id) => in_array($this->roster->firstWhere('id', $id)?->union_group_id, $this->preferredGroupIds, true)));
        $pool = count($preferred) > 0 ? $preferred : $top;

        if ($best >= 0.9999) {
            return count($pool) === 1
                ? $this->result(self::EXACT, $pool[0], [], $best, null)
                : $this->result(self::AMBIGUOUS, null, array_slice($pool, 0, 5), $best, 'Có nhiều đoàn viên trùng tên, hãy chọn đúng người.');
        }

        if ($best >= $this->threshold) {
            $second = $scores->reject(fn (float $s, $id) => in_array((int) $id, $top, true))->first() ?? 0.0;

            return count($pool) === 1 && $best - $second >= 0.04
                ? $this->result(self::FUZZY, $pool[0], [], $best, 'Tên gần giống, cần kiểm tra lại.')
                : $this->result(self::AMBIGUOUS, null, array_slice($pool, 0, 5), $best, 'Có nhiều đoàn viên tên gần giống, hãy chọn đúng người.');
        }

        $candidates = $scores->filter(fn (float $s) => $s >= 0.6)->keys()->map(fn ($id) => (int) $id)->take(3)->all();

        return $this->result(self::UNMATCHED, null, $candidates, $best, 'Không tìm thấy tên khớp, hãy chọn thủ công nếu có.');
    }

    /**
     * @param  list<int>  $candidates
     * @return array{status: string, member_id: ?int, candidates: list<int>, similarity: ?float, reason: ?string}
     */
    private function result(string $status, ?int $memberId, array $candidates, ?float $similarity, ?string $reason): array
    {
        return ['status' => $status, 'member_id' => $memberId, 'candidates' => $candidates, 'similarity' => $similarity !== null ? round($similarity, 3) : null, 'reason' => $reason];
    }

    /** Chữ thường, bỏ dấu, bỏ học hàm/học vị đứng đầu và ký tự thừa. */
    public static function normalize(string $name): string
    {
        $ascii = mb_strtolower(Str::ascii($name));
        $ascii = preg_replace('/[^a-z0-9\s]/', ' ', $ascii) ?? $ascii;
        $ascii = preg_replace('/^\s*((pgs|gs|ts|ths|th s|cn|ks|dr|mr|mrs|ms|thay|co)\s+)+/', '', $ascii) ?? $ascii;

        return trim(preg_replace('/\s+/', ' ', $ascii) ?? $ascii);
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)) ?? '');
    }

    /** Độ giống 0..1 dựa trên khoảng cách chỉnh sửa Levenshtein. */
    public static function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        $max = max(strlen($a), strlen($b));

        return $max === 0 ? 0.0 : 1 - levenshtein(substr($a, 0, 250), substr($b, 0, 250)) / $max;
    }
}
