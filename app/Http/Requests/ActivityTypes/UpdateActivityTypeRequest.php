<?php

namespace App\Http\Requests\ActivityTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity_type'));
    }

    public function rules(): array
    {
        $activityType = $this->route('activity_type');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('activity_types', 'name')->ignore($activityType->id)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên loại hoạt động.',
            'name.unique' => 'Loại hoạt động này đã tồn tại.',
        ];
    }
}
