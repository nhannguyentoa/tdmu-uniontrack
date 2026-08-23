<x-app-layout title="Danh sách tổ công đoàn" :breadcrumbs="['Tổ công đoàn' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã hoặc tên tổ..."
                   class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngưng hoạt động</option>
            </select>
            <x-btn type="submit" variant="secondary">Tìm kiếm</x-btn>
            @if(request()->anyFilled(['search', 'status']))
                <x-btn href="{{ route('union-groups.index') }}" variant="ghost">Đặt lại bộ lọc</x-btn>
            @endif
        </form>

        @can('create', \App\Models\UnionGroup::class)
            <x-btn href="{{ route('union-groups.create') }}">+ Thêm tổ công đoàn</x-btn>
        @endcan
    </div>

    <x-card no-padding>
        @if($unionGroups->isEmpty())
            <div class="p-5">
                <x-empty-state title="Chưa có tổ công đoàn nào" description="Hãy thêm tổ công đoàn đầu tiên để bắt đầu quản lý." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Mã tổ</th>
                            <th class="px-5 py-3">Tên tổ công đoàn</th>
                            <th class="px-5 py-3">Người phụ trách</th>
                            <th class="px-5 py-3 text-center">Đoàn viên</th>
                            <th class="px-5 py-3 text-center">Hoạt động</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($unionGroups as $group)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $group->code }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('union-groups.show', $group) }}" class="font-medium text-blue-600 hover:underline">{{ $group->name }}</a>
                                    <p class="text-xs text-slate-400">{{ $group->department }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $group->leader_name ?: '—' }}</td>
                                <td class="px-5 py-3 text-center">{{ $group->members_count }}</td>
                                <td class="px-5 py-3 text-center">{{ $group->activities_count }}</td>
                                <td class="px-5 py-3"><x-status-badge :status="$group->status" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-btn href="{{ route('union-groups.show', $group) }}" variant="ghost">Xem</x-btn>
                                        @can('update', $group)
                                            <x-btn href="{{ route('union-groups.edit', $group) }}" variant="ghost">Sửa</x-btn>
                                        @endcan
                                        @can('delete', $group)
                                            <x-delete-form :action="route('union-groups.destroy', $group)" confirm="Xóa tổ công đoàn này? Chỉ có thể xóa khi không còn đoàn viên/hoạt động liên quan." />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $unionGroups->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
