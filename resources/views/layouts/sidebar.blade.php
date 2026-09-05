@php($user = auth()->user())

<aside
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed inset-y-0 left-0 z-40 w-64 transform bg-slate-900 text-slate-200 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
>
    <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">TĐM</div>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-white">TDMU UnionTrack</p>
            <p class="text-xs text-slate-400">Quản lý Công đoàn</p>
        </div>
    </div>

    <nav class="mt-4 space-y-1 px-3 pb-10 text-sm">
        <x-nav-section-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <x-slot name="icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </x-slot>
            Tổng quan
        </x-nav-section-link>

        @can('viewAny', \App\Models\UnionGroup::class)
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Tổ công đoàn</p>
            <x-nav-section-link :href="route('union-groups.index')" :active="request()->routeIs('union-groups.index')">
                <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></x-slot>
                Danh sách tổ
            </x-nav-section-link>
            @can('create', \App\Models\UnionGroup::class)
                <x-nav-section-link :href="route('union-groups.create')" :active="request()->routeIs('union-groups.create')">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></x-slot>
                    Thêm tổ
                </x-nav-section-link>
            @endcan
        @endcan

        @can('viewAny', \App\Models\Member::class)
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Đoàn viên</p>
            <x-nav-section-link :href="route('members.index')" :active="request()->routeIs('members.*')">
                <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" /></x-slot>
                Danh sách đoàn viên
            </x-nav-section-link>
            @can('create', \App\Models\Member::class)
                <x-nav-section-link :href="route('members.create')" :active="request()->routeIs('members.create')">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></x-slot>
                    Thêm đoàn viên
                </x-nav-section-link>
            @endcan
        @endcan

        @if($user->isMember())
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Cá nhân</p>
            @if($user->member)
                <x-nav-section-link :href="route('members.show', $user->member)" :active="request()->routeIs('members.show')">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></x-slot>
                    Hồ sơ của tôi
                </x-nav-section-link>
            @endif
        @endif

        <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Hoạt động</p>
        <x-nav-section-link :href="route('activities.index')" :active="request()->routeIs('activities.index') || request()->routeIs('activities.show')">
            <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></x-slot>
            Danh sách hoạt động
        </x-nav-section-link>
        @can('create', \App\Models\Activity::class)
            <x-nav-section-link :href="route('activities.create')" :active="request()->routeIs('activities.create')">
                <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></x-slot>
                Thêm hoạt động
            </x-nav-section-link>
        @endcan
        @can('viewAny', \App\Models\ActivityType::class)
            <x-nav-section-link :href="route('activity-types.index')" :active="request()->routeIs('activity-types.index')">
                <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></x-slot>
                Loại hoạt động
            </x-nav-section-link>
        @endcan

        <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Kế hoạch & Thi đua</p>
        <x-nav-section-link :href="route('activity-plans.index')" :active="request()->routeIs('activity-plans.*')">
            <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></x-slot>
            Kế hoạch hoạt động
        </x-nav-section-link>
        <x-nav-section-link :href="route('evaluation.report')" :active="request()->routeIs('evaluation.*') || request()->routeIs('evaluation-criteria.*')">
            <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></x-slot>
            Chấm điểm thi đua
        </x-nav-section-link>

        <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Báo cáo</p>
        <x-nav-section-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
            <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></x-slot>
            Báo cáo & thống kê
        </x-nav-section-link>

        @can('viewAny', \App\Models\User::class)
            <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Hệ thống</p>
            <x-nav-section-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v1h16v-1c0-2.66-5.33-4-8-4z" /></x-slot>
                Quản lý tài khoản
            </x-nav-section-link>
        @endcan

        <x-nav-section-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
            <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /></x-slot>
            Cài đặt tài khoản
        </x-nav-section-link>
    </nav>
</aside>
