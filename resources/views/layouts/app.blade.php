<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' - ' : '' }}{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-800" x-data="{ sidebarOpen: false }">
        <div class="flex min-h-screen">
            @include('layouts.sidebar')

            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 shadow-sm sm:px-6">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = !sidebarOpen" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <div>
                            <nav class="text-xs text-slate-400" aria-label="breadcrumb">
                                <ol class="flex items-center gap-1">
                                    <li><a href="{{ route('dashboard') }}" class="hover:text-blue-600">Tổng quan</a></li>
                                    @isset($breadcrumbs)
                                        @foreach($breadcrumbs as $label => $url)
                                            <li>/</li>
                                            <li>
                                                @if($url)
                                                    <a href="{{ $url }}" class="hover:text-blue-600">{{ $label }}</a>
                                                @else
                                                    <span class="text-slate-600">{{ $label }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    @endisset
                                </ol>
                            </nav>
                            <h1 class="text-lg font-semibold text-slate-800">{{ $title ?? 'Tổng quan' }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">
                                @switch(auth()->user()->role)
                                    @case('admin') Quản trị viên @break
                                    @case('officer') Cán bộ công đoàn @break
                                    @default Đoàn viên
                                @endswitch
                            </p>
                        </div>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 font-semibold text-white">
                                {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                            </button>
                            <div x-show="open" x-cloak x-transition class="absolute right-0 z-30 mt-2 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Cài đặt tài khoản</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-slate-50">Đăng xuất</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-4 sm:p-6">
                    <x-flash-messages />
                    {{ $slot }}
                </main>

                <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-xs text-slate-400">
                    © {{ now()->year }} Công đoàn Trường Đại học Thủ Dầu Một — TDMU UnionTrack
                </footer>
            </div>
        </div>
    </body>
</html>
