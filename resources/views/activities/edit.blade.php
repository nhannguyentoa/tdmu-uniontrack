<x-app-layout title="Chỉnh sửa hoạt động" :breadcrumbs="['Hoạt động' => route('activities.index'), $activity->name => route('activities.show', $activity), 'Chỉnh sửa' => null]">
    <x-card :title="'Chỉnh sửa: '.$activity->name">
        <form method="POST" action="{{ route('activities.update', $activity) }}">
            @csrf
            @method('PUT')
            @include('activities._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activities.show', $activity) }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Cập nhật</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
