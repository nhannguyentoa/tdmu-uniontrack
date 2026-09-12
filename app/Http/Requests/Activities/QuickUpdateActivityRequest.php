<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class QuickUpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'in:not_started,preparing,in_progress,completed,cancelled'],
            'progress' => ['sometimes', 'required', 'integer', 'in:0,25,50,75,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'progress.in' => 'Tiến độ chỉ chọn theo các mốc 0, 25, 50, 75, 100.',
        ];
    }
}
