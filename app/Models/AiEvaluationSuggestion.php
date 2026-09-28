<?php

namespace App\Models;

use App\Services\Ai\AiResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable([
    'evaluation_criterion_id', 'union_group_id', 'provider', 'model', 'suggested_score', 'confidence',
    'evidence_status', 'reasoning', 'matched_activity_ids', 'missing_items', 'input_tokens', 'output_tokens', 'requested_by',
])]
class AiEvaluationSuggestion extends Model
{
    protected function casts(): array
    {
        return [
            'suggested_score' => 'decimal:2',
            'matched_activity_ids' => 'array',
            'missing_items' => 'array',
        ];
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(EvaluationCriterion::class, 'evaluation_criterion_id');
    }

    public function unionGroup(): BelongsTo
    {
        return $this->belongsTo(UnionGroup::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function matchedActivities(): Collection
    {
        return Activity::whereIn('id', $this->matched_activity_ids ?? [])->get(['id', 'code', 'name']);
    }

    public function statusLabel(): string
    {
        return AiResult::STATUSES[$this->evidence_status] ?? $this->evidence_status;
    }

    /**
     * Dạng JSON trả về giao diện; cán bộ (includeScore = false) không thấy điểm gợi ý để tránh dùng AI "chấm hộ" điểm tự chấm.
     *
     * @return array<string, mixed>
     */
    public function toPayload(bool $includeScore = true): array
    {
        $payload = [
            'status' => $this->evidence_status,
            'status_label' => $this->statusLabel(),
            'confidence' => $this->confidence,
            'confidence_label' => AiResult::CONFIDENCES[$this->confidence] ?? $this->confidence,
            'reasoning' => $this->reasoning,
            'missing' => $this->missing_items ?? [],
            'matched' => $this->matchedActivities()->map(fn (Activity $a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])->all(),
            'provider' => $this->provider,
            'model' => $this->model,
            'generated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];

        if ($includeScore) {
            $payload['score'] = (float) $this->suggested_score;
        }

        return $payload;
    }
}
