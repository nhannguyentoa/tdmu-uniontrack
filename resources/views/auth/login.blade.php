<x-guest-layout>
    <h2 class="mb-1 text-xl font-semibold text-slate-800">Đăng nhập hệ thống</h2>
    <p class="mb-6 text-sm text-slate-500">Vui lòng đăng nhập để quản lý hoạt động công đoàn.</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="ten@tdmu.edu.vn" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mật khẩu" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">Ghi nhớ đăng nhập</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-blue-600 hover:underline" href="{{ route('password.request') }}">
                    Quên mật khẩu?
                </a>
            @endif
        </div>

        <x-btn type="submit" class="w-full justify-center">Đăng nhập</x-btn>

        <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-500">
            <p class="font-medium text-slate-600">Tài khoản dùng thử:</p>
            <p>Quản trị viên: admin@tdmu.edu.vn / Admin@123</p>
            <p>Cán bộ công đoàn: officer1@tdmu.edu.vn / Officer@123</p>
            <p>Đoàn viên: member@tdmu.edu.vn / Member@123</p>
        </div>
    </form>
</x-guest-layout>
