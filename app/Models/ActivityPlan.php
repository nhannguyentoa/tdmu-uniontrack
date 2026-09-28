<?php

namespace App\Models;

use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'academic_year', 'month', 'title', 'host_union_group_id', 'department_id', 'activity_type_id',
    'note', 'counts_for_evaluation', 'evaluation_max_score', 'status', 'activity_id', 'created_by',
])]
class ActivityPlan extends Model
{
    use HasFactory;

    // Cột status chỉ lưu 'planned' hoặc 'cancelled'; trạng thái hiển thị được tính bởi displayStatusKey().
    public const STATUS_PLANNED = 'planned';
    public const STATUS_CANCELLED = 'cancelled';

    public const DISPLAY_PLANNED = 'planned';
    public const DISPLAY_OVERDUE = 'overdue';

    protected function casts(): array
    {
        return [
            'counts_for_evaluation' => 'boolean',
            'evaluation_max_score' => 'decimal:2',
        ];
    }

    public function hostUnionGroup(): BelongsTo
    {
        return $this->belongsTo(UnionGroup::class, 'host_union_group_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Các tổ phối hợp / tham gia dự kiến (được điền sẵn sang hoạt động khi chuyển kế hoạch thành hoạt động).
     */
    public function collaboratingGroups(): BelongsToMany
    {
        return $this->belongsToMany(UnionGroup::class, 'activity_plan_union_groups')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Ngày đầu tiên của tháng kế hoạch (theo năm học).
     */
    public function monthStart(): Carbon
    {
        return AcademicYear::monthStart($this->academic_year, (int) $this->month);
    }

    public function monthEnd(): Carbon
    {
        return $this->monthStart()->copy()->endOfMonth();
    }

    /**
     * Kế hoạch chưa chuyển thành hoạt động mà tháng kế hoạch đã qua.
     */
    public function isOverdue(): bool
    {
        return ! $this->isCancelled() && $this->activity === null && $this->monthEnd()->isPast();
    }

    /**
     * Trạng thái hiển thị (khóa dùng với <x-status-badge>):
     * - đã chuyển thành hoạt động: đồng bộ đúng trạng thái của hoạt động;
     * - chưa chuyển: "Đã hủy" nếu đánh dấu hủy, "Quá hạn" nếu tháng kế hoạch đã qua, ngược lại "Dự kiến".
     */
    public function displayStatusKey(): string
    {
        if ($this->activity !== null) {
            return $this->activity->status;
        }

        if ($this->isCancelled()) {
            return Activity::STATUS_CANCELLED;
        }

        return $this->isOverdue() ? self::DISPLAY_OVERDUE : self::DISPLAY_PLANNED;
    }
}
