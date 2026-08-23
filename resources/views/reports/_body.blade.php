<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <x-stat-card label="Tổng số hoạt động" :value="$report['total_activities']" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />' />
    <x-stat-card label="Đã hoàn thành" :value="$report['completed_activities']" color="green" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' />
    <x-stat-card label="Tỷ lệ hoàn thành" value="{{ $report['completion_rate'] }}%" color="amber" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H11V3.512A9.025 9.025 0 0120.488 9z" />' />
    <x-stat-card label="Tổng lượt tham gia" :value="$report['total_participants']" color="sky" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />' />
</div>

<div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <x-card title="Số hoạt động theo tổ công đoàn">
        @if($report['by_group']->isEmpty())
            <x-empty-state title="Không có dữ liệu" />
        @else
            <ul class="space-y-2 text-sm">
                @foreach($report['by_group'] as $name => $count)
                    <li class="flex items-center justify-between border-b border-slate-50 pb-2">
                        <span class="text-slate-600">{{ $name }}</span>
                        <span class="font-semibold text-slate-800">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card title="Số hoạt động theo loại">
        @if($report['by_type']->isEmpty())
            <x-empty-state title="Không có dữ liệu" />
        @else
            <ul class="space-y-2 text-sm">
                @foreach($report['by_type'] as $name => $count)
                    <li class="flex items-center justify-between border-b border-slate-50 pb-2">
                        <span class="text-slate-600">{{ $name }}</span>
                        <span class="font-semibold text-slate-800">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</div>

@if(isset($report['group_comparison']) && $report['group_comparison']->isNotEmpty())
    <x-card title="So sánh giữa các tổ công đoàn" class="mb-6" no-padding>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tổ công đoàn</th>
                        <th class="px-5 py-3 text-center">Tổng hoạt động</th>
                        <th class="px-5 py-3 text-center">Đã hoàn thành</th>
                        <th class="px-5 py-3 text-center">Tỷ lệ hoàn thành</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($report['group_comparison'] as $row)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $row->name }}</td>
                            <td class="px-5 py-3 text-center">{{ $row->total_activities }}</td>
                            <td class="px-5 py-3 text-center">{{ $row->completed_activities }}</td>
                            <td class="px-5 py-3 text-center">{{ $row->total_activities > 0 ? round($row->completed_activities / $row->total_activities * 100) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif

<x-card title="Danh sách hoạt động" no-padding>
    @if($report['activities']->isEmpty())
        <div class="p-5"><x-empty-state title="Không có hoạt động nào trong kỳ báo cáo" /></div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Mã HĐ</th>
                        <th class="px-5 py-3">Tên hoạt động</th>
                        <th class="px-5 py-3">Tổ công đoàn</th>
                        <th class="px-5 py-3">Thời gian</th>
                        <th class="px-5 py-3">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($report['activities'] as $activity)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700">{{ $activity->code }}</td>
                            <td class="px-5 py-3"><a href="{{ route('activities.show', $activity) }}" class="text-blue-600 hover:underline">{{ $activity->name }}</a></td>
                            <td class="px-5 py-3 text-slate-500">{{ $activity->unionGroup?->name }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $activity->start_time->format('d/m/Y') }}</td>
                            <td class="px-5 py-3"><x-status-badge :status="$activity->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>
