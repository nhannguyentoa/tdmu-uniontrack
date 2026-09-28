<?php

namespace App\Http\Requests\ActivityPlans;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityPlanRequest extends FormRequest
{
    use HasPlanRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity_plan'));
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
