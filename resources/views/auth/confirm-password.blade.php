<x-guest-layout>
    <h2 class="mb-4 text-xl font-semibold text-slate-800">Xác nhận mật khẩu</h2>
    <div class="mb-4 text-sm text-slate-500">
        Đây là khu vực bảo mật. Vui lòng xác nhận mật khẩu trước khi tiếp tục.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Mật khẩu" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-btn type="submit" class="w-full justify-center">Xác nhận</x-btn>
    </form>
</x-guest-layout>
