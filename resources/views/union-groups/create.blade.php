<x-app-layout title="Thêm tổ công đoàn" :breadcrumbs="['Danh sách tổ' => route('union-groups.index'), 'Thêm tổ' => null]">
    <x-card title="Thêm tổ công đoàn">
        <form method="POST" action="{{ route('union-groups.store') }}">
            @csrf
            @include('union-groups._form', ['unionGroup' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('union-groups.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Lưu tổ công đoàn</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
