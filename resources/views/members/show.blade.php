<x-app-layout :title="$member->full_name" :breadcrumbs="['Đoàn viên' => route('members.index'), $member->full_name => null]">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-1">
            <div class="flex flex-col items-center text-center">
                @if($member->avatar_path)
                    <img src="{{ Storage::url($member->avatar_path) }}" class="h-24 w-24 rounded-full object-cover" alt="{{ $member->full_name }}">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-blue-50 text-2xl font-bold text-blue-600">
                        {{ mb_strtoupper(mb_substr($member->full_name, 0, 1)) }}
                    </div>
                @endif
                <h2 class="mt-3 text-lg font-semibold text-slate-800">{{ $member->full_name }}</h2>
                <p class="text-sm text-slate-400">{{ $member->code }}</p>
                <div class="mt-2"><x-status-badge :status="$member->status" /></div>
            </div>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Tổ công đoàn</dt><dd class="font-medium text-slate-700"><a href="{{ route('union-groups.show', $member->unionGroup) }}" class="text-blue-600 hover:underline">{{ $member->unionGroup?->name }}</a></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Chức vụ</dt><dd class="font-medium text-slate-700">{{ $member->position ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Đơn vị</dt><dd class="font-medium text-slate-700">{{ $member->department ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Ngày sinh</dt><dd class="font-medium text-slate-700">{{ $member->dob?->format('d/m/Y') ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Giới tính</dt><dd class="font-medium text-slate-700">{{ ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'][$member->gender] ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Điện thoại</dt><dd class="font-medium text-slate-700">{{ $member->phone ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd class="font-medium text-slate-700">{{ $member->email ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Ngày tham gia CĐ</dt><dd class="font-medium text-slate-700">{{ $member->joined_union_date?->format('d/m/Y') ?: '—' }}</dd></div>
                @if($member->note)
                    <div class="border-t border-slate-100 pt-3 text-slate-600">{{ $member->note }}</div>
                @endif
            </dl>

            <div class="mt-6 flex gap-2">
                @can('update', $member)
                    <x-btn href="{{ route('members.edit', $member) }}" variant="secondary" class="flex-1 justify-center">Chỉnh sửa</x-btn>
                @endcan
            </div>
        </x-card>

        <x-card title="Hoạt động đã tham gia" class="lg:col-span-2" no-padding>
            @if($activities->isEmpty())
                <div class="p-5"><x-empty-state title="Chưa tham gia hoạt động nào" /></div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($activities as $activity)
                        <li class="px-5 py-3">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a>
                                <x-status-badge :status="$activity->status" />
                            </div>
                            <p class="text-xs text-slate-400">{{ $activity->activityType?->name }} · {{ $activity->start_time->format('d/m/Y') }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-100 px-5 py-4">{{ $activities->links() }}</div>
            @endif
        </x-card>
    </div>
</x-app-layout>
