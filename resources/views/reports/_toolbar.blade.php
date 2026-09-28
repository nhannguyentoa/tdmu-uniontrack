@php
    $exportParams = array_merge(request()->query(), ['type' => $report['type']]);
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3 no-print">
    <div>
        <h2 class="text-lg font-semibold text-slate-800">{{ $report['label'] }}</h2>
        <p class="text-sm text-slate-500">Báo cáo hoạt động Công đoàn Trường Đại học Thủ Dầu Một</p>
    </div>
    <div class="flex gap-2">
        @if($report['type'] === 'month' && ! empty($selectedUnionGroupId))
            <x-btn href="{{ route('ai.documents.create', ['type' => 'monthly_group', 'union_group_id' => $selectedUnionGroupId, 'year' => $report['year'], 'month' => $report['month']]) }}" variant="secondary">Soạn báo cáo (AI)</x-btn>
        @elseif($report['type'] === 'year' && auth()->user()->isAdmin())
            <x-btn href="{{ route('ai.documents.create', ['type' => 'yearly_school']) }}" variant="secondary">Soạn báo cáo năm học (AI)</x-btn>
        @endif
        <x-btn href="{{ route('reports.export.excel', $exportParams) }}" variant="secondary">Xuất Excel</x-btn>
        <x-btn href="{{ route('reports.export.pdf', $exportParams) }}" variant="secondary">Xuất PDF</x-btn>
        <x-btn type="button" onclick="window.print()" variant="secondary">In báo cáo</x-btn>
    </div>
</div>

<div class="mb-6 hidden text-center print:block">
    <p class="font-semibold">TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT</p>
    <p class="font-bold uppercase">Báo cáo hoạt động công đoàn</p>
    <p class="text-sm">{{ $report['label'] }} — In lúc {{ now()->format('d/m/Y H:i') }}</p>
</div>
