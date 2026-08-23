@props(['label', 'value', 'color' => 'blue', 'icon' => null])

@php
    $colors = [
        'blue' => 'bg-blue-50 text-blue-600',
        'green' => 'bg-green-50 text-green-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'slate' => 'bg-slate-100 text-slate-600',
        'sky' => 'bg-sky-50 text-sky-600',
    ];
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-4">
        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg {{ $colors[$color] ?? $colors['blue'] }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $icon !!}</svg>
        </div>
        <div>
            <p class="text-xs font-medium text-slate-400">{{ $label }}</p>
            <p class="text-2xl font-bold text-slate-800">{{ $value }}</p>
        </div>
    </div>
</div>
