<x-app-layout title="Báo cáo năm" :breadcrumbs="['Báo cáo & thống kê' => null]">
    @include('reports._tabs')
    @include('reports._toolbar')

    <x-card class="mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Năm</label>
                <input type="number" name="year" value="{{ $report['year'] }}" class="mt-0.5 block w-32 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
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

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Tổng số đoàn viên" :value="$report['total_members']" color="sky" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />' />
    </div>

    @include('reports._body')
</x-app-layout>
