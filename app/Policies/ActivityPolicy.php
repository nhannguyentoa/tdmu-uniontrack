<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Activity $activity): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->managesUnionGroup($activity->union_group_id);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }

    public function manageParticipants(User $user, Activity $activity): bool
    {
        return $user->managesUnionGroup($activity->union_group_id);
    }

    public function manageEvidences(User $user, Activity $activity): bool
    {
        return $user->managesUnionGroup($activity->union_group_id);
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }
}
