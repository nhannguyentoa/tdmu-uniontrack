<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'name', 'department', 'leader_name', 'leader_phone',
    'leader_email', 'established_date', 'status', 'description',
])]
class UnionGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'established_date' => 'date',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function officers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'union_group_officer');
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    /**
     * Các hoạt động do tổ khác chủ trì mà tổ này phối hợp/tham gia.
     */
    public function collaboratingActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_union_groups')
            ->withPivot(['role', 'note'])
            ->withTimestamps();
    }

    public function evaluationScores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function activityPlans(): HasMany
    {
        return $this->hasMany(ActivityPlan::class, 'host_union_group_id');
    }
}
