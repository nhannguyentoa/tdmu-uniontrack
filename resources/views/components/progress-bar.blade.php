@props(['value' => 0])

@php
    $value = max(0, min(100, (int) $value));
    $color = match (true) {
        $value >= 100 => 'bg-green-500',
        $value >= 50 => 'bg-amber-500',
        $value > 0 => 'bg-sky-500',
        default => 'bg-slate-300',
    };
@endphp

<div class="flex items-center gap-2">
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
        <div class="h-2 rounded-full {{ $color }}" style="width: {{ $value }}%"></div>
    </div>
    <span class="w-10 flex-shrink-0 text-right text-xs font-medium text-slate-500">{{ $value }}%</span>
</div>
