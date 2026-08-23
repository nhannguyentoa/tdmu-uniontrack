<x-guest-layout>
    <h2 class="mb-1 text-xl font-semibold text-slate-800">Quên mật khẩu</h2>
    <div class="mb-4 text-sm text-slate-500">
        Vui lòng nhập địa chỉ email của bạn. Hệ thống sẽ gửi liên kết đặt lại mật khẩu.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-btn type="submit" class="w-full justify-center">Gửi liên kết đặt lại mật khẩu</x-btn>
    </form>
</x-guest-layout>
