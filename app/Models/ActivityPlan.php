<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'academic_year', 'month', 'title', 'host_union_group_id', 'department_id',
    'note', 'status', 'activity_id', 'created_by',
])]
class ActivityPlan extends Model
{
    use HasFactory;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PLANNED => 'Dự kiến',
        self::STATUS_IN_PROGRESS => 'Đang triển khai',
        self::STATUS_DONE => 'Đã thực hiện',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const STATUS_BADGES = [
        self::STATUS_PLANNED => 'secondary',
        self::STATUS_IN_PROGRESS => 'warning',
        self::STATUS_DONE => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    public function hostUnionGroup(): BelongsTo
    {
        return $this->belongsTo(UnionGroup::class, 'host_union_group_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }
}
