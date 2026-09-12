<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'academic_year', 'classification', 'score', 'note', 'scored_by', 'scored_at'])]
class MemberEvaluation extends Model
{
    use HasFactory;

    public const CLASSIFICATION_EXCELLENT = 'xuat_sac';
    public const CLASSIFICATION_ACTIVE = 'tich_cuc';
    public const CLASSIFICATION_COMPLETED = 'hoan_thanh';
    public const CLASSIFICATION_NOT_COMPLETED = 'chua_hoan_thanh';

    public const CLASSIFICATIONS = [
        self::CLASSIFICATION_EXCELLENT => 'Đoàn viên xuất sắc',
        self::CLASSIFICATION_ACTIVE => 'Đoàn viên tích cực',
        self::CLASSIFICATION_COMPLETED => 'Hoàn thành nhiệm vụ',
        self::CLASSIFICATION_NOT_COMPLETED => 'Chưa hoàn thành nhiệm vụ',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'scored_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function scoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    public function classificationLabel(): ?string
    {
        return self::CLASSIFICATIONS[$this->classification] ?? null;
    }
}
