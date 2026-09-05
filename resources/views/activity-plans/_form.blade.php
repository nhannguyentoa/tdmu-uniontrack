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
        <x-input-label for="status" value="Trạng thái *" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            @foreach(\App\Models\ActivityPlan::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $p?->status ?? 'planned') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="note" value="Ghi chú" />
        <textarea id="note" name="note" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note', $p?->note) }}</textarea>
        <x-input-error :messages="$errors->get('note')" class="mt-1" />
    </div>
</div>
