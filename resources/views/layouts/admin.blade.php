<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', __('menus.dashboard')) — {{ \App\Models\Setting::get('org.name', config('app.name')) }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
    @include('partials.admin-theme')
    @php
        $adminLogoSize = min(max((int) \App\Models\Setting::get('appearance.logo_size', 36), 24), 72);
    @endphp
    <style>
    /* Sidebar colour tokens (--sb-*) come from partials.admin-theme, chosen for the sidebar colour. */
    /* ── Sidebar scrollbar ──────────────────────────────────────── */
    .sidebar-nav::-webkit-scrollbar       { width: 3px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: var(--sb-line); border-radius: 99px; }
    .sidebar-nav::-webkit-scrollbar-thumb:hover { background: var(--sb-muted); }

    /* ── Collapsed tooltip ──────────────────────────────────────── */
    .nav-tooltip {
        pointer-events: none;
        position: fixed;
        left: 5rem;
        z-index: 200;
        white-space: nowrap;
        padding: .35rem .75rem;
        background: #0f172a;
        color: #f8fafc;
        font-size: .8125rem;
        font-weight: 600;
        border-radius: .5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.35);
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity .15s ease, transform .15s ease;
    }
    .nav-item:hover .nav-tooltip { opacity: 1; transform: translateX(0); }

    /* ── Nav item states ────────────────────────────────────────── */
    .nav-item:hover        { background: var(--sb-hover) !important; }
    .nav-active            { background: var(--sb-active-bg); color: var(--sb-active-text); }
    .nav-inactive          { color: var(--sb-muted); }
    .nav-icon-active       { color: var(--sb-active-icon); }
    .nav-icon-inactive     { color: var(--sb-inactive-icon); }

    /* ── Topbar design tokens — light mode ──────────────────────── */
    :root {
        --tb-bg:            rgba(255,255,255,.97);
        --tb-border:        rgba(0,0,0,.07);
        --tb-text:          #111827;
        --tb-muted:         #4b5563;
        --tb-dim:           #6b7280;
        --tb-hover:         rgba(0,0,0,.05);
        --tb-search-bg:     #f6f7f9;
        --tb-search-border: #e5e7eb;
        --tb-search-text:   #4b5563;
    }

    /* ── Topbar design tokens — dark mode ───────────────────────── */
    html.dark {
        --tb-bg:            rgba(10,17,34,.97);
        --tb-border:        rgba(255,255,255,.07);
        --tb-text:          rgba(255,255,255,.88);
        --tb-muted:         rgba(255,255,255,.75);
        --tb-dim:           rgba(255,255,255,.6);
        --tb-hover:         rgba(255,255,255,.07);
        --tb-search-bg:     rgba(255,255,255,.06);
        --tb-search-border: rgba(255,255,255,.12);
        --tb-search-text:   rgba(255,255,255,.7);
    }

    /* ── Topbar btn base ────────────────────────────────────────── */
    .tb-btn {
        align-items: center; justify-content: center;
        height: 2.25rem; width: 2.25rem; border-radius: .5rem;
        color: var(--tb-muted); transition: background .12s ease, color .12s ease;
    }
    .tb-btn:hover { background: var(--tb-hover); color: var(--tb-text); }

    /* ── Locale switcher active/inactive ────────────────────────── */
    .locale-active   { background: var(--color-brand); color: #fff; }
    .locale-inactive { color: var(--tb-muted); }
    .locale-inactive:hover { color: var(--tb-text); }

    [data-admin-page-frame] {
        transition: opacity .16s ease, transform .16s ease;
    }

    [data-admin-page-frame].is-loading {
        opacity: .38;
        pointer-events: none;
        transform: translateY(4px);
    }

    [data-admin-progress] {
        opacity: 0;
        transform: scaleX(0);
        transform-origin: left;
        transition: opacity .2s ease, transform .2s ease;
    }

    [data-admin-progress].is-loading {
        opacity: 1;
        transform: scaleX(1);
    }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased locale-{{ app()->getLocale() }} lang-{{ app()->getLocale() }}"
      x-data="adminShell()"
      @keydown.escape="sidebarOpen = false; searchOpen = false"
      @keydown.window.ctrl.k.prevent="openSearch()"
      @keydown.window.meta.k.prevent="openSearch()">

{{-- ════════════════════════════════════════════════════════════
     Mobile overlay
════════════════════════════════════════════════════════════ --}}
<div x-show="sidebarOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
     @click="sidebarOpen = false" style="display:none"></div>

{{-- ════════════════════════════════════════════════════════════
     Sidebar — colour from Settings › Appearance (sidebar colour);
     text tokens (--sb-*) are chosen for readability in partials.admin-theme.
     Desktop: 264px, collapsible to 72px. Mobile: slide-in drawer.
════════════════════════════════════════════════════════════ --}}
@php
    $orgName = \App\Models\Setting::get('org.name', config('app.name'));
    $orgLogo = \App\Models\Setting::get('org.logo', '');
@endphp
<aside
    id="admin-sidebar"
    aria-label="{{ __('menus.admin_panel') }}"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    {{-- object syntax keeps the static background below --}}
    :style="{ width: (sidebarOpen || !sidebarCollapsed) ? '16.5rem' : '4.5rem' }"
    style="width: 16.5rem; background: linear-gradient(180deg, color-mix(in srgb, var(--color-navy) 90%, white) 0%, var(--color-navy) 38%, var(--color-navy-dark) 100%); box-shadow: 1px 0 0 var(--sb-border), 4px 0 24px rgba(0,0,0,.10);"
    class="fixed inset-y-0 left-0 z-50 flex flex-col overflow-hidden transition-all duration-300 ease-out lg:translate-x-0">

    {{-- ── Brand + collapse ── --}}
    <div class="flex min-h-16 shrink-0 items-center gap-3 px-3.5 py-2" style="border-bottom: 1px solid var(--sb-border);">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 flex-1 items-center gap-3" :class="sidebarCollapsed && !sidebarOpen ? 'justify-center' : ''">
            @if($orgLogo)
            <img src="{{ Storage::url($orgLogo) }}" alt="" class="shrink-0 rounded-md object-contain"
                 style="width: {{ $adminLogoSize }}px; height: {{ $adminLogoSize }}px;">
            @else
            <span class="flex shrink-0 items-center justify-center rounded-lg text-[13px] font-bold"
                  style="width: {{ $adminLogoSize }}px; height: {{ $adminLogoSize }}px; color: var(--sb-text); border: 1px solid var(--sb-line); background: var(--sb-footer-bg);">
                {{ mb_strtoupper(mb_substr($orgName, 0, 2)) }}
            </span>
            @endif
            <span class="min-w-0" x-show="!sidebarCollapsed || sidebarOpen">
                <span class="block truncate text-sm font-bold leading-tight" style="color: var(--sb-text);">{{ $orgName }}</span>
                <span class="mt-0.5 block truncate text-xs" style="color: var(--sb-dim);">{{ __('menus.admin_panel') }}</span>
            </span>
        </a>

        {{-- Desktop: collapse / expand --}}
        <button type="button" @click="toggleSidebar()" x-show="!sidebarOpen"
                class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg transition lg:flex"
                style="color: var(--sb-text); background: var(--sb-hover);"
                :class="sidebarCollapsed ? 'absolute left-1/2 top-[4.5rem] -translate-x-1/2' : ''"
                :title="sidebarCollapsed ? @js(__('messages.expand_sidebar')) : @js(__('messages.collapse_sidebar'))"
                :aria-label="sidebarCollapsed ? @js(__('messages.expand_sidebar')) : @js(__('messages.collapse_sidebar'))"
                aria-controls="admin-sidebar" :aria-expanded="(!sidebarCollapsed).toString()">
            <svg class="h-4 w-4 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>

        {{-- Mobile: close drawer --}}
        <button type="button" @click="sidebarOpen = false" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg lg:hidden"
                style="color: var(--sb-text);" aria-label="{{ __('public.close') }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- ── Navigation ── --}}
    <nav class="sidebar-nav flex-1 overflow-y-auto overflow-x-hidden px-2.5 pb-3" :class="sidebarCollapsed && !sidebarOpen ? 'pt-14' : 'pt-2'"
         aria-label="{{ __('menus.admin_panel') }}">
        @php
        $authUser = auth()->user();
        $nav = [];
        $canManageExamInterview =
            $authUser->hasAnyRole(['super_admin','admin','hr_manager','hr_officer','exam_officer','interview_officer'])
            || $authUser->hasAnyPermission(['exams.view','interviews.view','exams.record-results','interviews.record-results']);

        $nav[] = [
            'route' => 'admin.dashboard', 'label' => __('menus.dashboard'),
            'match' => 'admin.dashboard',
            'icon'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        ];

        // canPerm: safe wrapper — returns false if permission row doesn't exist yet in DB
        $canPerm = function(string $perm) use ($authUser): bool {
            try { return $authUser->hasPermissionTo($perm); } catch (\Throwable) { return false; }
        };
        $canAnyPerm = function(array $perms) use ($authUser): bool {
            try { return $authUser->hasAnyPermission($perms); } catch (\Throwable) { return false; }
        };

        if ($canAnyPerm(['institutions.view','vacancies.view','applications.view','screening.view']) || $canManageExamInterview)
            $nav[] = 'recruitment';
        if ($canPerm('institutions.view')) {
            $nav[] = ['route'=>'admin.institutions.index','label'=>__('menus.institutions'),'match'=>'admin.institutions.*',
                'icon'=>'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'];
        }
        if ($canPerm('vacancies.view')) {
            $nav[] = ['route'=>'admin.vacancies.index',    'label'=>__('menus.vacancies'),    'match'=>'admin.vacancies.*',
                'icon'=>'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'];
            $nav[] = ['route'=>'admin.announcements.index','label'=>__('menus.announcements'),'match'=>'admin.announcements.*',
                'icon'=>'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'];
        }
        if ($canPerm('applications.view')) {
            $nav[] = ['route'=>'admin.applications.index','label'=>__('menus.applications'),'match'=>'admin.applications.*',
                'icon'=>'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
            $nav[] = ['route'=>'admin.applicants.index',  'label'=>__('menus.applicants'),  'match'=>'admin.applicants.*',
                'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
        }
        if ($canPerm('screening.view')) {
            $nav[] = 'screening';
            $nav[] = ['route'=>'admin.screening.index', 'label'=>__('menus.screening'),         'match'=>'admin.screening.index',
                'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
            $nav[] = ['route'=>'admin.screening.passed','label'=>__('menus.passed_applicants'), 'match'=>'admin.screening.passed',
                'icon'=>'M5 13l4 4L19 7'];
            $nav[] = ['route'=>'admin.screening.failed','label'=>__('menus.failed_applicants'),'match'=>'admin.screening.failed',
                'icon'=>'M6 18L18 6M6 6l12 12'];
        }
        if ($canManageExamInterview) {
            $nav[] = 'exams';
            $nav[] = ['route'=>'admin.schedules.index',    'label'=>__('menus.schedules'),    'match'=>'admin.schedules.*',
                'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'];
            $nav[] = ['route'=>'admin.final-results.index','label'=>__('menus.final_results'),'match'=>'admin.final-results.*',
                'icon'=>'M9 12l2 2 4-4M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z'];
        }
        if ($authUser->hasAnyRole(['super_admin','admin','hr_manager']) || $canAnyPerm(['notifications.view','notifications.templates.manage','notifications.send'])) {
            $nav[] = 'notifications';
            $nav[] = ['route'=>'admin.notification-templates.index','label'=>__('menus.notification_templates'),'match'=>'admin.notification-templates.*',
                'icon'=>'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'];
        }
        if ($canPerm('reports.view')) {
            $nav[] = 'reports';
            $nav[] = ['route'=>'admin.reports.index','label'=>__('menus.reports'),'match'=>'admin.reports.*',
                'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'];
        }
        if ($canAnyPerm(['users.view','roles.view','permissions.view'])) {
            $nav[] = 'access';
            if ($canPerm('users.view'))
                $nav[] = ['route'=>'admin.users.index','label'=>__('menus.users'),'match'=>'admin.users.*',
                    'icon'=>'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
            if ($canAnyPerm(['roles.view','permissions.view']))
                $nav[] = ['route'=>'admin.roles.index','label'=>__('menus.roles'),'match'=>'admin.roles.*',
                    'icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'];
        }
        if ($canAnyPerm(['settings.view','audit.view','backups.view'])) {
            $nav[] = 'system';
            if ($canPerm('settings.view')) {
                $nav[] = ['route'=>'admin.settings.index',   'label'=>__('menus.settings'),   'match'=>'admin.settings.*',
                    'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                $nav[] = ['route'=>'admin.hero-sliders.index','label'=>__('menus.hero_slider'),'match'=>'admin.hero-sliders.*',
                    'icon'=>'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'];
            }
            if ($canPerm('backups.view'))
                $nav[] = ['route'=>'admin.backups.index','label'=>__('menus.backups'),'match'=>'admin.backups.*',
                    'icon'=>'M4 7v10c0 2.2 3.6 4 8 4s8-1.8 8-4V7M4 7c0 2.2 3.6 4 8 4s8-1.8 8-4M4 7c0-2.2 3.6-4 8-4s8 1.8 8 4m0 5c0 2.2-3.6 4-8 4s-8-1.8-8-4'];
            if ($canPerm('audit.view'))
                $nav[] = ['route'=>'admin.audit-logs.index','label'=>__('menus.audit_logs'),'match'=>'admin.audit-logs.*',
                    'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'];
        }

        // Live count of applications waiting for screening (only for users who can screen).
        if ($canPerm('screening.view')) {
            try {
                $pendingScreening = \App\Models\Application::whereIn('status', [\App\Enums\ApplicationStatus::Submitted, \App\Enums\ApplicationStatus::UnderReview])->count();
            } catch (\Throwable) {
                $pendingScreening = 0;
            }
            foreach ($nav as &$navEntry) {
                if (is_array($navEntry) && $navEntry['route'] === 'admin.screening.index') { $navEntry['count'] = $pendingScreening; }
            }
            unset($navEntry);
        }

        $groupLabels = [
            'recruitment'   => __('menus.recruitment'),
            'screening'     => __('menus.screening'),
            'exams'         => __('menus.exams_interviews'),
            'notifications' => __('menus.notifications'),
            'reports'       => __('menus.reports'),
            'access'        => __('menus.access_control'),
            'system'        => __('menus.system'),
        ];
        @endphp
        @foreach($nav as $item)
            @if(is_string($item))
                <p class="mt-5 mb-1.5 px-3 text-xs font-bold uppercase tracking-wider whitespace-nowrap" style="color: var(--sb-dim)"
                   x-show="!sidebarCollapsed || sidebarOpen">{{ $groupLabels[$item] ?? $item }}</p>
                <div class="mx-2 my-2.5 h-px" style="background: var(--sb-line)" x-show="sidebarCollapsed && !sidebarOpen" x-cloak></div>
            @else
                @php $active = request()->routeIs($item['match']); @endphp
                <a href="{{ route($item['route']) }}"
                   @if($active) aria-current="page" @endif
                   class="nav-item group relative mb-0.5 flex h-10 items-center rounded-lg text-sm transition-colors duration-150 {{ $active ? 'nav-active font-semibold' : 'nav-inactive font-medium' }}"
                   :class="sidebarCollapsed && !sidebarOpen ? 'justify-center' : 'gap-3 px-3'">
                    @if($active)
                    <span class="absolute inset-y-2 left-0 w-0.75 rounded-full" style="background: var(--color-accent);" aria-hidden="true"></span>
                    @endif
                    <svg class="h-[18px] w-[18px] shrink-0 {{ $active ? 'nav-icon-active' : 'nav-icon-inactive' }}" aria-hidden="true"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="{{ $item['icon'] }}"/>
                    </svg>
                    <span class="flex-1 truncate" x-show="!sidebarCollapsed || sidebarOpen">{{ $item['label'] }}</span>
                    @if(!empty($item['count']))
                    <span class="rounded-full px-2 py-px text-xs font-bold text-white" style="background: var(--color-accent);"
                          x-show="!sidebarCollapsed || sidebarOpen"
                          title="{{ trans_choice('messages.waiting_count', $item['count'], ['count' => $item['count']]) }}">{{ $item['count'] > 99 ? '99+' : $item['count'] }}</span>
                    <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full" style="background: var(--color-accent);" x-show="sidebarCollapsed && !sidebarOpen" x-cloak aria-hidden="true"></span>
                    @endif
                    <span class="nav-tooltip" x-show="sidebarCollapsed && !sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- ── Footer ── --}}
    <div class="shrink-0 space-y-2 p-2.5" style="border-top: 1px solid var(--sb-border);">
        {{-- Language switch lives here on mobile (topbar hides it) --}}
        @if(\App\Models\Setting::get('localization.show_language_switcher', true))
        <div class="flex rounded-lg p-0.5 text-sm font-semibold sm:hidden" style="background: var(--sb-hover);" role="group" aria-label="{{ __('public.language') }}">
            @foreach(['en' => 'EN', 'am' => 'አማ'] as $code => $label)
                @if(in_array($code, (array) \App\Models\Setting::get('app.available_locales', ['en', 'am']), true))
                <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}" data-admin-no-spa
                   class="flex-1 rounded-md py-2 text-center"
                   style="{{ app()->getLocale() === $code ? 'background:#fff;color:#111827;' : 'color: var(--sb-text);' }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>
        @endif
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="nav-item nav-inactive group relative flex h-10 items-center rounded-lg text-sm font-medium"
           :class="sidebarCollapsed && !sidebarOpen ? 'justify-center' : 'gap-3 px-3'">
            <svg class="h-[18px] w-[18px] shrink-0 nav-icon-inactive" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <span class="truncate" x-show="!sidebarCollapsed || sidebarOpen">{{ __('messages.view_public_site') }}</span>
            <span class="sr-only">({{ __('messages.opens_new_tab') }})</span>
            <span class="nav-tooltip" x-show="sidebarCollapsed && !sidebarOpen" x-cloak>{{ __('messages.view_public_site') }}</span>
        </a>
    </div>
</aside>

{{-- ════════════════════════════════════════════════════════════
     Main content wrapper
════════════════════════════════════════════════════════════ --}}
<div class="flex flex-col min-h-full transition-all duration-300 ease-out"
     :class="sidebarCollapsed ? 'lg:pl-18' : 'lg:pl-66'">
    <div data-admin-progress class="fixed left-0 right-0 top-0 z-[80] h-0.5"
         style="background: linear-gradient(90deg, var(--color-brand), var(--color-accent));"></div>

    {{-- ════════════════════════════════════════════════════════════
         Topbar — LEFT breadcrumb | CENTER search | RIGHT language, theme, user
    ════════════════════════════════════════════════════════════ --}}
    @php
        $userName     = auth()->user()?->name ?? 'User';
        $userRole     = auth()->user()?->roles->first()?->name ?? '';
        $nameParts    = preg_split('/\s+/', trim($userName)) ?: [$userName];
        $userInitials = mb_strtoupper(mb_substr($nameParts[0], 0, 1).(count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));

        // Breadcrumb: the menu group of the active page, then the page title.
        $crumbGroup = null;
        $lastGroup = null;
        foreach ($nav as $navItem) {
            if (is_string($navItem)) { $lastGroup = $groupLabels[$navItem] ?? null; continue; }
            if (request()->routeIs($navItem['match'])) { $crumbGroup = $lastGroup; break; }
        }
    @endphp
    <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 px-4 sm:px-6"
            style="background: var(--tb-bg); border-bottom: 1px solid var(--tb-border); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);">

        {{-- LEFT: menu (mobile) + breadcrumb --}}
        <div class="flex min-w-0 items-center gap-2">
            <button type="button" @click="sidebarOpen = true" class="tb-btn flex lg:hidden"
                    aria-label="{{ __('public.menu') }}" aria-controls="admin-sidebar" :aria-expanded="sidebarOpen.toString()">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <nav aria-label="Breadcrumb" class="min-w-0" data-admin-breadcrumb>
                <ol class="flex min-w-0 items-center gap-2 text-sm">
                    @if($crumbGroup)
                    <li class="hidden truncate md:block" style="color: var(--tb-muted)">{{ $crumbGroup }}</li>
                    <li class="hidden md:block" aria-hidden="true" style="color: var(--tb-dim)">/</li>
                    @endif
                    {{-- Not an <h1>: each page renders its own page title --}}
                    <li class="truncate font-semibold" style="color: var(--tb-text)" aria-current="page">@yield('title', __('menus.dashboard'))</li>
                    @hasSection('breadcrumb')
                    <li aria-hidden="true" style="color: var(--tb-dim)">/</li>
                    <li class="truncate" style="color: var(--tb-muted)">@yield('breadcrumb')</li>
                    @endif
                </ol>
            </nav>
        </div>

        {{-- CENTER: search (Ctrl K) --}}
        <div class="flex flex-1 justify-end sm:justify-center">
            <button type="button" @click="openSearch()"
                    class="hidden h-10 w-full max-w-md items-center gap-2.5 rounded-xl px-3 text-sm transition sm:flex hover:border-brand"
                    style="background: var(--tb-search-bg); border: 1px solid var(--tb-search-border);">
                <svg class="h-4 w-4 shrink-0" style="color: var(--tb-search-text)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span class="flex-1 text-left" style="color: var(--tb-search-text)">{{ __('messages.search_pages') }}</span>
                <kbd class="hidden rounded-md px-1.5 py-0.5 text-xs font-medium lg:inline" style="color: var(--tb-dim); border: 1px solid var(--tb-search-border);">Ctrl K</kbd>
            </button>
            <button type="button" @click="openSearch()" class="tb-btn flex sm:hidden" aria-label="{{ __('messages.search_pages') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </div>

        {{-- RIGHT --}}
        <div class="flex shrink-0 items-center gap-1.5">
            @if(\App\Models\Setting::get('localization.show_language_switcher', true))
            <div class="hidden items-center rounded-lg p-0.5 text-[13px] font-semibold sm:flex" style="background: var(--tb-search-bg); border: 1px solid var(--tb-search-border);"
                 role="group" aria-label="{{ __('public.language') }}">
                @foreach(['en' => 'EN', 'am' => 'አማ'] as $code => $label)
                    @if(in_array($code, (array) \App\Models\Setting::get('app.available_locales', ['en', 'am']), true))
                    <a href="{{ route('lang.switch', $code) }}" lang="{{ $code }}" data-admin-no-spa
                       @if(app()->getLocale() === $code) aria-current="true" @endif
                       class="rounded-md px-2.5 py-1 transition {{ app()->getLocale() === $code ? 'locale-active' : 'locale-inactive' }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            @endif

            <button type="button" @click="toggleDark()" class="tb-btn flex"
                    :title="darkMode ? @js(__('messages.light_mode')) : @js(__('messages.dark_mode'))"
                    :aria-label="darkMode ? @js(__('messages.light_mode')) : @js(__('messages.dark_mode'))">
                <svg x-show="!darkMode" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" x-cloak class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>

            <div class="mx-1 hidden h-6 w-px sm:block" style="background: var(--tb-border)" aria-hidden="true"></div>

            {{-- User menu --}}
            <div class="relative" x-data="{ open: false }" @keydown.escape.stop="open = false; $refs.userButton.focus()">
                <button type="button" x-ref="userButton" @click="open = !open" @click.outside="open = false"
                        :aria-expanded="open.toString()" aria-haspopup="menu"
                        class="flex h-11 items-center gap-2.5 rounded-xl pl-1 pr-2 text-left transition hover:bg-(--tb-hover)"
                        :class="open ? 'bg-(--tb-hover)' : ''">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand text-[13px] font-bold text-white">{{ $userInitials ?: 'U' }}</span>
                    <span class="hidden min-w-0 md:block">
                        <span class="block max-w-40 truncate text-sm font-semibold leading-tight" style="color: var(--tb-text)">{{ $userName }}</span>
                        @if($userRole)
                        <span class="block max-w-40 truncate text-xs leading-tight" style="color: var(--tb-muted)">{{ \Illuminate\Support\Str::headline($userRole) }}</span>
                        @endif
                    </span>
                    <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" style="color: var(--tb-dim)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" x-cloak role="menu"
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl"
                     style="background: var(--tb-bg); border: 1px solid var(--tb-border); box-shadow: 0 16px 40px rgba(16,24,40,.16);">
                    <div class="px-4 py-3" style="border-bottom: 1px solid var(--tb-border);">
                        <p class="truncate text-sm font-semibold" style="color: var(--tb-text)">{{ $userName }}</p>
                        <p class="mt-0.5 truncate text-[13px]" style="color: var(--tb-muted)">{{ auth()->user()?->email }}</p>
                    </div>
                    <div class="py-1">
                        @foreach([
                            [route('admin.profile.edit'), __('messages.edit_profile'), 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            [route('admin.two-factor.show'), __('messages.two_factor_security'), 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                        ] as [$href, $label, $icon])
                        <a href="{{ $href }}" role="menuitem" @click="open = false"
                           class="flex items-center gap-3 px-4 py-2.5 text-sm transition hover:bg-(--tb-hover) focus:bg-(--tb-hover) focus:outline-none" style="color: var(--tb-text);">
                            <svg class="h-4 w-4 shrink-0" style="color: var(--tb-muted)" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                    <div class="py-1" style="border-top: 1px solid var(--tb-border);">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" role="menuitem"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                {{ __('menus.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- ════════════════════════════════════════════════════════════
         Command palette / global search modal
    ════════════════════════════════════════════════════════════ --}}
    <div x-show="searchOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-start justify-center pt-[10vh] px-4"
         @click.self="searchOpen = false"
         style="background:rgba(0,0,0,.55); backdrop-filter:blur(4px); display:none;">

        <div x-show="searchOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             class="w-full max-w-lg rounded-2xl overflow-hidden"
             style="background:var(--tb-bg); border:1px solid var(--tb-border);
                    box-shadow:0 28px 70px rgba(0,0,0,.28);">

            {{-- Search input row --}}
            <div class="flex items-center gap-3 px-4" style="border-bottom:1px solid var(--tb-border);">
                <svg class="h-4.5 w-4.5 shrink-0" style="color:var(--tb-muted)"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <label for="admin-search-input" class="sr-only">{{ __('messages.search_pages') }}</label>
                <input x-ref="searchInput"
                       id="admin-search-input"
                       x-model="searchQuery"
                       type="text"
                       autocomplete="off"
                       placeholder="{{ __('messages.search_pages') }}"
                       class="flex-1 bg-transparent py-4 text-[15px] outline-none"
                       style="color:var(--tb-text); caret-color:var(--color-brand);"
                       @keydown.escape.stop="searchOpen = false"
                       @keydown.enter.prevent="$refs.searchResults.querySelector('a:not([hidden])')?.click()">
                <kbd class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium"
                     style="background:var(--tb-hover); color:var(--tb-dim); border:1px solid var(--tb-search-border);">Esc</kbd>
            </div>

            {{-- Every page this user can open (same list as the sidebar), filtered as you type --}}
            @php
                $searchLinks = collect($nav)->filter(fn ($i) => is_array($i))->values();
            @endphp
            <div class="max-h-[50vh] overflow-y-auto p-2" x-ref="searchResults">
                @foreach($searchLinks as $link)
                <a href="{{ route($link['route']) }}"
                   @click="searchOpen = false"
                   data-label="{{ mb_strtolower($link['label']) }}"
                   :hidden="searchQuery.trim() !== '' && !$el.dataset.label.includes(searchQuery.trim().toLowerCase())"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-100 hover:bg-(--tb-hover) focus:bg-(--tb-hover) focus:outline-none"
                   style="color:var(--tb-text);">
                    <svg class="h-4 w-4 shrink-0" style="color:var(--tb-muted)" aria-hidden="true"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
                @endforeach
                <p class="px-3 py-6 text-center text-sm" style="color:var(--tb-muted)"
                   x-show="searchQuery.trim() !== '' && ![...$refs.searchResults.querySelectorAll('a')].some(a => a.dataset.label.includes(searchQuery.trim().toLowerCase()))">
                    {{ __('messages.no_matching_pages') }}
                </p>
            </div>

            {{-- Footer --}}
            <div class="flex items-center gap-4 px-4 py-2.5"
                 style="border-top:1px solid var(--tb-border); background:var(--tb-search-bg);">
                <span class="flex items-center gap-1.5 text-xs" style="color:var(--tb-muted)">
                    <kbd class="rounded px-1.5 py-0.5 text-xs"
                         style="border:1px solid var(--tb-border); background:var(--tb-hover);">Enter</kbd>
                    {{ __('messages.to_open') }}
                </span>
                <span class="flex items-center gap-1.5 text-xs" style="color:var(--tb-muted)">
                    <kbd class="rounded px-1.5 py-0.5 text-xs"
                         style="border:1px solid var(--tb-border); background:var(--tb-hover);">Esc</kbd>
                    {{ __('messages.to_close') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── Flash messages ──────────────────────────────────────────── --}}
    <div id="admin-page-frame" data-admin-page-frame>
    @php
        $flashes = [
            'success' => ['alert-success', 'status', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            'error'   => ['alert-danger',  'alert',  'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
            'warning' => ['alert-warning', 'alert',  'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z'],
        ];
    @endphp
    <div class="mx-auto w-full max-w-360 space-y-3 px-4 sm:px-6 lg:px-8 {{ session()->hasAny(array_keys($flashes)) || $errors->any() ? 'pt-5' : '' }}">
        @foreach($flashes as $flashKey => [$flashClass, $flashRole, $flashIcon])
            @if(session($flashKey))
            <div class="alert {{ $flashClass }}" role="{{ $flashRole }}" x-data="{ show: true }" x-show="show">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $flashIcon }}"/>
                </svg>
                <p class="flex-1">{{ session($flashKey) }}</p>
                <button type="button" @click="show = false" class="-m-1 rounded p-1 opacity-70 hover:opacity-100" aria-label="{{ __('messages.dismiss') }}">
                    <svg class="h-4! w-4!" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endif
        @endforeach
        @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $flashes['error'][2] }}"/>
            </svg>
            <div class="flex-1">
                <p class="font-semibold">{{ __('messages.fix_errors') }}</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <main class="mx-auto w-full max-w-360 flex-1 px-4 py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    {{-- Page scripts live inside the frame so fast navigation (which swaps only this
         frame and re-runs its scripts) defines component factories such as
         ethiopianDatepicker() before Alpine initialises the new page. --}}
    @stack('scripts')
    </div>
