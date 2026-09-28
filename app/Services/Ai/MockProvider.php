<?php

namespace App\Services\Ai;

/**
 * Nhà cung cấp giả lập, xác định (không gọi mạng, không tốn phí): dùng để phát triển, kiểm thử và làm mốc so sánh
 * (baseline) cho các model thật trong thí nghiệm. Chỉ dùng so khớp từ khóa và số liệu có sẵn.
 */
final class MockProvider implements AiProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function model(): string
    {
        return 'heuristic-v1';
    }

    public function evaluate(EvaluationContext $context): AiResult
    {
        $matched = collect($context->activities)
            ->reject(fn (array $a) => $a['status'] === 'cancelled')
            ->filter(fn (array $a) => TextMatcher::overlap($context->criterionContent, $a['name'].' '.($a['goal'] ?? '').' '.($a['type'] ?? '')) >= 1)
            ->values();

        if ($matched->isEmpty()) {
            return new AiResult(0.0, 'medium', AiResult::STATUS_NO_DATA,
                'Không tìm thấy hoạt động nào của tổ trong năm học có nội dung phù hợp với tiêu chí này.', [],
                ['Hoạt động phù hợp với nội dung tiêu chí'], $this->name(), $this->model());
        }

        $done = $matched->filter(fn (array $a) => $a['status'] === 'completed');
        $withEvidence = $done->filter(fn (array $a) => ! empty($a['evidences']));

        if ($withEvidence->isEmpty()) {
            $missing = $done->isEmpty() ? ['Hoạt động chưa hoàn thành'] : ['Ảnh/PDF minh chứng cho hoạt động đã hoàn thành'];

            return new AiResult(round($context->maxScore * 0.25, 2), 'medium', AiResult::STATUS_INSUFFICIENT,
                'Có hoạt động liên quan nhưng chưa có minh chứng hợp lệ (hoạt động hoàn thành kèm tệp minh chứng).',
                $matched->pluck('id')->all(), $missing, $this->name(), $this->model());
        }

        $ratios = $withEvidence->map(function (array $a) {
            $expected = (int) ($a['expected_quantity'] ?? 0);
            $actual = max((int) ($a['actual_quantity'] ?? 0), (int) ($a['participants_attended'] ?? 0));

            return $expected > 0 ? min(1.0, $actual / $expected) : 1.0;
        });
        $ratio = $ratios->avg();
        $full = $ratio >= 0.8;

        return new AiResult(
            round($context->maxScore * ($full ? 1.0 : max(0.4, $ratio)), 2),
            $full ? 'high' : 'medium',
            $full ? AiResult::STATUS_SUFFICIENT : AiResult::STATUS_PARTIAL,
            sprintf('Có %d hoạt động hoàn thành kèm minh chứng; mức tham gia so với dự kiến đạt khoảng %d%%.', $withEvidence->count(), round($ratio * 100)),
            $withEvidence->pluck('id')->all(),
            $full ? [] : ['Số lượng tham gia thực tế thấp hơn dự kiến'],
            $this->name(),
            $this->model(),
        );
    }
}
