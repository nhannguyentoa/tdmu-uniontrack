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

    <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-800">
        Trợ lý AI ({{ \App\Services\Ai\AiManager::PROVIDERS[config('ai.provider')] ?? config('ai.provider') }}) chỉ gợi ý dựa trên hoạt động và minh chứng của tổ trong hệ thống.
        Tên đoàn viên không được gửi đi; ảnh/PDF minh chứng được gửi nguyên bản tới nhà cung cấp AI (nếu không phải chế độ giả lập).
        Bấm "Áp dụng" chỉ điền sẵn vào ô điểm — điểm chỉ được ghi khi bạn bấm "Lưu điểm thẩm định".
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
                    @if($criterion->group_label !== \App\Models\EvaluationCriterion::GROUP_BONUS)
                        <div class="flex flex-wrap items-center gap-3 px-5 pt-3"
                             x-data="{
                                 running: false, done: 0, total: 0,
                                 async runAll() {
                                     const cells = [...document.querySelectorAll('[data-ai-cell=&quot;c{{ $criterion->id }}&quot;]')];
                                     this.running = true; this.done = 0; this.total = cells.length;
                                     for (const el of cells) { await Alpine.$data(el).run(); this.done++; }
                                     this.running = false;
                                 }
                             }">
                            <button type="button" @click="runAll()" :disabled="running"
                                    class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 disabled:opacity-60">
                                AI gợi ý cả cột (16 tổ)
                            </button>
                            <span x-show="running" x-cloak class="text-xs text-slate-500">Đang chạy <span x-text="done"></span>/<span x-text="total"></span>...</span>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Tổ công đoàn</th>
                                    <th class="px-5 py-3 text-center">Tự chấm</th>
                                    <th class="px-5 py-3 w-32">Thẩm định</th>
                                    <th class="px-5 py-3">Ghi chú thẩm định</th>
                                    <th class="px-5 py-3">Trợ lý AI</th>
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
                                                   id="verified-{{ $criterion->id }}-{{ $group->id }}"
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
                                        <td class="px-5 py-3 min-w-[16rem]">
                                            @if($criterion->group_label !== \App\Models\EvaluationCriterion::GROUP_BONUS)
                                                <x-ai-suggest-cell
                                                    :url="route('evaluation.ai.suggest', [$criterion, $group])"
                                                    :initial="$aiSuggestions->get($criterion->id.'-'.$group->id)?->toPayload()"
                                                    :apply-target="'verified-'.$criterion->id.'-'.$group->id"
                                                    :column="'c'.$criterion->id" />
                                            @endif
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
