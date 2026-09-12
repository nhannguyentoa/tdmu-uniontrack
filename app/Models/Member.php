<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'user_id', 'full_name', 'dob', 'gender', 'email', 'phone',
    'position', 'department', 'union_group_id', 'joined_union_date',
    'status', 'avatar_path', 'note',
])]
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'joined_union_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unionGroup(): BelongsTo
    {
        return $this->belongsTo(UnionGroup::class);
    }

    public function activityParticipants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_participants')
            ->withPivot(['role_note', 'status', 'registered_at', 'note'])
            ->withTimestamps();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(MemberEvaluation::class);
    }
}
