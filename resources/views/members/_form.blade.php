@php
    $m = $member ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="code" value="Mã đoàn viên *" />
        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $m?->code)" required />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="full_name" value="Họ và tên *" />
        <x-text-input id="full_name" name="full_name" class="mt-1 block w-full" :value="old('full_name', $m?->full_name)" required />
        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="dob" value="Ngày sinh" />
        <x-text-input id="dob" name="dob" type="date" class="mt-1 block w-full" :value="old('dob', $m?->dob?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('dob')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="gender" value="Giới tính" />
        <select id="gender" name="gender" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">-- Chọn --</option>
            <option value="male" @selected(old('gender', $m?->gender) === 'male')>Nam</option>
            <option value="female" @selected(old('gender', $m?->gender) === 'female')>Nữ</option>
            <option value="other" @selected(old('gender', $m?->gender) === 'other')>Khác</option>
        </select>
        <x-input-error :messages="$errors->get('gender')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $m?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="phone" value="Số điện thoại" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $m?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="position" value="Chức vụ" />
        <x-text-input id="position" name="position" class="mt-1 block w-full" :value="old('position', $m?->position)" />
        <x-input-error :messages="$errors->get('position')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="department" value="Đơn vị/Khoa/Phòng" />
        <x-text-input id="department" name="department" class="mt-1 block w-full" :value="old('department', $m?->department)" />
        <x-input-error :messages="$errors->get('department')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="union_group_id" value="Tổ công đoàn *" />
        <select id="union_group_id" name="union_group_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="">-- Chọn tổ công đoàn --</option>
            @foreach($unionGroups as $group)
                <option value="{{ $group->id }}" @selected((string) old('union_group_id', $m?->union_group_id ?? request('union_group_id')) === (string) $group->id)>{{ $group->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('union_group_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="joined_union_date" value="Ngày tham gia công đoàn" />
        <x-text-input id="joined_union_date" name="joined_union_date" type="date" class="mt-1 block w-full" :value="old('joined_union_date', $m?->joined_union_date?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('joined_union_date')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="status" value="Trạng thái *" />
        <select id="status" name="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="active" @selected(old('status', $m?->status ?? 'active') === 'active')>Đang hoạt động</option>
            <option value="inactive" @selected(old('status', $m?->status) === 'inactive')>Ngưng hoạt động</option>
            <option value="transferred" @selected(old('status', $m?->status) === 'transferred')>Đã chuyển</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="avatar" value="Ảnh đại diện (jpg, png, webp - tối đa 2MB)" />
        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
               class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
        <x-input-error :messages="$errors->get('avatar')" class="mt-1" />
        @if($m?->avatar_path)
            <img src="{{ Storage::url($m->avatar_path) }}" class="mt-2 h-16 w-16 rounded-full object-cover" alt="Ảnh hiện tại">
        @endif
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="note" value="Ghi chú" />
        <textarea id="note" name="note" rows="3" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('note', $m?->note) }}</textarea>
        <x-input-error :messages="$errors->get('note')" class="mt-1" />
    </div>
</div>
