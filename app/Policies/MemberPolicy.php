<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function view(User $user, Member $member): bool
    {
        return $user->managesUnionGroup($member->union_group_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOfficer();
    }

    public function update(User $user, Member $member): bool
    {
        return $user->managesUnionGroup($member->union_group_id);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin() || $user->managesUnionGroup($member->union_group_id);
    }

    public function restore(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Member $member): bool
    {
        return $user->isAdmin();
    }
}
