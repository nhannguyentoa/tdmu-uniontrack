<x-app-layout title="Tổng quan">
    <x-card class="mb-6">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @if($unionGroupOptions->count() > 1 || auth()->user()->isAdmin())
                <select name="union_group_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">-- Tất cả tổ công đoàn --</option>
                    @foreach($unionGroupOptions as $group)
                        <option value="{{ $group->id }}" @selected((string) $unionGroupId === (string) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="activity_type_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả loại hoạt động --</option>
                @foreach($activityTypes as $type)
                    <option value="{{ $type->id }}" @selected(request('activity_type_id') == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            <input type="number" name="year" value="{{ request('year') }}" placeholder="Năm (VD: {{ now()->year }})" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="semester" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Học kỳ --</option>
                <option value="1" @selected(request('semester') == 1)>Học kỳ 1</option>
                <option value="2" @selected(request('semester') == 2)>Học kỳ 2</option>
            </select>
            <select name="month" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tháng --</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected((int) request('month') === $m)>Tháng {{ $m }}</option>
                @endfor
            </select>
            <div class="flex gap-2">
                <x-btn type="submit">Lọc dữ liệu</x-btn>
                <x-btn href="{{ route('dashboard') }}" variant="ghost">Đặt lại</x-btn>
            </div>
        </form>
    </x-card>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Tổ công đoàn" :value="$kpis['total_union_groups']" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />' />
        <x-stat-card label="Đoàn viên đang hoạt động" :value="$kpis['total_members']" color="sky" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />' />
        <x-stat-card label="Tổng hoạt động" :value="$kpis['total_activities']" color="blue" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />' />
        <x-stat-card label="Tổng lượt tham gia" :value="$kpis['total_participants']" color="green" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />' />
        <x-stat-card label="Đã hoàn thành" :value="$kpis['completed_activities']" color="green" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        <x-stat-card label="Đang thực hiện" :value="$kpis['in_progress_activities']" color="amber" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />' />
        <x-stat-card label="Chưa bắt đầu" :value="$kpis['not_started_activities']" color="slate" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4m6 0a10 10 0 11-20 0 10 10 0 0120 0z" />' />
        <x-stat-card label="Đã hủy" :value="$kpis['cancelled_activities']" color="red" icon='<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />' />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Số lượng hoạt động theo tháng">
            <canvas id="chartByMonth" height="220"></canvas>
        </x-card>
        <x-card title="Số lượng người tham gia theo tháng">
            <canvas id="chartParticipants" height="220"></canvas>
        </x-card>
        <x-card title="Số lượng hoạt động theo tổ công đoàn">
            <canvas id="chartByGroup" height="220"></canvas>
        </x-card>
        <x-card title="Số lượng hoạt động theo loại">
            <canvas id="chartByType" height="220"></canvas>
        </x-card>
        <x-card title="Tỷ lệ trạng thái hoạt động" class="lg:col-span-2">
            <div class="mx-auto max-w-md"><canvas id="chartStatus" height="240"></canvas></div>
        </x-card>
    </div>

    <x-card title="Hoạt động gần đây" class="mt-6" no-padding>
        @if($recentActivities->isEmpty())
            <div class="p-5"><x-empty-state title="Chưa có hoạt động nào" /></div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($recentActivities as $activity)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a>
                            <p class="text-xs text-slate-400">{{ $activity->unionGroup?->name }} · {{ $activity->start_time->format('d/m/Y') }}</p>
                        </div>
                        <x-status-badge :status="$activity->status" />
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <script>
        const chartTextColor = '#64748b';
        Chart.defaults.color = chartTextColor;
        Chart.defaults.font.family = "'Figtree', sans-serif";

        new Chart(document.getElementById('chartByMonth'), {
            type: 'line',
            data: {
                labels: @json($activitiesByMonth['labels']),
                datasets: [{
                    label: 'Số hoạt động',
                    data: @json($activitiesByMonth['data']),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('chartParticipants'), {
            type: 'bar',
            data: {
                labels: @json($participantsByMonth['labels']),
                datasets: [{
                    label: 'Lượt tham gia',
                    data: @json($participantsByMonth['data']),
                    backgroundColor: '#0ea5e9',
                    borderRadius: 4,
                }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('chartByGroup'), {
            type: 'bar',
            data: {
                labels: @json($activitiesByGroup['labels']),
                datasets: [{
                    label: 'Số hoạt động',
                    data: @json($activitiesByGroup['data']),
                    backgroundColor: '#6366f1',
                    borderRadius: 4,
                }]
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }
        });

        new Chart(document.getElementById('chartByType'), {
            type: 'doughnut',
            data: {
                labels: @json($activitiesByType['labels']),
                datasets: [{
                    data: @json($activitiesByType['data']),
                    backgroundColor: ['#2563eb', '#0ea5e9', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#64748b'],
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('chartStatus'), {
            type: 'pie',
            data: {
                labels: @json($statusDistribution['labels']),
                datasets: [{
                    data: @json($statusDistribution['data']),
                    backgroundColor: ['#94a3b8', '#38bdf8', '#f59e0b', '#22c55e', '#ef4444'],
                }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</x-app-layout>
