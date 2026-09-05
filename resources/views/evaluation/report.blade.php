@php
    use App\Services\EvaluationService;
    $user = auth()->user();
@endphp

<x-app-layout title="Báo cáo Hội đồng thi đua" :breadcrumbs="['Chấm điểm thi đua' => null, 'Báo cáo Hội đồng' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Báo cáo tổng hợp kết quả đánh giá thi đua</h2>
            <p class="text-sm text-slate-500">Năm học {{ $academicYear }} · {{ $summary->count() }} tổ công đoàn</p>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET">
                <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                    @endforeach
                </select>
            </form>
            <x-btn href="{{ route('evaluation.report.export', ['academic_year' => $academicYear]) }}" variant="secondary">Xuất Excel</x-btn>
            @if($user->isAdmin())
                <x-btn href="{{ route('evaluation-criteria.index', ['academic_year' => $academicYear]) }}" variant="secondary">Tiêu chí</x-btn>
                <x-btn href="{{ route('evaluation.verify.edit', ['academic_year' => $academicYear]) }}">Thẩm định</x-btn>
            @endif
        </div>
    </div>

    @php
        $counts = $summary->countBy('classification');
    @endphp

    <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <x-stat-card label="Tổng số tổ" :value="$summary->count()" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />' />
        <x-stat-card label="Xuất sắc nhiệm vụ" :value="$counts[EvaluationService::CLASSIFICATION_EXCELLENT] ?? 0" color="green" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        <x-stat-card label="Hoàn thành tốt" :value="$counts[EvaluationService::CLASSIFICATION_GOOD] ?? 0" color="sky" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' />
        <x-stat-card label="Chưa thẩm định xong" :value="$counts[EvaluationService::CLASSIFICATION_PENDING] ?? 0" color="slate" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />' />
    </div>

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">STT</th>
                        <th class="px-5 py-3">Tên tổ công đoàn</th>
                        <th class="px-5 py-3 text-center">Điểm chuẩn</th>
                        <th class="px-5 py-3 text-center">Tổng tự chấm</th>
                        <th class="px-5 py-3 text-center">Tổng thẩm định</th>
                        <th class="px-5 py-3 text-center">Chênh lệch</th>
                        <th class="px-5 py-3">Xếp loại</th>
                        <th class="px-5 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($summary as $i => $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $row['union_group']->name }}</td>
                            <td class="px-5 py-3 text-center text-slate-500">{{ $row['standard_total'] }}</td>
                            <td class="px-5 py-3 text-center text-slate-700">{{ $row['self_total'] ?? '—' }}</td>
                            <td class="px-5 py-3 text-center text-slate-700">
                                {{ $row['verified_total'] ?? '—' }}
                                <p class="text-[11px] text-slate-400">{{ $row['verified_count'] }}/{{ $row['criteria_count'] }} tiêu chí</p>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($row['diff'] !== null)
                                    <span @class(['font-medium', 'text-red-600' => $row['diff'] < 0, 'text-green-600' => $row['diff'] > 0])>{{ $row['diff'] > 0 ? '+' : '' }}{{ $row['diff'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                    'bg-green-100 text-green-700' => $row['classification'] === EvaluationService::CLASSIFICATION_EXCELLENT,
                                    'bg-blue-100 text-blue-700' => $row['classification'] === EvaluationService::CLASSIFICATION_GOOD,
                                    'bg-red-100 text-red-700' => $row['classification'] === EvaluationService::CLASSIFICATION_NOT_MET,
                                    'bg-slate-100 text-slate-600' => $row['classification'] === EvaluationService::CLASSIFICATION_PENDING,
                                ])>{{ $row['classification'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($user->isAdmin() || $user->managesUnionGroup($row['union_group']->id))
                                    <x-btn href="{{ route('evaluation.self.edit', ['union_group' => $row['union_group'], 'academic_year' => $academicYear]) }}" variant="ghost">Tự chấm</x-btn>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-app-layout>
