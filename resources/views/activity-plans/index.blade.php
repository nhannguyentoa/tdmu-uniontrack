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
                                        <td class="px-5 py-3 font-medium text-slate-700">{{ $plan->title }}</td>
                                        <td class="px-5 py-3 text-slate-500">{{ $plan->hostUnionGroup?->name ?: '—' }}</td>
                                        <td class="px-5 py-3 text-slate-500">{{ $plan->department?->name ?: '—' }}</td>
                                        <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($plan->note, 50) ?: '—' }}</td>
                                        <td class="px-5 py-3">
                                            <span @class([
                                                'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                                'bg-green-100 text-green-700' => $plan->status === 'done',
                                                'bg-yellow-100 text-yellow-700' => $plan->status === 'in_progress',
                                                'bg-slate-100 text-slate-600' => $plan->status === 'planned',
                                                'bg-red-100 text-red-700' => $plan->status === 'cancelled',
                                            ])>{{ $plan->statusLabel() }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex justify-end gap-1">
                                                @if($plan->activity)
                                                    <x-btn href="{{ route('activities.show', $plan->activity) }}" variant="ghost">Xem hoạt động</x-btn>
                                                @elseif(auth()->user()->can('update', $plan))
                                                    <x-btn href="{{ route('activities.create', ['from_activity_plan_id' => $plan->id, 'name' => $plan->title, 'union_group_id' => $plan->host_union_group_id]) }}" variant="ghost">Chuyển thành hoạt động</x-btn>
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
