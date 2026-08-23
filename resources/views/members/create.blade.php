<x-app-layout title="Thêm đoàn viên" :breadcrumbs="['Đoàn viên' => route('members.index'), 'Thêm mới' => null]">
    <x-card title="Thêm đoàn viên">
        <form method="POST" action="{{ route('members.store') }}" enctype="multipart/form-data">
            @csrf
            @include('members._form', ['member' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('members.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Lưu đoàn viên</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
