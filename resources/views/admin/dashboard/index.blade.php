@extends('layouts.admin')

@section('title', __('dashboard.title'))

@section('content')
<div class="space-y-6">

    {{-- ── Quick Actions ──────────────────────────────────────────────── --}}
    @php
    $quickActions = [];
    if (auth()->user()->hasPermissionTo('vacancies.create'))
        $quickActions[] = ['label' => __('dashboard.quick_actions.create_vacancy'), 'route' => route('admin.vacancies.create'), 'icon' => 'M12 4v16m8-8H4', 'style' => 'primary'];
    if (auth()->user()->hasPermissionTo('applications.view'))
        $quickActions[] = ['label' => __('dashboard.quick_actions.view_applications'), 'route' => route('admin.applications.index'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'style' => 'outline'];
    if (auth()->user()->hasPermissionTo('screening.view'))
        $quickActions[] = ['label' => __('dashboard.quick_actions.screening_queue'), 'route' => route('admin.screening.index'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'style' => 'accent'];
    if (auth()->user()->hasPermissionTo('reports.view'))
        $quickActions[] = ['label' => __('dashboard.quick_actions.generate_report'), 'route' => route('admin.reports.index'), 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'style' => 'outline'];
    @endphp

    @if(count($quickActions))
    <div class="flex flex-wrap gap-2">
        @foreach($quickActions as $action)
        @php
        $btnStyles = ['primary' => 'btn-primary', 'accent' => 'btn-accent'];
        $btnClass = $btnStyles[$action['style']] ?? 'btn-outline';
        @endphp
        <a href="{{ $action['route'] }}" class="{{ $btnClass }} btn">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/>
            </svg>
            {{ $action['label'] }}
        </a>
        @endforeach
    </div>
    @endif

    {{-- ── KPI Cards ───────────────────────────────────────────────────── --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
        $cards = [
            [
                'label' => __('dashboard.kpi.open_vacancies'),
                'value' => $stats['open_vacancies'],
                'icon'  => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'route' => 'admin.vacancies.index',
                'grad'  => 'from-brand to-blue-500',
            ],
            [
                'label' => __('dashboard.kpi.total_applications'),
                'value' => $stats['total_applications'],
                'icon'  => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'route' => 'admin.applications.index',
                'grad'  => 'from-violet-600 to-violet-400',
            ],
            [
                'label' => __('dashboard.kpi.pending_screening'),
                'value' => $stats['pending_screening'],
                'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'route' => 'admin.screening.index',
                'grad'  => 'from-amber-500 to-amber-400',
            ],
            [
                'label' => __('dashboard.kpi.total_applicants'),
                'value' => $stats['total_applicants'],
                'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                'route' => 'admin.applications.index',
                'grad'  => 'from-emerald-600 to-emerald-400',
            ],
        ];
        @endphp

        @foreach($cards as $card)
        <a href="{{ route($card['route']) }}"
           class="group overflow-hidden rounded-xl bg-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-card-hover"
           style="box-shadow: var(--shadow-card)">
            {{-- Blue gradient header --}}
            <div class="bg-linear-to-r {{ $card['grad'] }} flex items-center justify-between px-5 py-4">
                <p class="text-sm font-medium text-white/90 leading-tight">{{ $card['label'] }}</p>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
            {{-- White body with orange left accent --}}
            <div class="flex">
                <div class="w-1 shrink-0 bg-accent"></div>
                <div class="flex-1 px-5 py-4">
                    <p class="text-3xl font-bold tracking-tight text-gray-900 group-hover:text-brand transition-colors duration-200">
                        {{ number_format($card['value']) }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ __('dashboard.actions.view_all') }} →</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>

    {{-- ── Main grid ──────────────────────────────────────────────────── --}}
    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Recent Applications --}}
        <div class="xl:col-span-2 overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <span class="h-4 w-1 rounded-full bg-accent"></span>
                    <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.recent_applications') }}</h2>
                </div>
                <a href="{{ route('admin.applications.index') }}"
                   class="text-xs font-medium text-brand hover:text-brand-dark transition">{{ __('dashboard.actions.view_all') }} →</a>
            </div>

            @if($recentApplications->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_applications') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentApplications as $app)
                @php
                $badgeColors = ['submitted' => 'badge-blue', 'passed_screening' => 'badge-green', 'failed_screening' => 'badge-red'];
                $badgeClass = $badgeColors[$app->status->value] ?? 'badge-gray';
                @endphp
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-brand-muted transition-colors">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-muted text-xs font-semibold text-brand ring-1 ring-brand/10">
                        @if($canViewSensitive)
                        {{ mb_substr($app->applicant?->full_name ?? '?', 0, 2) }}
                        @else
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="truncate text-sm font-medium text-gray-800">
                            @if($canViewSensitive)
                            {{ $app->applicant?->full_name }}
                            @else
                            <span class="italic text-gray-400">{{ __('dashboard.restricted') }}</span>
                            @endif
                        </p>
                        <p class="truncate text-xs text-gray-400">{{ $app->vacancy?->title }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="{{ $badgeClass }}">{{ $app->status->getLabel() }}</span>
                        <p class="mt-1 text-xs text-gray-400">{{ $app->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Right column --}}
        <div class="space-y-6">

            {{-- Open Vacancies --}}
            <div class="overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-1 rounded-full bg-brand"></span>
                        <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.open_vacancies') }}</h2>
                    </div>
                    <a href="{{ route('admin.vacancies.index') }}"
                       class="text-xs font-medium text-brand hover:text-brand-dark transition">{{ __('dashboard.actions.view_all') }} →</a>
                </div>
                @if($openVacancies->isEmpty())
                <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_open_vacancies') }}</p>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($openVacancies as $vacancy)
                    <div class="flex items-center justify-between px-5 py-3 hover:bg-brand-muted transition-colors">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-800">{{ $vacancy->title }}</p>
                            <p class="text-xs text-gray-400">{{ $vacancy->closing_date?->format('d M Y') }}</p>
                        </div>
                        <span class="ml-2 shrink-0 badge-blue">{{ $vacancy->applications_count }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Upcoming Schedules --}}
            <div class="overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-1 rounded-full bg-accent"></span>
                        <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.upcoming_schedules') }}</h2>
                    </div>
                    <a href="{{ route('admin.schedules.index') }}"
                       class="text-xs font-medium text-brand hover:text-brand-dark transition">{{ __('dashboard.actions.view_all') }} →</a>
                </div>
                @if($upcomingSchedules->isEmpty())
                <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_schedules') }}</p>
                @else
                <div class="divide-y divide-gray-50">
                    @foreach($upcomingSchedules as $schedule)
                    <div class="px-5 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ $schedule->title }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ $schedule->date?->format('d M Y') }} · {{ $schedule->start_time }}
                            @if($schedule->venue) · {{ $schedule->venue }} @endif
                        </p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Recent Activity ─────────────────────────────────────────────── --}}
    @if($canViewAudit)
    <div class="overflow-hidden rounded-xl bg-white" style="box-shadow: var(--shadow-card)">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div class="flex items-center gap-2">
                <span class="h-4 w-1 rounded-full bg-navy"></span>
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.recent_activity') }}</h2>
            </div>
            <a href="{{ route('admin.audit-logs.index') }}"
               class="text-xs font-medium text-brand hover:text-brand-dark transition">{{ __('dashboard.actions.view_all') }} →</a>
        </div>
        @if($recentActivity->isEmpty())
        <p class="px-5 py-6 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_activity') }}</p>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($recentActivity as $log)
            <div class="flex items-center gap-3 px-5 py-3">
                <span class="rounded-full bg-brand-muted px-2.5 py-0.5 text-xs font-medium text-brand">{{ $log->action }}</span>
                <span class="text-xs text-gray-500">{{ $log->module }}</span>
                @if($log->user)
                <span class="text-xs text-gray-400">{{ $log->user->name }}</span>
                @endif
                <span class="ml-auto text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

</div>
@endsection
