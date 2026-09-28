<x-app-layout title="Thêm hoạt động" :breadcrumbs="['Hoạt động' => route('activities.index'), 'Thêm mới' => null]">
    @if($plan)
        <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800"
             x-data="{
                 warn: false,
                 planMonth: {{ (int) $plan->monthStart()->month }},
                 planYear: {{ (int) $plan->monthStart()->year }},
                 check() {
                     const v = document.getElementById('start_time')?.value;
                     if (!v) { this.warn = false; return; }
                     const d = new Date(v);
                     this.warn = (d.getMonth() + 1) !== this.planMonth || d.getFullYear() !== this.planYear;
                 }
             }"
             x-init="check(); document.getElementById('start_time')?.addEventListener('input', () => check())">
            <div class="font-medium">Đang tạo hoạt động từ kế hoạch: {{ $plan->title }}</div>
            <div class="mt-0.5">Kế hoạch tháng {{ $plan->month }} năm học {{ $plan->academic_year }}. Các thông tin đã nhập ở kế hoạch (tên, tổ, loại hoạt động, điểm thi đua, tổ phối hợp, ghi chú) được điền sẵn — hãy chọn thời gian tổ chức.</div>
            <div x-show="warn" x-cloak class="mt-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-amber-800">
                Ngày bắt đầu đang nằm ngoài tháng {{ $plan->monthStart()->format('m/Y') }} của kế hoạch — hoạt động có thể thuộc năm học khác khi tính điểm thi đua. Bạn vẫn có thể lưu nếu đã dời lịch.
            </div>
        </div>
    @endif

    <x-card title="Thêm hoạt động công đoàn">
        <form method="POST" action="{{ route('activities.store') }}">
            @csrf
            @if($plan)
                <input type="hidden" name="from_activity_plan_id" value="{{ $plan->id }}">
            @endif
            @include('activities._form', ['activity' => null])

            <div class="mt-6 flex justify-end gap-3">
                <x-btn href="{{ route('activities.index') }}" variant="secondary">Hủy</x-btn>
                <x-btn type="submit">Lưu hoạt động</x-btn>
            </div>
        </form>
    </x-card>
</x-app-layout>
