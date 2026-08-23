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
}
