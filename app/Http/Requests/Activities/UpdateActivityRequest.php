<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity'));
    }

    public function rules(): array
    {
        $activity = $this->route('activity');

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('activities', 'code')->ignore($activity->id)],
            'name' => ['required', 'string', 'max:255'],
            'union_group_id' => ['required', 'exists:union_groups,id'],
            'activity_type_id' => ['required', 'exists:activity_types,id'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'goal' => ['nullable', 'string'],
            'expected_quantity' => ['nullable', 'integer', 'min:0'],
            'actual_quantity' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:not_started,preparing,in_progress,completed,cancelled'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã hoạt động.',
            'code.unique' => 'Mã hoạt động này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên hoạt động.',
            'union_group_id.required' => 'Vui lòng chọn tổ công đoàn.',
            'union_group_id.exists' => 'Tổ công đoàn không tồn tại.',
            'activity_type_id.required' => 'Vui lòng chọn loại hoạt động.',
            'activity_type_id.exists' => 'Loại hoạt động không tồn tại.',
            'start_time.required' => 'Vui lòng nhập thời gian bắt đầu.',
            'end_time.required' => 'Vui lòng nhập thời gian kết thúc.',
            'end_time.after_or_equal' => 'Thời gian kết thúc không được trước thời gian bắt đầu.',
            'expected_quantity.min' => 'Số lượng dự kiến phải là số không âm.',
            'actual_quantity.min' => 'Số lượng thực tế phải là số không âm.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'progress.required' => 'Vui lòng nhập tiến độ.',
            'progress.min' => 'Tiến độ phải từ 0 đến 100.',
            'progress.max' => 'Tiến độ phải từ 0 đến 100.',
            'budget.numeric' => 'Kinh phí phải là một số.',
            'budget.min' => 'Kinh phí không được âm.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            if ($user->isOfficer() && $this->union_group_id && ! $user->managesUnionGroup((int) $this->union_group_id)) {
                $validator->errors()->add('union_group_id', 'Bạn không được phép chuyển hoạt động sang tổ công đoàn khác.');
            }
        });
    }
}
