<x-app-layout title="Loại hoạt động" :breadcrumbs="['Loại hoạt động' => null]">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên loại hoạt động..."
                   class="w-64 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <x-btn type="submit" variant="secondary">Tìm kiếm</x-btn>
            @if(request('search'))
                <x-btn href="{{ route('activity-types.index') }}" variant="ghost">Đặt lại</x-btn>
            @endif
        </form>

        <x-btn x-data @click="$dispatch('open-modal', 'create-type')">+ Thêm loại hoạt động</x-btn>
    </div>

    <x-card no-padding>
        @if($activityTypes->isEmpty())
            <div class="p-5"><x-empty-state title="Chưa có loại hoạt động nào" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tên loại hoạt động</th>
                            <th class="px-5 py-3">Mô tả</th>
                            <th class="px-5 py-3 text-center">Số hoạt động</th>
                            <th class="px-5 py-3">Trạng thái</th>
                            <th class="px-5 py-3 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($activityTypes as $type)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium text-slate-700">{{ $type->name }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ \Illuminate\Support\Str::limit($type->description, 60) }}</td>
                                <td class="px-5 py-3 text-center">{{ $type->activities_count }}</td>
                                <td class="px-5 py-3">
                                    <span @class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', 'bg-green-100 text-green-700' => $type->is_active, 'bg-slate-100 text-slate-600' => ! $type->is_active])>
                                        {{ $type->is_active ? 'Đang sử dụng' : 'Đã vô hiệu hóa' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-1" x-data>
                                        <x-btn variant="ghost" @click="$dispatch('open-modal', 'edit-type-{{ $type->id }}')">Sửa</x-btn>
                                        <x-delete-form :action="route('activity-types.destroy', $type)" confirm="Xóa loại hoạt động này? Chỉ xóa được khi không còn hoạt động thuộc loại này." />
                                    </div>
                                </td>
                            </tr>

                            <x-modal name="edit-type-{{ $type->id }}" max-width="md">
                                <form method="POST" action="{{ route('activity-types.update', $type) }}" class="p-6">
                                    @csrf @method('PUT')
                                    <h3 class="mb-4 text-lg font-semibold text-slate-800">Chỉnh sửa loại hoạt động</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label value="Tên loại hoạt động *" />
                                            <x-text-input name="name" class="mt-1 block w-full" value="{{ $type->name }}" required />
                                        </div>
                                        <div>
                                            <x-input-label value="Mô tả" />
                                            <textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $type->description }}</textarea>
                                        </div>
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="is_active" value="1" @checked($type->is_active) class="rounded border-slate-300 text-blue-600">
                                            Kích hoạt loại hoạt động này
                                        </label>
                                    </div>
                                    <div class="mt-6 flex justify-end gap-2">
                                        <x-btn type="button" variant="secondary" @click="$dispatch('close')">Hủy</x-btn>
                                        <x-btn type="submit">Cập nhật</x-btn>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $activityTypes->links() }}</div>
        @endif
    </x-card>

    <x-modal name="create-type" max-width="md">
        <form method="POST" action="{{ route('activity-types.store') }}" class="p-6">
            @csrf
            <h3 class="mb-4 text-lg font-semibold text-slate-800">Thêm loại hoạt động</h3>
            <div class="space-y-4">
                <div>
                    <x-input-label value="Tên loại hoạt động *" />
                    <x-text-input name="name" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label value="Mô tả" />
                    <textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600">
                    Kích hoạt loại hoạt động này
                </label>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <x-btn type="button" variant="secondary" @click="$dispatch('close')">Hủy</x-btn>
                <x-btn type="submit">Lưu</x-btn>
            </div>
        </form>
    </x-modal>
</x-app-layout>
