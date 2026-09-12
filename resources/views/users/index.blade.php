<x-app-layout title="Quản lý tài khoản" :breadcrumbs="['Quản lý tài khoản' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc email..."
                   class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="role" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả vai trò --</option>
                <option value="admin" @selected(request('role') === 'admin')>Quản trị viên</option>
                <option value="officer" @selected(request('role') === 'officer')>Cán bộ công đoàn</option>
            </select>
            <x-btn type="submit" variant="secondary">Tìm kiếm</x-btn>
            @if(request()->anyFilled(['search', 'role']))
                <x-btn href="{{ route('users.index') }}" variant="ghost">Đặt lại bộ lọc</x-btn>
            @endif
        </form>
        <x-btn href="{{ route('users.create') }}">+ Thêm tài khoản</x-btn>
    </div>

    <x-card no-padding>
        @if($users->isEmpty())
            <div class="p-5"><x-empty-state title="Không tìm thấy tài khoản" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Họ và tên</th>
                            <th class="px-5 py-3">Email</th>
                            <th class="px-5 py-3">Vai trò</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $u)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $u->name }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ $u->email }}</td>
                                <td class="px-5 py-3 text-slate-600">
                                    @switch($u->role)
                                        @case('admin') Quản trị viên @break
                                        @default Cán bộ công đoàn
                                    @endswitch
                                </td>
                                <td class="px-5 py-3">
                                    <span @class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', 'bg-green-100 text-green-700' => $u->is_active, 'bg-slate-100 text-slate-600' => ! $u->is_active])>
                                        {{ $u->is_active ? 'Đang hoạt động' : 'Vô hiệu hóa' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-btn href="{{ route('users.edit', $u) }}" variant="ghost">Sửa</x-btn>
                                        @if($u->id !== auth()->id())
                                            <x-delete-form :action="route('users.destroy', $u)" confirm="Xóa tài khoản này?" />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
