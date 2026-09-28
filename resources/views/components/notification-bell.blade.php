@php
    $groups = app(\App\Services\NotificationService::class)->groups(auth()->user());
    $total = \App\Services\NotificationService::total($groups);
    $dot = ['danger' => 'bg-red-500', 'warning' => 'bg-amber-500', 'info' => 'bg-sky-500'];
    $hasDanger = collect($groups)->contains('level', 'danger');
@endphp

<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false" title="Thông báo"
            class="relative flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
        @if($total > 0)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[10px] font-bold text-white {{ $hasDanger ? 'bg-red-600' : 'bg-amber-500' }}">{{ $total > 99 ? '99+' : $total }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-transition class="absolute right-0 z-30 mt-2 w-[22rem] max-w-[92vw] rounded-xl border border-slate-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="text-sm font-semibold text-slate-800">Thông báo</p>
            <span class="text-xs text-slate-400">{{ $total }} việc cần chú ý</span>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($groups as $group)
                <div class="border-b border-slate-100 px-4 py-3 last:border-b-0">
                    <a href="{{ route('notifications.index', ['type' => $group['type']]) }}" class="mb-2 flex items-center justify-between">
                        <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <span class="h-2 w-2 rounded-full {{ $dot[$group['level']] }}"></span>{{ $group['label'] }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $group['count'] }}</span>
                    </a>
                    <ul class="space-y-2">
                        @foreach(array_slice($group['items'], 0, 3) as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="block rounded-lg px-2 py-1.5 hover:bg-slate-50">
                                    <p class="truncate text-sm font-medium text-slate-700">{{ $item['title'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $item['message'] }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    @if($group['count'] > 3)
                        <a href="{{ route('notifications.index', ['type' => $group['type']]) }}" class="mt-1 block px-2 text-xs font-medium text-blue-600 hover:underline">và {{ $group['count'] - 3 }} mục khác</a>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-400">Không có việc nào cần chú ý.</div>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-sm font-medium text-blue-600 hover:bg-slate-50">Xem tất cả thông báo</a>
    </div>
</div>
