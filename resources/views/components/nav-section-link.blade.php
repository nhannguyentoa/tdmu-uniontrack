@props(['href', 'active' => false])

<a href="{{ $href }}"
   @class([
       'flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition',
       'bg-blue-600 text-white shadow-sm' => $active,
       'text-slate-300 hover:bg-slate-800 hover:text-white' => ! $active,
   ])
>
    @isset($icon)
        <svg class="h-4.5 w-4.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            {{ $icon }}
        </svg>
    @endisset
    <span>{{ $slot }}</span>
</a>
