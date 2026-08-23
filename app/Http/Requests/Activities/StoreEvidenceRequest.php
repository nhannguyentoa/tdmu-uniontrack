<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageEvidences', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Vui lòng chọn ít nhất một tệp để tải lên.',
            'files.*.mimes' => 'Tệp phải có định dạng jpg, jpeg, png, webp hoặc pdf.',
            'files.*.max' => 'Mỗi tệp không được vượt quá 10MB.',
        ];
    }
}
