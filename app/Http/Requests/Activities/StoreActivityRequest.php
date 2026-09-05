<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Activity::class);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:activities,code'],
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
            'collaborating_groups' => ['nullable', 'array'],
            'collaborating_groups.phoi_hop' => ['nullable', 'array'],
            'collaborating_groups.phoi_hop.*' => ['exists:union_groups,id'],
            'collaborating_groups.tham_gia' => ['nullable', 'array'],
            'collaborating_groups.tham_gia.*' => ['exists:union_groups,id'],
            'from_activity_plan_id' => ['nullable', 'exists:activity_plans,id'],
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
                $validator->errors()->add('union_group_id', 'Bạn không được phép tạo hoạt động cho tổ công đoàn khác.');
            }

            $this->validateCollaboratingGroups($validator);
        });
    }

    protected function validateCollaboratingGroups(Validator $validator): void
    {
        $phoiHop = $this->input('collaborating_groups.phoi_hop', []);
        $thamGia = $this->input('collaborating_groups.tham_gia', []);
        $primary = (int) $this->union_group_id;

        if (in_array($primary, array_map('intval', array_merge($phoiHop, $thamGia)), true)) {
            $validator->errors()->add('collaborating_groups', 'Tổ chủ trì không thể đồng thời là tổ phối hợp/tham gia.');
        }

        $overlap = array_intersect(array_map('intval', $phoiHop), array_map('intval', $thamGia));
        if (! empty($overlap)) {
            $validator->errors()->add('collaborating_groups', 'Một tổ không thể vừa là tổ phối hợp vừa là tổ tham gia.');
        }
    }
}
