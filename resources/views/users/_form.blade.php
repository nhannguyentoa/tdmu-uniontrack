@php
    $u = $user ?? null;
@endphp

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Họ và tên *" />
        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $u?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="email" value="Email *" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $u?->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password" :value="$u ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu *'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" {{ $u ? '' : 'required' }} />
        <x-input-error :messages="$errors->get('password')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Xác nhận mật khẩu" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" {{ $u ? '' : 'required' }} />
    </div>

    <div>
        <x-input-label for="role" value="Vai trò *" />
        <select id="role" name="role" x-data x-on:change="document.getElementById('union-group-picker').classList.toggle('hidden', $event.target.value !== 'officer')"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            <option value="admin" @selected(old('role', $u?->role) === 'admin')>Quản trị viên</option>
            <option value="officer" @selected(old('role', $u?->role) === 'officer')>Cán bộ công đoàn</option>
            <option value="member" @selected(old('role', $u?->role ?? 'member') === 'member')>Đoàn viên</option>
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="phone" value="Số điện thoại" />
        <x-text-input id="phone" name="phone" class="mt-1 block w-full" :value="old('phone', $u?->phone)" />
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>

    <div id="union-group-picker" class="sm:col-span-2 {{ old('role', $u?->role) !== 'officer' ? 'hidden' : '' }}">
        <x-input-label value="Tổ công đoàn phụ trách (dành cho cán bộ công đoàn)" />
        <div class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-3">
            @foreach($unionGroups as $group)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="union_group_ids[]" value="{{ $group->id }}"
                           @checked(collect(old('union_group_ids', $assignedGroupIds ?? []))->contains($group->id))
                           class="rounded border-slate-300 text-blue-600">
                    {{ $group->name }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $u?->is_active ?? true)) class="rounded border-slate-300 text-blue-600">
            Tài khoản đang hoạt động
        </label>
    </div>
</div>
