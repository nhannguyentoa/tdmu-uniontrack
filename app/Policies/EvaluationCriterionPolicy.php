<?php

namespace App\Policies;

use App\Models\EvaluationCriterion;
use App\Models\User;

class EvaluationCriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, EvaluationCriterion $evaluationCriterion): bool
    {
        return $user->isAdmin();
    }
}
