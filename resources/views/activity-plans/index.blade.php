<x-app-layout title="Kế hoạch hoạt động" :breadcrumbs="['Kế hoạch hoạt động' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @foreach($academicYears as $year)
                    <option value="{{ $year }}" @selected($year === $academicYear)>Năm học {{ $year }}</option>
                @endforeach
            </select>
        </form>

        @can('create', \App\Models\ActivityPlan::class)
            <x-btn href="{{ route('activity-plans.create') }}">+ Thêm kế hoạch</x-btn>
        @endcan
    </div>

    @if($plans->isEmpty())
        <x-card><x-empty-state title="Chưa có kế hoạch hoạt động cho năm học này" /></x-card>
    @else
        <div class="space-y-6">
            @foreach($plans as $month => $items)
                <x-card no-padding>
                    <x-slot name="title">Tháng {{ $month }}</x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3">Nội dung</th>
                                    <th class="px-5 py-3">Đơn vị đăng cai</th>
                                    <th class="px-5 py-3">Ban phụ trách</th>
                                    <th class="px-5 py-3">Ghi chú</th>
                                    <th class="px-5 py-3">Trạng thái</th>
                                    <th class="px-5 py-3 text-right">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($items as $plan)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 font-medium text-slate-700">
                                            {{ $plan->title }}
                                            @if($plan->activityType)
                                                <div class="mt-0.5 text-xs font-normal text-slate-400">{{ $plan->activityType->name }}</div>
                                            @endif
                                            @if($plan->counts_for_evaluation)
                                                <div class="mt-0.5 text-xs font-normal text-blue-600">Tính điểm thi đua (tối đa {{ rtrim(rtrim($plan->evaluation_max_score, '0'), '.') }})</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-slate-500">{{ $plan->hostUnionGroup?->name ?: '—' }}</td>
                                        <td class="px-5 py-3 text-slate-500">{{ $plan->department?->name ?: '—' }}</td>
                                        <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($plan->note, 50) ?: '—' }}</td>
                                        <td class="px-5 py-3">
                                            <x-status-badge :status="$plan->displayStatusKey()" />
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex justify-end gap-1">
                                                @if($plan->activity)
                                                    <x-btn href="{{ route('activities.show', $plan->activity) }}" variant="ghost">Xem hoạt động</x-btn>
                                                @elseif(! $plan->isCancelled() && auth()->user()->can('update', $plan))
                                                    <x-btn href="{{ route('activities.create', ['from_activity_plan_id' => $plan->id]) }}" variant="ghost">Chuyển thành hoạt động</x-btn>
                                                @endif
                                                @can('update', $plan)
                                                    <x-btn href="{{ route('activity-plans.edit', $plan) }}" variant="ghost">Sửa</x-btn>
                                                @endcan
                                                @can('delete', $plan)
                                                    <x-delete-form :action="route('activity-plans.destroy', $plan)" confirm="Xóa kế hoạch hoạt động này?" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</x-app-layout>
