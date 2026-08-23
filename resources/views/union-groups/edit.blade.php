<x-app-layout title="Chỉnh sửa tổ công đoàn" :breadcrumbs="['Danh sách tổ' => route('union-groups.index'), 'Chỉnh sửa' => null]">
    <x-card :title="'Chỉnh sửa: '.$unionGroup->name">
        <form method="POST" action="{{ route('union-groups.update', $unionGroup) }}">
            @csrf
            @method('PUT')
            @include('union-groups._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('union-groups.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Cập nhật</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
