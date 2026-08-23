<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-800 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 px-4 py-10">
            <div class="mb-6 flex flex-col items-center text-center">
                <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-xl font-bold text-blue-700 shadow-lg">TĐM</div>
                <h1 class="text-lg font-semibold text-white">TRƯỜNG ĐẠI HỌC THỦ DẦU MỘT</h1>
                <p class="text-sm text-blue-200">Hệ thống quản lý hoạt động Công đoàn — TDMU UnionTrack</p>
            </div>

            <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-2xl sm:px-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-blue-200">© {{ now()->year }} Công đoàn Trường Đại học Thủ Dầu Một</p>
        </div>
    </body>
</html>
