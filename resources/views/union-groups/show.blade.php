<x-app-layout :title="$unionGroup->name" :breadcrumbs="['Danh sách tổ' => route('union-groups.index'), $unionGroup->name => null]">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-semibold text-slate-800">{{ $unionGroup->name }}</h2>
                <x-status-badge :status="$unionGroup->status" />
            </div>
            <p class="text-sm text-slate-500">Mã tổ: {{ $unionGroup->code }} · {{ $unionGroup->department }}</p>
        </div>
        <div class="flex gap-2">
            @can('update', $unionGroup)
                <x-btn href="{{ route('union-groups.edit', $unionGroup) }}" variant="secondary">Chỉnh sửa</x-btn>
            @endcan
            @if(auth()->user()->managesUnionGroup($unionGroup->id))
                <x-btn href="{{ route('evaluation.members.edit', $unionGroup) }}" variant="secondary">Chấm điểm thi đua đoàn viên</x-btn>
            @endif
            <x-btn href="{{ route('members.create', ['union_group_id' => $unionGroup->id]) }}">+ Thêm đoàn viên</x-btn>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-6">
        <x-stat-card label="Tổng đoàn viên" :value="$stats['total_members']" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />' />
        <x-stat-card label="Tổng hoạt động" :value="$stats['total_activities']" color="sky" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />' />
        <x-stat-card label="Đã hoàn thành" :value="$stats['completed']" color="green" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        <x-stat-card label="Đang thực hiện" :value="$stats['in_progress']" color="amber" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        <x-stat-card label="Chưa bắt đầu" :value="$stats['not_started']" color="slate" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4m6 0a10 10 0 11-20 0 10 10 0 0120 0z" />' />
        <x-stat-card label="Lượt tham gia" :value="$stats['total_participants']" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />' />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card title="Thông tin tổ công đoàn" class="lg:col-span-1">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Người phụ trách</dt><dd class="font-medium text-slate-700">{{ $unionGroup->leader_name ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Điện thoại</dt><dd class="font-medium text-slate-700">{{ $unionGroup->leader_phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-700">{{ $unionGroup->leader_email ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Ngày thành lập</dt><dd class="font-medium text-slate-700">{{ $unionGroup->established_date?->format('d/m/Y') ?: '—' }}</dd></div>
                @if($unionGroup->description)
                    <div class="border-t border-slate-100 pt-3 text-slate-600">{{ $unionGroup->description }}</div>
                @endif
            </dl>
        </x-card>

        <x-card title="Đoàn viên gần đây" class="lg:col-span-1" no-padding>
            @if($members->isEmpty())
                <div class="p-5"><x-empty-state title="Chưa có đoàn viên" /></div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($members as $member)
                        <li class="flex items-center justify-between px-5 py-3 text-sm">
                            <a href="{{ route('members.show', $member) }}" class="font-medium text-blue-600 hover:underline">{{ $member->full_name }}</a>
                            <x-status-badge :status="$member->status" />
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="border-t border-slate-100 p-3 text-center">
                <x-btn href="{{ route('members.index', ['union_group_id' => $unionGroup->id]) }}" variant="ghost">Xem tất cả đoàn viên →</x-btn>
            </div>
        </x-card>

        <x-card title="Hoạt động gần đây" class="lg:col-span-1" no-padding>
            @if($recentActivities->isEmpty())
                <div class="p-5"><x-empty-state title="Chưa có hoạt động" /></div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($recentActivities as $activity)
                        <li class="px-5 py-3 text-sm">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a>
                                <x-status-badge :status="$activity->status" />
                            </div>
                            <p class="text-xs text-slate-400">{{ $activity->start_time->format('d/m/Y') }} · {{ $activity->activityType?->name }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="border-t border-slate-100 p-3 text-center">
                <x-btn href="{{ route('activities.index', ['union_group_id' => $unionGroup->id]) }}" variant="ghost">Xem tất cả hoạt động →</x-btn>
            </div>
        </x-card>
    </div>
</x-app-layout>
