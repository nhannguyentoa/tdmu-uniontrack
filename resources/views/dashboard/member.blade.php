<x-app-layout title="Tổng quan">
    @if(! $member)
        <x-empty-state title="Tài khoản của bạn chưa được gắn với hồ sơ đoàn viên"
                        description="Vui lòng liên hệ cán bộ công đoàn hoặc quản trị viên để được hỗ trợ liên kết tài khoản." />
    @else
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <x-card class="lg:col-span-1">
                <div class="flex flex-col items-center text-center">
                    @if($member->avatar_path)
                        <img src="{{ Storage::url($member->avatar_path) }}" class="h-20 w-20 rounded-full object-cover" alt="{{ $member->full_name }}">
                    @else
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-2xl font-bold text-blue-600">
                            {{ mb_strtoupper(mb_substr($member->full_name, 0, 1)) }}
                        </div>
                    @endif
                    <h2 class="mt-3 font-semibold text-slate-800">{{ $member->full_name }}</h2>
                    <p class="text-sm text-slate-400">{{ $member->code }} · {{ $member->unionGroup?->name }}</p>
                    <x-btn href="{{ route('members.show', $member) }}" variant="secondary" class="mt-4 w-full justify-center">Xem hồ sơ đầy đủ</x-btn>
                </div>
            </x-card>

            <x-card title="Hoạt động tôi đã tham gia" class="lg:col-span-2" no-padding>
                @if($myActivities->isEmpty())
                    <div class="p-5"><x-empty-state title="Bạn chưa tham gia hoạt động nào" /></div>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach($myActivities as $activity)
                            <li class="px-5 py-3">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a>
                                    <x-status-badge :status="$activity->status" />
                                </div>
                                <p class="text-xs text-slate-400">{{ $activity->activityType?->name }} · {{ $activity->start_time->format('d/m/Y') }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    @endif

    <x-card title="Hoạt động sắp diễn ra" no-padding>
        @if($upcomingActivities->isEmpty())
            <div class="p-5"><x-empty-state title="Chưa có hoạt động sắp diễn ra" /></div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($upcomingActivities as $activity)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <a href="{{ route('activities.show', $activity) }}" class="font-medium text-blue-600 hover:underline">{{ $activity->name }}</a>
                            <p class="text-xs text-slate-400">{{ $activity->unionGroup?->name }} · {{ $activity->location }}</p>
                        </div>
                        <span class="text-xs font-medium text-slate-500">{{ $activity->start_time->format('d/m/Y H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>
