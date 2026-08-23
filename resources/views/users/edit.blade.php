<x-app-layout title="Chỉnh sửa tài khoản" :breadcrumbs="['Quản lý tài khoản' => route('users.index'), 'Chỉnh sửa' => null]">
    <x-card :title="'Chỉnh sửa: '.$user->name">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PUT')
            @include('users._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('users.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Cập nhật</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
