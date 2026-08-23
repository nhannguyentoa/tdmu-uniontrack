<x-app-layout title="Danh sách hoạt động" :breadcrumbs="['Hoạt động' => null]">
    <x-card class="mb-5">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên/mã hoạt động..."
                   class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:col-span-2">

            <select name="union_group_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tổ công đoàn --</option>
                @foreach($unionGroups as $group)
                    <option value="{{ $group->id }}" @selected(request('union_group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>

            <select name="activity_type_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Loại hoạt động --</option>
                @foreach($activityTypes as $type)
                    <option value="{{ $type->id }}" @selected(request('activity_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Trạng thái --</option>
                @foreach(\App\Models\Activity::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="sort" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="start_time_desc" @selected(request('sort', 'start_time_desc') === 'start_time_desc')>Mới nhất trước</option>
                <option value="start_time_asc" @selected(request('sort') === 'start_time_asc')>Cũ nhất trước</option>
                <option value="name_asc" @selected(request('sort') === 'name_asc')>Tên A-Z</option>
            </select>

            <div>
                <label class="text-xs text-slate-500">Từ ngày</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-0.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-slate-500">Đến ngày</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-0.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-slate-500">Năm</label>
                <input type="number" name="year" value="{{ request('year') }}" placeholder="VD: {{ now()->year }}" class="mt-0.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-xs text-slate-500">Tháng</label>
                <select name="month" class="mt-0.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">--</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected((int) request('month') === $m)>Tháng {{ $m }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-2">
                <x-btn type="submit">Áp dụng bộ lọc</x-btn>
                @if(request()->anyFilled(['search', 'union_group_id', 'activity_type_id', 'status', 'date_from', 'date_to', 'year', 'month']))
                    <x-btn href="{{ route('activities.index') }}" variant="ghost">Đặt lại bộ lọc</x-btn>
                @endif
            </div>
        </form>
    </x-card>

    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">Tìm thấy {{ $activities->total() }} hoạt động</p>
        <div class="flex gap-2">
            <x-btn href="{{ route('activities.export', request()->query()) }}" variant="secondary">Xuất Excel</x-btn>
            @can('create', \App\Models\Activity::class)
                <x-btn href="{{ route('activities.create') }}">+ Thêm hoạt động</x-btn>
            @endcan
        </div>
    </div>

    @if($activities->isEmpty())
        <x-empty-state title="Không tìm thấy hoạt động" description="Thử thay đổi bộ lọc hoặc tạo hoạt động mới." />
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($activities as $activity)
                <a href="{{ route('activities.show', $activity) }}" class="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="mb-2 flex items-start justify-between gap-2">
                        <span class="text-xs font-medium text-slate-400">{{ $activity->code }}</span>
                        <x-status-badge :status="$activity->status" />
                    </div>
                    <h3 class="mb-1 font-semibold text-slate-800">{{ $activity->name }}</h3>
                    <p class="mb-3 text-xs text-slate-500">{{ $activity->unionGroup?->name }} · {{ $activity->activityType?->name }}</p>
                    <p class="mb-3 text-xs text-slate-400">
                        {{ $activity->start_time->format('d/m/Y H:i') }} — {{ $activity->end_time->format('d/m/Y H:i') }}
                        @if($activity->isOverdue())
                            <span class="ml-1 font-medium text-red-500">(Quá hạn)</span>
                        @endif
                    </p>
                    <x-progress-bar :value="$activity->progress" />
                    <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                        <span>{{ $activity->location ?: 'Chưa có địa điểm' }}</span>
                        <span>{{ $activity->members_count }} người tham gia</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $activities->links() }}</div>
    @endif
</x-app-layout>
