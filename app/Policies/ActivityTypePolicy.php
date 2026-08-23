<?php

namespace App\Policies;

use App\Models\ActivityType;
use App\Models\User;

class ActivityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ActivityType $activityType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ActivityType $activityType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ActivityType $activityType): bool
    {
        return $user->isAdmin();
    }
}
