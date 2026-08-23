<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageParticipants', $this->route('activity'));
    }

    public function rules(): array
    {
        return [
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['required', 'integer', 'exists:members,id'],
            'role_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:registered,attended,absent,cancelled'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_ids.required' => 'Vui lòng chọn ít nhất một đoàn viên tham gia.',
            'member_ids.*.exists' => 'Đoàn viên được chọn không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái tham gia.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $activity = $this->route('activity');
            $existing = $activity->participants()->pluck('member_id')->all();
            $duplicated = array_intersect($this->input('member_ids', []), $existing);

            if (! empty($duplicated)) {
                $validator->errors()->add('member_ids', 'Một hoặc nhiều đoàn viên đã được thêm vào hoạt động này trước đó.');
            }
        });
    }
}
