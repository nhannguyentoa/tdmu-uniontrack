<?php

namespace App\Policies;

use App\Models\UnionGroup;
use App\Models\User;

class UnionGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function view(User $user, UnionGroup $unionGroup): bool
    {
        return $user->managesUnionGroup($unionGroup->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, UnionGroup $unionGroup): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, UnionGroup $unionGroup): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, UnionGroup $unionGroup): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, UnionGroup $unionGroup): bool
    {
        return $user->isAdmin();
    }
}
