@php
    $p = $activityPlan ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="academic_year" value="Năm học *" />
        <x-text-input id="academic_year" name="academic_year" class="mt-1 block w-full" placeholder="VD: 2026-2027" :value="old('academic_year', $p?->academic_year ?? '2026-2027')" required />
        <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="month" value="Tháng *" />
        <select id="month" name="month" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected((string) old('month', $p?->month) === (string) $m)>Tháng {{ $m }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('month')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="title" value="Nội dung hoạt động *" />
        <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title', $p?->title)" required />
        <x-input-error :messages="$errors->get('title')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="host_union_group_id" value="Đơn vị đăng cai" />
        <select id="host_union_group_id" name="host_union_group_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Chưa xác định --</option>
            @foreach($unionGroups as $group)
                <option value="{{ $group->id }}" @selected((string) old('host_union_group_id', $p?->host_union_group_id) === (string) $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('host_union_group_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="department_id" value="Ban phụ trách" />
        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Chưa xác định --</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $p?->department_id) === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('department_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="activity_type_id" value="Loại hoạt động" />
        <select id="activity_type_id" name="activity_type_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Chưa xác định --</option>
            @foreach($activityTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('activity_type_id', $p?->activity_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('activity_type_id')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="note" value="Ghi chú" />
        <textarea id="note" name="note" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note', $p?->note) }}</textarea>
        <x-input-error :messages="$errors->get('note')" class="mt-1" />
    </div>

    <div class="sm:col-span-2 rounded-lg border border-slate-200 p-4" x-data="{ counts: {{ old('counts_for_evaluation', $p?->counts_for_evaluation ?? false) ? 'true' : 'false' }} }">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="counts_for_evaluation" value="1" x-model="counts"
                   @checked(old('counts_for_evaluation', $p?->counts_for_evaluation)) class="rounded border-slate-300 text-blue-600">
            Hoạt động này có tính điểm thi đua
        </label>
        <p class="mt-1 text-xs text-slate-500">Thông tin này sẽ được điền sẵn khi chuyển kế hoạch thành hoạt động.</p>
        <div x-show="counts" x-cloak class="mt-3 max-w-xs">
            <x-input-label for="evaluation_max_score" value="Điểm thi đua tối đa *" />
            <x-text-input id="evaluation_max_score" name="evaluation_max_score" type="number" min="0.5" max="50" step="0.5" class="mt-1 block w-full" :value="old('evaluation_max_score', $p?->evaluation_max_score)" />
            <x-input-error :messages="$errors->get('evaluation_max_score')" class="mt-1" />
        </div>
    </div>

    @php
        $phoiHopIds = old('collaborating_groups.phoi_hop', $p?->collaboratingGroups->where('pivot.role', 'phoi_hop')->pluck('id')->all() ?? []);
        $thamGiaIds = old('collaborating_groups.tham_gia', $p?->collaboratingGroups->where('pivot.role', 'tham_gia')->pluck('id')->all() ?? []);
    @endphp

    <div class="sm:col-span-2">
        <x-input-label value="Tổ phối hợp tổ chức (ngoài đơn vị đăng cai)" />
        <div class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-3">
            @foreach($unionGroups as $group)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="collaborating_groups[phoi_hop][]" value="{{ $group->id }}"
                           @checked(collect($phoiHopIds)->contains($group->id))
                           class="rounded border-slate-300 text-blue-600">
                    {{ $group->name }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('collaborating_groups')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label value="Tổ tham gia (không đứng ra tổ chức)" />
        <div class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-3">
            @foreach($unionGroups as $group)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="collaborating_groups[tham_gia][]" value="{{ $group->id }}"
                           @checked(collect($thamGiaIds)->contains($group->id))
                           class="rounded border-slate-300 text-blue-600">
                    {{ $group->name }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="sm:col-span-2">
        @if($p?->activity)
            <div class="rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">
                Kế hoạch này đã được chuyển thành hoạt động
                <a href="{{ route('activities.show', $p->activity) }}" class="font-medium underline">{{ $p->activity->code }}</a>
                — trạng thái kế hoạch được đồng bộ theo trạng thái của hoạt động.
            </div>
        @else
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_cancelled" value="0">
                <input type="checkbox" name="is_cancelled" value="1"
                       @checked(old('is_cancelled', $p?->isCancelled())) class="rounded border-slate-300 text-red-600">
                Đánh dấu kế hoạch này đã hủy (không tổ chức nữa)
            </label>
            <p class="mt-1 text-xs text-slate-500">Trạng thái kế hoạch tự động: "Dự kiến" khi tháng kế hoạch chưa qua, "Quá hạn" khi đã qua mà chưa chuyển thành hoạt động.</p>
        @endif
    </div>
</div>
