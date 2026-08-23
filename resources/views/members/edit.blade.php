<x-app-layout title="Chỉnh sửa đoàn viên" :breadcrumbs="['Đoàn viên' => route('members.index'), 'Chỉnh sửa' => null]">
    <x-card :title="'Chỉnh sửa: '.$member->full_name">
        <form method="POST" action="{{ route('members.update', $member) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('members._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('members.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Cập nhật</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
