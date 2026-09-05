<x-app-layout title="Thêm kế hoạch hoạt động" :breadcrumbs="['Kế hoạch hoạt động' => route('activity-plans.index'), 'Thêm mới' => null]">
    <x-card title="Thêm kế hoạch hoạt động">
        <form method="POST" action="{{ route('activity-plans.store') }}">
            @csrf
            @include('activity-plans._form', ['activityPlan' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activity-plans.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Lưu kế hoạch</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
