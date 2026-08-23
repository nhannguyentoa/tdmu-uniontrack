@php
    $a = $activity ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="code" value="Mã hoạt động *" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $a?->code)" required />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="name" value="Tên hoạt động *" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $a?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="union_group_id" value="Tổ công đoàn *" />
        <select id="union_group_id" name="union_group_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">-- Chọn tổ công đoàn --</option>
            @foreach($unionGroups as $group)
                <option value="{{ $group->id }}" @selected((string) old('union_group_id', $a?->union_group_id) === (string) $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('union_group_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="activity_type_id" value="Loại hoạt động *" />
        <select id="activity_type_id" name="activity_type_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">-- Chọn loại hoạt động --</option>
            @foreach($activityTypes as $type)
                <option value="{{ $type->id }}" @selected((string) old('activity_type_id', $a?->activity_type_id) === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('activity_type_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="responsible_user_id" value="Người phụ trách (tài khoản)" />
        <select id="responsible_user_id" name="responsible_user_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Không chọn --</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((string) old('responsible_user_id', $a?->responsible_user_id) === (string) $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('responsible_user_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="responsible_name" value="Người phụ trách (tên hiển thị)" />
        <x-text-input id="responsible_name" name="responsible_name" class="mt-1 block w-full" :value="old('responsible_name', $a?->responsible_name)" />
        <x-input-error :messages="$errors->get('responsible_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="start_time" value="Thời gian bắt đầu *" />
        <x-text-input id="start_time" name="start_time" type="datetime-local" class="mt-1 block w-full" :value="old('start_time', $a?->start_time?->format('Y-m-d\TH:i'))" required />
        <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="end_time" value="Thời gian kết thúc *" />
        <x-text-input id="end_time" name="end_time" type="datetime-local" class="mt-1 block w-full" :value="old('end_time', $a?->end_time?->format('Y-m-d\TH:i'))" required />
        <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="location" value="Địa điểm" />
        <x-text-input id="location" name="location" class="mt-1 block w-full" :value="old('location', $a?->location)" />
        <x-input-error :messages="$errors->get('location')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="goal" value="Mục tiêu" />
        <textarea id="goal" name="goal" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('goal', $a?->goal) }}</textarea>
        <x-input-error :messages="$errors->get('goal')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="content" value="Nội dung hoạt động" />
        <textarea id="content" name="content" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('content', $a?->content) }}</textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="expected_quantity" value="Số lượng dự kiến" />
        <x-text-input id="expected_quantity" name="expected_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('expected_quantity', $a?->expected_quantity ?? 0)" />
        <x-input-error :messages="$errors->get('expected_quantity')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="actual_quantity" value="Số lượng thực tế" />
        <x-text-input id="actual_quantity" name="actual_quantity" type="number" min="0" class="mt-1 block w-full" :value="old('actual_quantity', $a?->actual_quantity ?? 0)" />
        <x-input-error :messages="$errors->get('actual_quantity')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="status" value="Trạng thái *" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            @foreach(\App\Models\Activity::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $a?->status ?? 'not_started') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="progress" value="Tiến độ (%) *" />
        <x-text-input id="progress" name="progress" type="number" min="0" max="100" class="mt-1 block w-full" :value="old('progress', $a?->progress ?? 0)" required />
        <x-input-error :messages="$errors->get('progress')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="budget" value="Kinh phí (VNĐ)" />
        <x-text-input id="budget" name="budget" type="number" min="0" step="1000" class="mt-1 block w-full" :value="old('budget', $a?->budget)" />
        <x-input-error :messages="$errors->get('budget')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="note" value="Ghi chú" />
        <textarea id="note" name="note" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note', $a?->note) }}</textarea>
        <x-input-error :messages="$errors->get('note')" class="mt-1" />
    </div>
</div>
