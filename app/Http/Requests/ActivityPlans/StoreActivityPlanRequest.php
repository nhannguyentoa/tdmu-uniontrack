<?php

namespace App\Http\Requests\ActivityPlans;

use App\Models\ActivityPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityPlanRequest extends FormRequest
{
    use HasPlanRules;

    public function authorize(): bool
    {
        return $this->user()->can('create', ActivityPlan::class);
    }

    public function rules(): array
    {
        return $this->planRules();
    }

    public function messages(): array
    {
        return $this->planMessages();
    }
}