</div>

<script>
function adminShell() {
    return {
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        darkMode: localStorage.getItem('theme') === 'dark',
        searchOpen: false,
        searchQuery: '',
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        toggleSidebar() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
        },
        openSearch() {
            this.searchOpen = true;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
    };
}
</script>

<script>
(() => {
    const frameSelector = '[data-admin-page-frame]';
    const progressSelector = '[data-admin-progress]';
    const skippedPathFragments = ['/download', '/preview', '/export', '/logout', '/announcements'];
    let controller = null;

    const shouldSkip = (link, event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        if (link.target || link.hasAttribute('download') || link.dataset.adminNoSpa !== undefined) {
            return true;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || ! url.pathname.startsWith('/admin')) {
            return true;
        }

        return skippedPathFragments.some((fragment) => url.pathname.includes(fragment));
    };

    const setLoading = (loading) => {
        document.querySelector(frameSelector)?.classList.toggle('is-loading', loading);
        document.querySelector(progressSelector)?.classList.toggle('is-loading', loading);
    };

    const initTree = (element) => {
        window.Alpine?.initTree?.(element);
    };

    // Scripts parsed by DOMParser are inert. Re-create the page's inline scripts so
    // component factories they define (e.g. x-data="templateEditor(...)") exist
    // before Alpine initialises the new frame.
    const runScripts = (root) => {
        root.querySelectorAll('script').forEach((inert) => {
            if (inert.type && ! ['text/javascript', 'module', 'application/javascript'].includes(inert.type)) {
                return;
            }
            const live = document.createElement('script');
            [...inert.attributes].forEach((attr) => live.setAttribute(attr.name, attr.value));
            live.textContent = inert.textContent;
            inert.replaceWith(live);
        });
    };

    const swapSidebarNav = (nextDocument) => {
        const currentNav = document.querySelector('.sidebar-nav');
        const nextNav = nextDocument.querySelector('.sidebar-nav');

        if (! currentNav || ! nextNav) {
            return;
        }

        currentNav.innerHTML = nextNav.innerHTML;
        initTree(currentNav);
    };

    // The top bar sits outside the swapped page frame, so its breadcrumb
    // (section / page) must be replaced explicitly on every navigation.
    const swapBreadcrumb = (nextDocument) => {
        const current = document.querySelector('[data-admin-breadcrumb]');
        const next = nextDocument.querySelector('[data-admin-breadcrumb]');

        if (current && next) {
            current.innerHTML = next.innerHTML;
        }
    };

    const closeMobileMenu = () => {
        const shell = window.Alpine?.$data?.(document.body);

        if (shell) {
            shell.sidebarOpen = false;
        }
    };

    const visit = async (url, pushState = true) => {
        controller?.abort();
        controller = new AbortController();
        setLoading(true);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Admin-Navigation': 'partial',
                },
                signal: controller.signal,
            });

            const contentType = response.headers.get('content-type') || '';

            if (! response.ok || ! contentType.includes('text/html')) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const nextDocument = new DOMParser().parseFromString(html, 'text/html');
            const nextFrame = nextDocument.querySelector(frameSelector);
            const currentFrame = document.querySelector(frameSelector);

            if (! nextFrame || ! currentFrame) {
                window.location.href = url;
                return;
            }

            document.title = nextDocument.title;
            swapSidebarNav(nextDocument);
            swapBreadcrumb(nextDocument);
            closeMobileMenu();
            currentFrame.replaceWith(nextFrame);
            runScripts(nextFrame);
            initTree(nextFrame);

            if (pushState) {
                window.history.pushState({ adminNavigation: true }, '', url);
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
            document.dispatchEvent(new CustomEvent('admin:navigated', { detail: { url } }));
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.href = url;
            }
        } finally {
            setLoading(false);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');

        if (! link || shouldSkip(link, event)) {
            return;
        }

        event.preventDefault();
        visit(link.href);
    });

    window.addEventListener('popstate', () => visit(window.location.href, false));
})();
</script>
</body>
</html>
