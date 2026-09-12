<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar_path', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_OFFICER = 'officer';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOfficer(): bool
    {
        return $this->role === self::ROLE_OFFICER;
    }

    /**
     * Các tổ công đoàn mà cán bộ công đoàn này được phân công phụ trách.
     */
    public function managedUnionGroups(): BelongsToMany
    {
        return $this->belongsToMany(UnionGroup::class, 'union_group_officer');
    }

    public function createdActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'created_by');
    }

    public function uploadedEvidences(): HasMany
    {
        return $this->hasMany(ActivityEvidence::class, 'uploaded_by');
    }

    public function managesUnionGroup(int $unionGroupId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->isOfficer() && $this->managedUnionGroups()->where('union_groups.id', $unionGroupId)->exists();
    }
}
