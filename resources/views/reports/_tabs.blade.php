@php($current = $report['type'])

<div class="mb-5 flex gap-2 border-b border-slate-200">
    <a href="{{ route('reports.month') }}" @class(['px-4 py-2 text-sm font-medium border-b-2 -mb-px', 'border-blue-600 text-blue-600' => $current === 'month', 'border-transparent text-slate-500 hover:text-slate-700' => $current !== 'month'])>Báo cáo tháng</a>
    <a href="{{ route('reports.semester') }}" @class(['px-4 py-2 text-sm font-medium border-b-2 -mb-px', 'border-blue-600 text-blue-600' => $current === 'semester', 'border-transparent text-slate-500 hover:text-slate-700' => $current !== 'semester'])>Báo cáo học kỳ</a>
    <a href="{{ route('reports.year') }}" @class(['px-4 py-2 text-sm font-medium border-b-2 -mb-px', 'border-blue-600 text-blue-600' => $current === 'year', 'border-transparent text-slate-500 hover:text-slate-700' => $current !== 'year'])>Báo cáo năm</a>
</div>
