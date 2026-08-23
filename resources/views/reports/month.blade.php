<x-app-layout title="Báo cáo tháng" :breadcrumbs="['Báo cáo & thống kê' => null]">
    @include('reports._tabs')
    @include('reports._toolbar')

    <x-card class="mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Năm</label>
                <input type="number" name="year" value="{{ $report['year'] }}" class="mt-0.5 block w-32 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-slate-500">Tháng</label>
                <select name="month" class="mt-0.5 block w-32 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected($report['month'] === $m)>Tháng {{ $m }}</option>
                    @endfor
                </select>
            </div>
            @if($unionGroups->count() > 1)
                <div>
                    <label class="text-xs text-slate-500">Tổ công đoàn</label>
                    <select name="union_group_id" class="mt-0.5 block w-56 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Tất cả tổ --</option>
                        @foreach($unionGroups as $group)
                            <option value="{{ $group->id }}" @selected((string) $selectedUnionGroupId === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <x-btn type="submit">Xem báo cáo</x-btn>
        </form>
    </x-card>

    @include('reports._body')
</x-app-layout>
