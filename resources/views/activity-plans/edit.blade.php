<x-app-layout title="Chỉnh sửa kế hoạch hoạt động" :breadcrumbs="['Kế hoạch hoạt động' => route('activity-plans.index'), 'Chỉnh sửa' => null]">
    <x-card title="Chỉnh sửa kế hoạch hoạt động">
        <form method="POST" action="{{ route('activity-plans.update', $activityPlan) }}">
            @csrf
            @method('PUT')
            @include('activity-plans._form')

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activity-plans.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Cập nhật</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
