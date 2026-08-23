@php
    $ug = $unionGroup ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="code" value="Mã tổ *" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $ug?->code)" required />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="name" value="Tên tổ công đoàn *" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $ug?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="department" value="Khoa/phòng/đơn vị" />
        <x-text-input id="department" name="department" class="mt-1 block w-full" :value="old('department', $ug?->department)" />
        <x-input-error :messages="$errors->get('department')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="status" value="Trạng thái *" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="active" @selected(old('status', $ug?->status ?? 'active') === 'active')>Đang hoạt động</option>
            <option value="inactive" @selected(old('status', $ug?->status) === 'inactive')>Ngưng hoạt động</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="leader_name" value="Người phụ trách" />
        <x-text-input id="leader_name" name="leader_name" class="mt-1 block w-full" :value="old('leader_name', $ug?->leader_name)" />
        <x-input-error :messages="$errors->get('leader_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="established_date" value="Ngày thành lập" />
        <x-text-input id="established_date" name="established_date" type="date" class="mt-1 block w-full" :value="old('established_date', $ug?->established_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('established_date')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="leader_phone" value="Số điện thoại" />
        <x-text-input id="leader_phone" name="leader_phone" class="mt-1 block w-full" :value="old('leader_phone', $ug?->leader_phone)" />
        <x-input-error :messages="$errors->get('leader_phone')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="leader_email" value="Email" />
        <x-text-input id="leader_email" name="leader_email" type="email" class="mt-1 block w-full" :value="old('leader_email', $ug?->leader_email)" />
        <x-input-error :messages="$errors->get('leader_email')" class="mt-1" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Mô tả" />
        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $ug?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-1" />
    </div>
</div>
