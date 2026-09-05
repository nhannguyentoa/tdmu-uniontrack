<?php

namespace App\Http\Requests\Evaluation;

use App\Models\EvaluationCriterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EvaluationCriterion::class);
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:20'],
            'group_label' => ['required', 'in:I,II,III,thuong'],
            'order_no' => [
                'required', 'integer', 'min:1',
                Rule::unique('evaluation_criteria', 'order_no')->where('academic_year', $this->input('academic_year')),
            ],
            'content' => ['required', 'string'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.required' => 'Vui lòng nhập năm học.',
            'order_no.required' => 'Vui lòng nhập số thứ tự tiêu chí.',
            'order_no.unique' => 'Số thứ tự này đã tồn tại trong năm học đó.',
            'content.required' => 'Vui lòng nhập nội dung tiêu chí.',
            'max_score.required' => 'Vui lòng nhập điểm chuẩn.',
        ];
    }
}
