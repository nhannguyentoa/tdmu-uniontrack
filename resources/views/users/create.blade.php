<x-app-layout title="Thêm tài khoản" :breadcrumbs="['Quản lý tài khoản' => route('users.index'), 'Thêm mới' => null]">
    <x-card title="Thêm tài khoản người dùng">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users._form', ['user' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('users.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Tạo tài khoản</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
