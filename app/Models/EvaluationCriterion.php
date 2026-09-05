<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year', 'group_label', 'order_no', 'content', 'max_score', 'department_id'])]
class EvaluationCriterion extends Model
{
    use HasFactory;

    public const GROUP_I = 'I';
    public const GROUP_II = 'II';
    public const GROUP_III = 'III';
    public const GROUP_BONUS = 'thuong';

    public const GROUP_LABELS = [
        self::GROUP_I => 'I. Đại diện chăm lo, bảo vệ quyền, lợi ích hợp pháp, chính đáng của đoàn viên',
        self::GROUP_II => 'II. Xây dựng tổ chức Công đoàn, hoạt động Nữ công, phong trào văn thể',
        self::GROUP_III => 'III. Công tác tuyên truyền và các hoạt động khác',
        self::GROUP_BONUS => 'Điểm thưởng',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function groupLabelText(): string
    {
        return self::GROUP_LABELS[$this->group_label] ?? $this->group_label;
    }
}
