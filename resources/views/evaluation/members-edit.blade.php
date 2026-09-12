<x-app-layout title="Chấm điểm thi đua đoàn viên" :breadcrumbs="['Chấm điểm thi đua' => route('evaluation.report'), $unionGroup->name => route('union-groups.show', $unionGroup), 'Đoàn viên' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Xếp loại thi đua đoàn viên — {{ $unionGroup->name }}</h2>
            <p class="text-sm text-slate-500">
                Năm học {{ $academicYear }}. Tổ có {{ $totalGroupActivities }} hoạt động trong năm học này —
                tỷ lệ tham gia dưới đây chỉ để tham khảo, không tự động quyết định xếp loại.
            </p>
        </div>
        <form method="GET">
            <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if($members->isEmpty())
        <x-card><x-empty-state title="Tổ chưa có đoàn viên đang hoạt động" /></x-card>
    @else
        <form method="POST" action="{{ route('evaluation.members.update', $unionGroup) }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="academic_year" value="{{ $academicYear }}">

            <x-card no-padding>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">Đoàn viên</th>
                                <th class="px-5 py-3 text-center">Tỷ lệ tham gia hoạt động</th>
                                <th class="px-5 py-3 w-56">Xếp loại</th>
                                <th class="px-5 py-3 w-24">Điểm số</th>
                                <th class="px-5 py-3">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($members as $member)
                                @php($evaluation = $evaluations->get($member->id))
                                @php($attended = $attendedCounts->get($member->id, 0))
                                <tr>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('members.show', $member) }}" class="font-medium text-blue-600 hover:underline">{{ $member->full_name }}</a>
                                        <p class="text-xs text-slate-400">{{ $member->code }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-500">
                                        @if($totalGroupActivities > 0)
                                            {{ $attended }}/{{ $totalGroupActivities }} ({{ round($attended / $totalGroupActivities * 100) }}%)
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <select name="evaluations[{{ $member->id }}][classification]"
                                                class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">-- Chưa xếp loại --</option>
                                            @foreach(\App\Models\MemberEvaluation::CLASSIFICATIONS as $key => $label)
                                                <option value="{{ $key }}" @selected(old('evaluations.'.$member->id.'.classification', $evaluation?->classification) === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="number" step="0.1" min="0" max="100"
                                               name="evaluations[{{ $member->id }}][score]"
                                               value="{{ old('evaluations.'.$member->id.'.score', $evaluation?->score) }}"
                                               class="block w-20 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                    <td class="px-5 py-3">
                                        <input type="text" name="evaluations[{{ $member->id }}][note]"
                                               value="{{ old('evaluations.'.$member->id.'.note', $evaluation?->note) }}"
                                               placeholder="Ghi chú (nếu có)"
                                               class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>

            <div class="mt-5 flex justify-end">
                <x-btn type="submit">Lưu xếp loại thi đua</x-btn>
            </div>
        </form>
    @endif
</x-app-layout>
