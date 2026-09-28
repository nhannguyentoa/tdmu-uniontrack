@props([
    'url',
    'mode' => 'suggest',
    'initial' => null,
    'applyTarget' => null,
    'column' => null,
])

@php
    $isPrecheck = $mode === 'precheck';
@endphp

<div {{ $attributes->class(['text-xs']) }}
     @if($column) data-ai-cell="{{ $column }}" @endif
     x-data="{
         loading: false,
         error: null,
         open: false,
         s: @js($initial),
         async run() {
             if (this.loading) return;
             this.loading = true;
             this.error = null;
             try {
                 const response = await fetch(@js($url), {
                     method: 'POST',
                     headers: {
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json',
                     },
                 });
                 const data = await response.json().catch(() => ({}));
                 if (!response.ok) {
                     this.error = data.message || 'Không gọi được trợ lý AI (mã lỗi ' + response.status + ').';
                 } else {
                     this.s = data.suggestion;
                     this.open = false;
                 }
             } catch (e) {
                 this.error = 'Mất kết nối tới máy chủ. Vui lòng thử lại.';
             }
             this.loading = false;
         },
         apply() {
             const el = document.getElementById(@js($applyTarget));
             if (el && this.s && this.s.score !== undefined) {
                 el.value = this.s.score;
                 el.dispatchEvent(new Event('input', { bubbles: true }));
             }
         },
         badge() {
             return {
                 sufficient: 'bg-green-100 text-green-700',
                 partial: 'bg-amber-100 text-amber-700',
                 insufficient: 'bg-orange-100 text-orange-700',
                 no_data: 'bg-slate-100 text-slate-600',
             }[this.s?.status] || 'bg-slate-100 text-slate-600';
         },
     }">
    <div class="flex flex-wrap items-center gap-1.5">
        <button type="button" @click="run()" :disabled="loading"
                class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 font-medium text-indigo-700 hover:bg-indigo-100 disabled:opacity-60">
            <svg x-show="loading" x-cloak class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            <span x-text="loading ? 'Đang phân tích...' : (s ? 'Chạy lại' : '{{ $isPrecheck ? 'AI kiểm tra trước' : 'AI gợi ý' }}')"></span>
        </button>

        <template x-if="s">
            <span class="inline-flex items-center gap-1.5">
                <span class="rounded-full px-2 py-0.5 font-medium" :class="badge()" x-text="s.status_label"></span>
                @unless($isPrecheck)
                    <span x-show="s.score !== undefined" class="font-semibold text-slate-700">Gợi ý: <span x-text="s.score"></span></span>
                    @if($applyTarget)
                        <button type="button" @click="apply()" class="rounded-md border border-slate-300 bg-white px-2 py-0.5 text-slate-700 hover:bg-slate-50">Áp dụng</button>
                    @endif
                @endunless
                <button type="button" @click="open = !open" class="text-slate-500 underline" x-text="open ? 'Ẩn' : 'Chi tiết'"></button>
            </span>
        </template>
    </div>

    <p x-show="error" x-cloak x-text="error" class="mt-1 text-red-600"></p>

    <div x-show="open && s" x-cloak class="mt-2 space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-slate-600">
        <p x-text="s?.reasoning"></p>
        <template x-if="s && s.matched && s.matched.length">
            <p><span class="font-medium text-slate-700">Hoạt động được xét:</span>
                <template x-for="a in s.matched" :key="a.id">
                    <a :href="'{{ url('activities') }}/' + a.id" target="_blank" class="ml-1 text-blue-600 hover:underline" x-text="a.code + ' — ' + a.name"></a>
                </template>
            </p>
        </template>
        <template x-if="s && s.missing && s.missing.length">
            <div><span class="font-medium text-slate-700">Còn thiếu:</span>
                <ul class="ml-4 list-disc"><template x-for="m in s.missing" :key="m"><li x-text="m"></li></template></ul>
            </div>
        </template>
        <p class="text-[11px] text-slate-400">
            Gợi ý của <span x-text="s?.provider + ' / ' + s?.model"></span> lúc <span x-text="s?.generated_at"></span>.
            Chỉ mang tính tham khảo{{ $isPrecheck ? '' : '; người thẩm định quyết định điểm cuối cùng' }}.
        </p>
    </div>
</div>
