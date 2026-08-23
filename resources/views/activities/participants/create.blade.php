<x-app-layout title="Thêm người tham gia" :breadcrumbs="['Hoạt động' => route('activities.index'), $activity->name => route('activities.show', $activity), 'Thêm người tham gia' => null]">
    <x-card :title="'Thêm người tham gia cho: '.$activity->name">
        <form method="GET" class="mb-5 flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc mã đoàn viên..."
                   class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <select name="union_group_id" class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">-- Tất cả tổ --</option>
                @foreach(\App\Models\UnionGroup::orderBy('name')->get() as $group)
                    <option value="{{ $group->id }}" @selected(request('union_group_id') == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            <x-btn type="submit" variant="secondary">Tìm kiếm</x-btn>
        </form>

        <form method="POST" action="{{ route('activities.participants.store', $activity) }}">
            @csrf

            @if($members->isEmpty())
                <x-empty-state title="Không còn đoàn viên phù hợp" description="Tất cả đoàn viên phù hợp đã được thêm hoặc không có kết quả tìm kiếm." />
            @else
                <div class="mb-4 max-h-96 overflow-y-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-2 w-10"></th>
                                <th class="px-4 py-2">Đoàn viên</th>
                                <th class="px-4 py-2">Tổ công đoàn</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($members as $member)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2"><input type="checkbox" name="member_ids[]" value="{{ $member->id }}" class="rounded border-slate-300 text-blue-600"></td>
                                    <td class="px-4 py-2">
                                        <p class="font-medium text-slate-700">{{ $member->full_name }}</p>
                                        <p class="text-xs text-slate-400">{{ $member->code }}</p>
                                    </td>
                                    <td class="px-4 py-2 text-slate-500">{{ $member->unionGroup?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <x-input-error :messages="$errors->get('member_ids')" class="mb-3" />
                <div class="mb-4">{{ $members->links() }}</div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label value="Trạng thái tham gia *" />
                        <select name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="registered">Đã đăng ký</option>
                            <option value="attended">Đã tham gia</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label value="Vai trò/Ghi chú" />
                        <x-text-input name="role_note" class="mt-1 block w-full" placeholder="VD: Thành viên ban tổ chức" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-btn href="{{ route('activities.show', $activity) }}" variant="secondary">Hủy</x-btn>
                    <x-btn type="submit">Thêm vào hoạt động</x-btn>
                </div>
            @endif
        </form>
    </x-card>
</x-app-layout>
