<x-app-layout title="Báo cáo học kỳ" :breadcrumbs="['Báo cáo & thống kê' => null]">
    @include('reports._tabs')
    @include('reports._toolbar')

    <x-card class="mb-6 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Năm học</label>
                <input type="number" name="year" value="{{ $report['year'] }}" class="mt-0.5 block w-32 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-slate-500">Học kỳ</label>
                <select name="semester" class="mt-0.5 block w-40 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1" @selected($report['semester'] === 1)>Học kỳ 1</option>
                    <option value="2" @selected($report['semester'] === 2)>Học kỳ 2</option>
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
