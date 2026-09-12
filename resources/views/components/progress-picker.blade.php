@props(['name' => 'progress', 'value' => 0])

@php
    $milestones = [0, 25, 50, 75, 100];
@endphp

<div x-data="{ value: {{ (int) $value }} }">
    <input type="hidden" name="{{ $name }}" :value="value" {{ $attributes }}>
    <div class="flex items-center gap-1.5">
        @foreach($milestones as $m)
            <button type="button"
                    @click="value = {{ $m }}"
                    :class="value === {{ $m }} ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-500 border-slate-300 hover:border-blue-400'"
                    class="flex-1 rounded-lg border px-2 py-1.5 text-xs font-medium transition">
                {{ $m }}%
            </button>
        @endforeach
    </div>
</div>
