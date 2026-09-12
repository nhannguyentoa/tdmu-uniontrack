@php
    $groups = $criteria->groupBy('group_label');
@endphp

<x-app-layout title="Tự chấm điểm thi đua" :breadcrumbs="['Chấm điểm thi đua' => route('evaluation.report'), $unionGroup->name => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tự chấm điểm — {{ $unionGroup->name }}</h2>
            <p class="text-sm text-slate-500">Năm học {{ $academicYear }}. Điền điểm tự chấm cho từng tiêu chí (không vượt quá điểm chuẩn).</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET">
                <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                    @endforeach
                </select>
            </form>
            <x-btn href="{{ route('evaluation.members.edit', ['union_group' => $unionGroup, 'academic_year' => $academicYear]) }}" variant="secondary">Chấm điểm đoàn viên trong tổ →</x-btn>
        </div>
    </div>

    <x-card no-padding class="mb-5">
        <x-slot name="title">Điểm thưởng tự động từ hoạt động</x-slot>
        <p class="px-5 pt-3 text-xs text-slate-500">
            Đánh dấu "tính điểm thi đua" khi tạo hoạt động — điểm chỉ được ghi nhận khi hoạt động ở trạng thái
            <b>Đã hoàn thành</b>, tiến độ quyết định tỷ lệ điểm đạt được trên điểm tối đa đã đặt.
        </p>
        @if($bonusActivities->isEmpty())
            <div class="p-5"><x-empty-state title="Chưa có hoạt động nào được đánh dấu tính điểm thi đua trong năm học này" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Hoạt động</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-center">Tiến độ</th>
                            <th class="px-5 py-3 text-center">Điểm tối đa</th>
                            <th class="px-5 py-3 text-center">Điểm đạt được</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bonusActivities as $activity)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a></td>
                                <td class="px-5 py-3"><x-status-badge :status="$activity->status" /></td>
                                <td class="px-5 py-3 text-center text-slate-500">{{ $activity->progress }}%</td>
                                <td class="px-5 py-3 text-center text-slate-500">{{ rtrim(rtrim($activity->evaluation_max_score, '0'), '.') }}</td>
                                <td class="px-5 py-3 text-center font-medium text-slate-700">{{ $activity->earnedEvaluationScore() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-700" colspan="4">Tổng điểm thưởng (tối đa {{ rtrim(rtrim($bonusCriterion->max_score, '0'), '.') }})</td>
                            <td class="px-5 py-3 text-center font-semibold text-slate-800">{{ $bonusEarned }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-card>

    <form method="POST" action="{{ route('evaluation.self.update', $unionGroup) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="academic_year" value="{{ $academicYear }}">

        @foreach($groups as $groupLabel => $groupCriteria)
            <x-card no-padding class="mb-5">
                <x-slot name="title">{{ \App\Models\EvaluationCriterion::GROUP_LABELS[$groupLabel] ?? $groupLabel }}</x-slot>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Nội dung tiêu chí</th>
                                <th class="px-5 py-3 text-center">Điểm chuẩn</th>
                                <th class="px-5 py-3 w-32">Tự chấm</th>
                                <th class="px-5 py-3">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($groupCriteria as $criterion)
                                @php($score = $scores->get($criterion->id))
                                @php($isBonus = $criterion->id === $bonusCriterion->id)
                                <tr>
                                    <td class="px-5 py-3 text-slate-700">{{ $criterion->content }}</td>
                                    <td class="px-5 py-3 text-center text-slate-500">{{ rtrim(rtrim($criterion->max_score, '0'), '.') }}</td>
                                    @if($isBonus)
                                        <td class="px-5 py-3">
                                            <input type="text" value="{{ $bonusEarned }}" disabled
                                                   class="block w-24 rounded-lg border-slate-200 bg-slate-100 text-sm text-slate-500">
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-400">Tự động từ hoạt động — xem bảng phía trên.</td>
                                    @else
                                        <td class="px-5 py-3">
                                            <input type="number" step="0.1" min="0" max="{{ $criterion->max_score }}"
                                                   name="scores[{{ $criterion->id }}][self_score]"
                                                   value="{{ old('scores.'.$criterion->id.'.self_score', $score?->self_score) }}"
                                                   class="block w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                        <td class="px-5 py-3">
                                            <input type="text" name="scores[{{ $criterion->id }}][self_note]"
                                                   value="{{ old('scores.'.$criterion->id.'.self_note', $score?->self_note) }}"
                                                   placeholder="Giải trình (nếu có)"
                                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endforeach

        <div class="flex justify-end">
            <x-btn type="submit">Lưu điểm tự chấm</x-btn>
        </div>
    </form>
</x-app-layout>
