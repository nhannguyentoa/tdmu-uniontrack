<?php

namespace App\Policies;

use App\Models\ActivityPlan;
use App\Models\User;

class ActivityPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ActivityPlan $activityPlan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function update(User $user, ActivityPlan $activityPlan): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function delete(User $user, ActivityPlan $activityPlan): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }
}
