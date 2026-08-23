@props(['title' => null, 'subtitle' => null, 'noPadding' => false])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if($title)
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-xs text-slate-400">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="{{ $noPadding ? '' : 'p-5' }}">
        {{ $slot }}
    </div>
</div>
