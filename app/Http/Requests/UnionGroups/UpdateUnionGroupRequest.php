<?php

namespace App\Http\Requests\UnionGroups;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('union_group'));
    }

    public function rules(): array
    {
        $unionGroup = $this->route('union_group');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('union_groups', 'code')->ignore($unionGroup->id)],
            'name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'leader_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.()-]{8,20}$/'],
            'leader_email' => ['nullable', 'email', 'max:255'],
            'established_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã tổ công đoàn.',
            'code.unique' => 'Mã tổ công đoàn này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên tổ công đoàn.',
            'leader_phone.regex' => 'Số điện thoại không hợp lệ.',
            'leader_email.email' => 'Email không đúng định dạng.',
            'established_date.date' => 'Ngày thành lập không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
        ];
    }
}
