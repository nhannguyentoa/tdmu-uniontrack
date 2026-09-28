<?php

namespace App\Services\Ai;

final class AiResult
{
    public const STATUS_SUFFICIENT = 'sufficient';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_INSUFFICIENT = 'insufficient';
    public const STATUS_NO_DATA = 'no_data';

    public const STATUSES = [
        self::STATUS_SUFFICIENT => 'Đủ minh chứng',
        self::STATUS_PARTIAL => 'Minh chứng chưa đầy đủ',
        self::STATUS_INSUFFICIENT => 'Thiếu minh chứng',
        self::STATUS_NO_DATA => 'Chưa có hoạt động phù hợp',
    ];

    public const CONFIDENCES = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'];

    /**
     * @param  array<int, int>  $matchedActivityIds
     * @param  array<int, string>  $missingItems
     */
    public function __construct(
        public readonly float $suggestedScore,
        public readonly string $confidence,
        public readonly string $evidenceStatus,
        public readonly string $reasoning,
        public readonly array $matchedActivityIds,
        public readonly array $missingItems,
        public readonly string $provider,
        public readonly string $model,
        public readonly ?int $inputTokens = null,
        public readonly ?int $outputTokens = null,
    ) {
    }

    /**
     * Chuẩn hóa dữ liệu AI trả về: ép điểm vào [0, điểm chuẩn], giá trị liệt kê ngoài danh sách thì về mặc định an toàn,
     * và chỉ giữ các mã hoạt động thực sự có trong ngữ cảnh.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $validActivityIds
     */
    public static function fromArray(array $data, float $maxScore, array $validActivityIds, string $provider, string $model, ?int $in = null, ?int $out = null): self
    {
        $score = is_numeric($data['suggested_score'] ?? null) ? (float) $data['suggested_score'] : 0.0;
        $score = round(max(0.0, min($maxScore, $score)), 2);

        $confidence = (string) ($data['confidence'] ?? 'low');
        if (! array_key_exists($confidence, self::CONFIDENCES)) {
            $confidence = 'low';
        }

        $status = (string) ($data['evidence_status'] ?? self::STATUS_INSUFFICIENT);
        if (! array_key_exists($status, self::STATUSES)) {
            $status = self::STATUS_INSUFFICIENT;
        }

        $matched = collect($data['matched_activity_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => in_array($id, $validActivityIds, true))
            ->unique()->values()->all();

        $missing = collect($data['missing_items'] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => mb_substr(trim($item), 0, 200))
            ->take(8)->values()->all();

        return new self(
            $score,
            $confidence,
            $status,
            mb_substr(trim((string) ($data['reasoning'] ?? '')), 0, 1500),
            $matched,
            $missing,
            $provider,
            $model,
            $in,
            $out,
        );
    }
}
