<?php

namespace App\Http\Requests\ActivityPlans;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity_plan'));
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:20'],
            'month' => ['required', 'integer', 'between:1,12'],
            'title' => ['required', 'string', 'max:255'],
            'host_union_group_id' => ['nullable', 'exists:union_groups,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'note' => ['nullable', 'string'],
            'status' => ['required', 'in:planned,in_progress,done,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.required' => 'Vui lòng nhập năm học.',
            'month.required' => 'Vui lòng chọn tháng.',
            'month.between' => 'Tháng phải từ 1 đến 12.',
            'title.required' => 'Vui lòng nhập nội dung hoạt động.',
        ];
    }
}
