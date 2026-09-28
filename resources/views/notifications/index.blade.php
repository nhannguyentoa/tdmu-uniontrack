@php
    $dot = ['danger' => 'bg-red-500', 'warning' => 'bg-amber-500', 'info' => 'bg-sky-500'];
@endphp

<x-app-layout title="Thông báo" :breadcrumbs="['Thông báo' => null]">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('notifications.index') }}" class="rounded-full border px-3 py-1 text-sm {{ $type === null ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Tất cả ({{ $total }})</a>
        @foreach($groups as $group)
            <a href="{{ route('notifications.index', ['type' => $group['type']]) }}" class="rounded-full border px-3 py-1 text-sm {{ $type === $group['type'] ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">{{ $group['label'] }} ({{ $group['count'] }})</a>
        @endforeach
    </div>

    <p class="mb-4 text-xs text-slate-400">Thông báo được tính trực tiếp từ dữ liệu hiện tại: việc nào xử lý xong sẽ tự biến mất.
        {{ auth()->user()->isAdmin() ? 'Bạn xem được toàn trường.' : 'Bạn chỉ thấy các tổ mình phụ trách.' }}</p>

    @forelse($shown as $group)
        <x-card class="mb-5" no-padding>
            <x-slot name="title">
                <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $dot[$group['level']] }}"></span>{{ $group['label'] }} ({{ $group['count'] }})</span>
            </x-slot>
            <ul class="divide-y divide-slate-100">
                @foreach($group['items'] as $item)
                    <li>
                        <a href="{{ $item['url'] }}" class="block px-5 py-3 hover:bg-slate-50">
                            <p class="text-sm font-medium text-slate-700">{{ $item['title'] }}</p>
                            <p class="text-xs text-slate-500">{{ $item['message'] }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @empty
        <x-card><x-empty-state title="Không có thông báo" description="Hiện không có hoạt động hay công việc nào cần chú ý." /></x-card>
    @endforelse
</x-app-layout>
