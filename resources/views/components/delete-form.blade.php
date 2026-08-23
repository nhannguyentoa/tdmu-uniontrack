@props(['action', 'confirm' => 'Bạn có chắc chắn muốn xóa mục này không? Hành động này không thể hoàn tác.'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm('{{ $confirm }}');" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50']) }}>
        {{ $slot->isEmpty() ? 'Xóa' : $slot }}
    </button>
</form>
