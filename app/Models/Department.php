<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description'])]
class Department extends Model
{
    use HasFactory;

    public function evaluationCriteria(): HasMany
    {
        return $this->hasMany(EvaluationCriterion::class);
    }

    public function activityPlans(): HasMany
    {
        return $this->hasMany(ActivityPlan::class);
    }
}
