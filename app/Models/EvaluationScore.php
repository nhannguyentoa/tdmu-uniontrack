<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'evaluation_criterion_id', 'union_group_id',
    'self_score', 'self_note', 'self_scored_by', 'self_scored_at',
    'verified_score', 'verified_note', 'verified_by', 'verified_at',
])]
class EvaluationScore extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'self_score' => 'decimal:2',
            'verified_score' => 'decimal:2',
            'self_scored_at' => 'datetime',
            'verified_at' => 'datetime',
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

    public function selfScoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'self_scored_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
