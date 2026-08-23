<x-app-layout title="Thêm hoạt động" :breadcrumbs="['Hoạt động' => route('activities.index'), 'Thêm mới' => null]">
    <x-card title="Thêm hoạt động công đoàn">
        <form method="POST" action="{{ route('activities.store') }}">
            @csrf
            @include('activities._form', ['activity' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activities.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Lưu hoạt động</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
