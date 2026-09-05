@php
    $groups = $criteria->groupBy('group_label');
@endphp

<x-app-layout title="Tự chấm điểm thi đua" :breadcrumbs="['Chấm điểm thi đua' => route('evaluation.report'), $unionGroup->name => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Tự chấm điểm — {{ $unionGroup->name }}</h2>
            <p class="text-sm text-slate-500">Năm học {{ $academicYear }}. Điền điểm tự chấm cho từng tiêu chí (không vượt quá điểm chuẩn).</p>
        </div>
        <form method="GET">
            <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

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
                                <tr>
                                    <td class="px-5 py-3 text-slate-700">{{ $criterion->content }}</td>
                                    <td class="px-5 py-3 text-center text-slate-500">{{ rtrim(rtrim($criterion->max_score, '0'), '.') }}</td>
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
