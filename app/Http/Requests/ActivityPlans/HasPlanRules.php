<?php

namespace App\Http\Requests\ActivityPlans;

use Illuminate\Validation\Validator;

trait HasPlanRules
{
    protected function planRules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:20'],
            'month' => ['required', 'integer', 'between:1,12'],
            'title' => ['required', 'string', 'max:255'],
            'host_union_group_id' => ['nullable', 'exists:union_groups,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'activity_type_id' => ['nullable', 'exists:activity_types,id'],
            'note' => ['nullable', 'string'],
            'is_cancelled' => ['boolean'],
            'counts_for_evaluation' => ['boolean'],
            'evaluation_max_score' => ['nullable', 'required_if:counts_for_evaluation,1', 'numeric', 'min:0.5', 'max:50'],
            'collaborating_groups' => ['nullable', 'array'],
            'collaborating_groups.phoi_hop' => ['nullable', 'array'],
            'collaborating_groups.phoi_hop.*' => ['exists:union_groups,id'],
            'collaborating_groups.tham_gia' => ['nullable', 'array'],
            'collaborating_groups.tham_gia.*' => ['exists:union_groups,id'],
        ];
    }

    protected function planMessages(): array
    {
        return [
            'academic_year.required' => 'Vui lòng nhập năm học.',
            'month.required' => 'Vui lòng chọn tháng.',
            'month.between' => 'Tháng phải từ 1 đến 12.',
            'title.required' => 'Vui lòng nhập nội dung hoạt động.',
            'activity_type_id.exists' => 'Loại hoạt động không tồn tại.',
            'evaluation_max_score.required_if' => 'Vui lòng nhập điểm thi đua tối đa cho hoạt động này.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $phoiHop = array_map('intval', $this->input('collaborating_groups.phoi_hop', []));
            $thamGia = array_map('intval', $this->input('collaborating_groups.tham_gia', []));
            $host = (int) $this->input('host_union_group_id');

            if ($host && in_array($host, array_merge($phoiHop, $thamGia), true)) {
                $validator->errors()->add('collaborating_groups', 'Đơn vị đăng cai không thể đồng thời là tổ phối hợp/tham gia.');
            }

            if (! empty(array_intersect($phoiHop, $thamGia))) {
                $validator->errors()->add('collaborating_groups', 'Một tổ không thể vừa là tổ phối hợp vừa là tổ tham gia.');
            }
        });
    }
}
