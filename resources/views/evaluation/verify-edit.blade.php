<x-app-layout title="Thẩm định điểm thi đua" :breadcrumbs="['Chấm điểm thi đua' => route('evaluation.report'), 'Thẩm định' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Thẩm định điểm thi đua</h2>
            <p class="text-sm text-slate-500">Năm học {{ $academicYear }}. Chọn Ban chuyên môn để thẩm định các tiêu chí thuộc phạm vi Ban đó cho tất cả tổ công đoàn.</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                @endforeach
            </select>
            <select name="department_id" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected($department->id === $departmentId)>{{ $department->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($criteria->isEmpty())
        <x-card><x-empty-state title="Ban này chưa được phân công tiêu chí nào" /></x-card>
    @else
        <form method="POST" action="{{ route('evaluation.verify.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="academic_year" value="{{ $academicYear }}">
            <input type="hidden" name="department_id" value="{{ $departmentId }}">

            @foreach($criteria as $criterion)
                @php($criterionScores = $scores->get($criterion->id) ?? collect())
                <x-card no-padding class="mb-5">
                    <x-slot name="title">Tiêu chí #{{ $criterion->order_no }} (chuẩn {{ rtrim(rtrim($criterion->max_score, '0'), '.') }} điểm)</x-slot>
                    <p class="px-5 pt-3 text-sm text-slate-600">{{ $criterion->content }}</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Tổ công đoàn</th>
                                    <th class="px-5 py-3 text-center">Tự chấm</th>
                                    <th class="px-5 py-3 w-32">Thẩm định</th>
                                    <th class="px-5 py-3">Ghi chú thẩm định</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($unionGroups as $group)
                                    @php($score = $criterionScores->get($group->id))
                                    <tr>
                                        <td class="px-5 py-3 text-slate-700">{{ $group->name }}</td>
                                        <td class="px-5 py-3 text-center text-slate-500">{{ $score?->self_score ?? '—' }}</td>
                                        <td class="px-5 py-3">
                                            <input type="number" step="0.1" min="0" max="{{ $criterion->max_score }}"
                                                   name="scores[{{ $criterion->id }}][{{ $group->id }}][verified_score]"
                                                   value="{{ old('scores.'.$criterion->id.'.'.$group->id.'.verified_score', $score?->verified_score) }}"
                                                   class="block w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                        <td class="px-5 py-3">
                                            <input type="text" name="scores[{{ $criterion->id }}][{{ $group->id }}][verified_note]"
                                                   value="{{ old('scores.'.$criterion->id.'.'.$group->id.'.verified_note', $score?->verified_note) }}"
                                                   placeholder="Lý do trừ điểm (nếu có)"
                                                   class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endforeach

            <div class="flex justify-end">
                <x-btn type="submit">Lưu điểm thẩm định</x-btn>
            </div>
        </form>
    @endif
</x-app-layout>
