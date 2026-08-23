<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'code', 'name', 'union_group_id', 'activity_type_id', 'responsible_user_id',
    'responsible_name', 'start_time', 'end_time', 'location', 'content', 'goal',
    'expected_quantity', 'actual_quantity', 'status', 'progress', 'budget',
    'note', 'created_by', 'updated_by',
])]
class Activity extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_NOT_STARTED => 'Chưa bắt đầu',
        self::STATUS_PREPARING => 'Đang chuẩn bị',
        self::STATUS_IN_PROGRESS => 'Đang thực hiện',
        self::STATUS_COMPLETED => 'Đã hoàn thành',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    public const STATUS_BADGES = [
        self::STATUS_NOT_STARTED => 'secondary',
        self::STATUS_PREPARING => 'info',
        self::STATUS_IN_PROGRESS => 'warning',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'budget' => 'decimal:2',
        ];
    }

    public function unionGroup(): BelongsTo
    {
        return $this->belongsTo(UnionGroup::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'activity_participants')
            ->withPivot(['role_note', 'status', 'registered_at', 'note'])
            ->withTimestamps();
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ActivityEvidence::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ActivityStatusHistory::class)->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->status !== self::STATUS_CANCELLED
            && $this->end_time !== null
            && $this->end_time->isPast();
    }

    public function scopeForUnionGroup(Builder $query, ?int $unionGroupId): Builder
    {
        return $query->when($unionGroupId, fn (Builder $q) => $q->where('union_group_id', $unionGroupId));
    }

    public function scopeInPeriod(Builder $query, ?Carbon $from, ?Carbon $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->where('start_time', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('start_time', '<=', $to));
    }
}
