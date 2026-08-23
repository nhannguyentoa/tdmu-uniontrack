<?php

namespace App\Http\Requests\Members;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Member::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:members,code'],
            'full_name' => ['required', 'string', 'max:255'],
            'dob' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\s.()-]{8,20}$/'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'union_group_id' => ['required', 'exists:union_groups,id'],
            'joined_union_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive,transferred'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã đoàn viên.',
            'code.unique' => 'Mã đoàn viên này đã tồn tại.',
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'dob.before' => 'Ngày sinh không hợp lệ.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'email.email' => 'Email không đúng định dạng.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'union_group_id.required' => 'Vui lòng chọn tổ công đoàn.',
            'union_group_id.exists' => 'Tổ công đoàn không tồn tại.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'avatar.image' => 'Ảnh đại diện phải là hình ảnh.',
            'avatar.mimes' => 'Ảnh đại diện phải có định dạng jpg, jpeg, png hoặc webp.',
            'avatar.max' => 'Ảnh đại diện không được vượt quá 2MB.',
        ];
    }
}
