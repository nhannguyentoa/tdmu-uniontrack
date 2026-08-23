<x-app-layout title="Danh sách đoàn viên" :breadcrumbs="['Đoàn viên' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc mã đoàn viên..."
                   class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="union_group_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả tổ --</option>
                @foreach($unionGroups as $group)
                    <option value="{{ $group->id }}" @selected(request('union_group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngưng hoạt động</option>
                <option value="transferred" @selected(request('status') === 'transferred')>Đã chuyển</option>
            </select>
            <x-btn type="submit" variant="secondary">Tìm kiếm</x-btn>
            @if(request()->anyFilled(['search', 'union_group_id', 'status']))
                <x-btn href="{{ route('members.index') }}" variant="ghost">Đặt lại bộ lọc</x-btn>
            @endif
        </form>

        <div class="flex gap-2">
            <x-btn href="{{ route('members.export', request()->query()) }}" variant="secondary">Xuất Excel</x-btn>
            @can('create', \App\Models\Member::class)
                <x-btn href="{{ route('members.create') }}">+ Thêm đoàn viên</x-btn>
            @endcan
        </div>
    </div>

    <x-card no-padding>
        @if($members->isEmpty())
            <div class="p-5"><x-empty-state title="Không tìm thấy đoàn viên" description="Thử thay đổi bộ lọc hoặc thêm đoàn viên mới." /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Mã ĐV</th>
                            <th class="px-5 py-3">Họ và tên</th>
                            <th class="px-5 py-3">Tổ công đoàn</th>
                            <th class="px-5 py-3">Liên hệ</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($members as $member)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $member->code }}</td>
                                <td class="px-5 py-3">
                                    <a href="{{ route('members.show', $member) }}" class="font-medium text-blue-600 hover:underline">{{ $member->full_name }}</a>
                                    <p class="text-xs text-slate-400">{{ $member->position }}</p>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $member->unionGroup?->name }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    <p>{{ $member->phone ?: '—' }}</p>
                                    <p class="text-xs text-slate-400">{{ $member->email }}</p>
                                </td>
                                <td class="px-5 py-3"><x-status-badge :status="$member->status" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-btn href="{{ route('members.show', $member) }}" variant="ghost">Xem</x-btn>
                                        @can('update', $member)
                                            <x-btn href="{{ route('members.edit', $member) }}" variant="ghost">Sửa</x-btn>
                                        @endcan
                                        @can('delete', $member)
                                            <x-delete-form :action="route('members.destroy', $member)" confirm="Xóa đoàn viên này khỏi hệ thống?" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $members->links() }}
            </div>
        @endif
    </x-card>
</x-app-layout>
