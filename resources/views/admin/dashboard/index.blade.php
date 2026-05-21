@extends('layouts.admin')

@section('title', __('dashboard.title'))

@section('content')
<div class="space-y-6">

    {{-- ── Page Header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ __('dashboard.title') }}</h1>
            <p class="mt-0.5 text-sm text-gray-500">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($user->hasPermissionTo('vacancies.create'))
            <a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary inline-flex items-center gap-1.5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('dashboard.quick_actions.create_vacancy') }}
            </a>
            @endif
            @if($user->hasPermissionTo('applications.view'))
            <a href="{{ route('admin.applications.index') }}" class="btn btn-outline inline-flex items-center gap-1.5">
                {{ __('dashboard.quick_actions.view_applications') }}
            </a>
            @endif
        </div>
    </div>

    {{-- ── 8 KPI Cards ─────────────────────────────────────────────────── --}}
    @php
    $kpiCards = [
        ['label' => __('dashboard.kpi.total_applicants'),   'value' => $stats['total_applicants'],   'dot' => 'bg-blue-500',    'iconBg' => 'bg-blue-100',    'iconColor' => 'text-blue-600',    'bar' => 'bg-blue-500',    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
        ['label' => __('dashboard.kpi.total_applications'), 'value' => $stats['total_applications'], 'dot' => 'bg-violet-500',   'iconBg' => 'bg-violet-100',   'iconColor' => 'text-violet-600',   'bar' => 'bg-violet-500',   'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => __('dashboard.kpi.open_vacancies'),     'value' => $stats['open_vacancies'],     'dot' => 'bg-emerald-500',  'iconBg' => 'bg-emerald-100',  'iconColor' => 'text-emerald-600',  'bar' => 'bg-emerald-500',  'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
        ['label' => __('dashboard.kpi.pending_screening'),  'value' => $stats['pending_screening'],  'dot' => 'bg-amber-500',    'iconBg' => 'bg-amber-100',    'iconColor' => 'text-amber-600',    'bar' => 'bg-amber-500',    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
        ['label' => __('dashboard.kpi.passed_screening'),   'value' => $stats['passed_screening'],   'dot' => 'bg-teal-500',     'iconBg' => 'bg-teal-100',     'iconColor' => 'text-teal-600',     'bar' => 'bg-teal-500',     'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => __('dashboard.kpi.selected_applicants'), 'value' => $stats['selected'],            'dot' => 'bg-green-500',    'iconBg' => 'bg-green-100',    'iconColor' => 'text-green-600',    'bar' => 'bg-green-500',    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
        ['label' => __('dashboard.kpi.total_vacancies'),    'value' => $stats['total_vacancies'],    'dot' => 'bg-slate-500',    'iconBg' => 'bg-slate-100',    'iconColor' => 'text-slate-600',    'bar' => 'bg-slate-400',    'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
        ['label' => __('dashboard.kpi.closed_vacancies'),   'value' => $stats['closed_vacancies'],   'dot' => 'bg-rose-500',     'iconBg' => 'bg-rose-100',     'iconColor' => 'text-rose-600',     'bar' => 'bg-rose-500',     'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
    ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($kpiCards as $card)
        <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="flex flex-1 items-start justify-between p-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full {{ $card['dot'] }}"></span>
                        <p class="text-xs font-medium text-gray-500">{{ $card['label'] }}</p>
                    </div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">
                        {{ number_format($card['value']) }}
                    </p>
                </div>
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $card['iconBg'] }}">
                    <svg class="h-5 w-5 {{ $card['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <div class="h-1 w-full {{ $card['bar'] }}"></div>
        </div>
        @endforeach
    </div>

    {{-- ── Application Pipeline ─────────────────────────────────────────── --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.pipeline') }}</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                {{ __('dashboard.pipeline_summary', ['count' => number_format($pipelineStages->sum('count')), 'stages' => $pipelineStages->count()]) }}
            </p>
        </div>
        @if($pipelineStages->isEmpty())
        <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_pipeline_data') }}</p>
        @else
        <div class="p-5 space-y-2.5">
            @foreach($pipelineStages as $stage)
            <div class="flex items-center gap-3">
                <span class="w-40 shrink-0 text-right text-xs text-gray-600">{{ $stage['label'] }}</span>
                <div class="flex-1 h-5 overflow-hidden rounded-full bg-gray-100">
                    <div class="{{ $stage['color'] }} h-full rounded-full transition-all duration-500"
                         style="width: {{ max((float)$stage['pct'], $stage['count'] > 0 ? 0.5 : 0) }}%"></div>
                </div>
                <span class="w-20 shrink-0 text-xs text-gray-700">
                    {{ number_format($stage['count']) }}
                    <span class="text-gray-400">({{ $stage['pct'] }}%)</span>
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Demographics Row ─────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Gender Distribution --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.gender_distribution') }}</h2>
            </div>
            <div class="p-5 space-y-5">
                @php
                $genderGroups = [
                    ['label' => __('dashboard.gender.all_applicants'),   'data' => $genderDist,    'total' => $genderTotal],
                    ['label' => __('dashboard.gender.passed_screening'), 'data' => $genderPassed,  'total' => max(array_sum($genderPassed), 1)],
                    ['label' => __('dashboard.gender.selected'),         'data' => $genderSelected,'total' => max(array_sum($genderSelected), 1)],
                ];
                $gColors = ['male' => 'bg-blue-500', 'female' => 'bg-pink-500', 'unknown' => 'bg-gray-300'];
                @endphp
                @foreach($genderGroups as $group)
                <div>
                    <p class="mb-2 text-xs font-medium text-gray-500">{{ $group['label'] }}</p>
                    @foreach(['male', 'female', 'unknown'] as $g)
                    @php $cnt = $group['data'][$g] ?? 0; $pct = $cnt > 0 ? round($cnt / $group['total'] * 100, 1) : 0; @endphp
                    @if($cnt > 0)
                    <div class="mb-1 flex items-center gap-2">
                        <span class="w-14 text-xs text-gray-500">{{ __('dashboard.gender.'.$g) }}</span>
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-100">
                            <div class="{{ $gColors[$g] }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-20 text-right text-xs text-gray-600">{{ $cnt }} ({{ $pct }}%)</span>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>

        {{-- Age Distribution --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.age_distribution') }}</h2>
            </div>
            <div class="p-5 space-y-3">
                @php
                $ageColors = [
                    'Under 25' => 'bg-blue-400',
                    '25–30'    => 'bg-indigo-400',
                    '31–35'    => 'bg-violet-400',
                    '36–40'    => 'bg-purple-400',
                    'Over 40'  => 'bg-rose-400',
                ];
                @endphp
                @foreach($ageDist as $label => $count)
                @php $pct = $count > 0 ? round($count / $ageTotal * 100, 1) : 0; @endphp
                <div class="flex items-center gap-3">
                    <span class="w-20 shrink-0 text-xs text-gray-600">{{ $label }}</span>
                    <div class="h-4 flex-1 overflow-hidden rounded-full bg-gray-100">
                        <div class="{{ $ageColors[$label] ?? 'bg-gray-400' }} h-full rounded-full"
                             style="width: {{ $count > 0 ? max($pct, 1) : 0 }}%"></div>
                    </div>
                    <span class="w-10 shrink-0 text-right text-xs font-medium text-gray-600">{{ $count }}</span>
                </div>
                @endforeach
                <div class="mt-2 border-t border-gray-100 pt-2">
                    <p class="text-xs text-gray-400">{{ __('dashboard.age.total_known_dob') }}: {{ number_format(array_sum($ageDist)) }}</p>
                </div>
            </div>
        </div>

        {{-- Disability Distribution (SVG Donut) --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.disability_status') }}</h2>
            </div>
            <div class="flex flex-col items-center justify-center p-5">
                @php
                $dTotal     = max($disabilityDist['with'] + $disabilityDist['without'], 1);
                $withPct    = round($disabilityDist['with'] / $dTotal * 100, 1);
                $withoutPct = round($disabilityDist['without'] / $dTotal * 100, 1);
                @endphp
                {{-- r=15.9155 → circumference ≈ 100 --}}
                <svg viewBox="0 0 42 42" class="-rotate-90 h-32 w-32">
                    <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#e5e7eb" stroke-width="5"/>
                    @if($disabilityDist['without'] > 0)
                    <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#6366f1" stroke-width="5"
                            stroke-dasharray="{{ $withoutPct }} {{ 100 - $withoutPct }}"
                            stroke-dashoffset="0"/>
                    @endif
                    @if($disabilityDist['with'] > 0)
                    <circle cx="21" cy="21" r="15.9155" fill="none" stroke="#f59e0b" stroke-width="5"
                            stroke-dasharray="{{ $withPct }} {{ 100 - $withPct }}"
                            stroke-dashoffset="-{{ $withoutPct }}"/>
                    @endif
                </svg>
                <div class="mt-4 w-full space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-indigo-500"></span>
                            <span class="text-sm text-gray-600">{{ __('dashboard.disability.without') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-800">
                            {{ number_format($disabilityDist['without']) }}
                            <span class="text-xs font-normal text-gray-400">({{ $withoutPct }}%)</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-gray-600">{{ __('dashboard.disability.with') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-800">
                            {{ number_format($disabilityDist['with']) }}
                            <span class="text-xs font-normal text-gray-400">({{ $withPct }}%)</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Exam & Interview Top Scorers ─────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Exam Top Scorers --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.exam_top_scorers') }}</h2>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('dashboard.scores.top_10_exam') }}</p>
            </div>
            @if($examTopScorers->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_exam_scores') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($examTopScorers as $i => $scorer)
                @php $medals = ['🥇','🥈','🥉']; @endphp
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="w-6 shrink-0 text-center text-sm {{ $i < 3 ? 'font-bold' : 'text-gray-400 text-xs' }}">
                        {{ $i < 3 ? $medals[$i] : ($i + 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($canViewSensitive)
                        <p class="truncate text-sm font-medium text-gray-800">
                            {{ $scorer->application?->applicant?->full_name ?? '—' }}
                        </p>
                        @else
                        <p class="text-sm italic text-gray-400">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-400">{{ $scorer->schedule?->vacancy?->title ?? '—' }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="text-sm font-bold text-violet-700">{{ $scorer->score }}</span>
                        <div class="mt-1 h-1.5 w-16 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-violet-500"
                                 style="width: {{ min((float)$scorer->score, 100) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Interview Top Scorers --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.interview_top_scorers') }}</h2>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('dashboard.scores.top_10_interview') }}</p>
            </div>
            @if($interviewTopScorers->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_interview_scores') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($interviewTopScorers as $i => $scorer)
                @php $medals = ['🥇','🥈','🥉']; @endphp
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="w-6 shrink-0 text-center text-sm {{ $i < 3 ? 'font-bold' : 'text-gray-400 text-xs' }}">
                        {{ $i < 3 ? $medals[$i] : ($i + 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($canViewSensitive)
                        <p class="truncate text-sm font-medium text-gray-800">
                            {{ $scorer->application?->applicant?->full_name ?? '—' }}
                        </p>
                        @else
                        <p class="text-sm italic text-gray-400">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-400">{{ $scorer->schedule?->vacancy?->title ?? '—' }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="text-sm font-bold text-cyan-700">{{ $scorer->score }}</span>
                        <div class="mt-1 h-1.5 w-16 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-cyan-500"
                                 style="width: {{ min((float)$scorer->score, 100) }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ── Exam by Gender · Final Results · Vacancy Load ──────────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Exam Performance by Gender --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.exam_by_gender') }}</h2>
            </div>
            @if($examPassByGender->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_exam_data') }}</p>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500">{{ __('dashboard.scores.gender') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ __('dashboard.scores.count') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ __('dashboard.scores.avg') }}</th>
                        <th class="px-5 py-2 text-right text-xs font-medium text-gray-500">{{ __('dashboard.scores.max') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($examPassByGender as $gender => $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-2.5 font-medium text-gray-700">{{ __('dashboard.gender.'.$gender) }}</td>
                        <td class="px-3 py-2.5 text-right text-gray-600">{{ $row->total }}</td>
                        <td class="px-3 py-2.5 text-right font-semibold text-violet-700">{{ $row->avg_score }}</td>
                        <td class="px-5 py-2.5 text-right text-gray-600">{{ $row->max_score }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Final Results Overview --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.final_results') }}</h2>
            </div>
            <div class="grid grid-cols-2 gap-4 p-5">
                <div class="rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($finalResultStats['total']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.scores.total_records') }}</p>
                </div>
                <div class="rounded-lg bg-violet-50 p-4 text-center">
                    <p class="text-2xl font-bold text-violet-700">{{ $finalResultStats['avg_exam'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.scores.avg_exam') }}</p>
                </div>
                <div class="rounded-lg bg-cyan-50 p-4 text-center">
                    <p class="text-2xl font-bold text-cyan-700">{{ $finalResultStats['avg_int'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.scores.avg_interview') }}</p>
                </div>
                <div class="rounded-lg bg-green-50 p-4 text-center">
                    <p class="text-2xl font-bold text-green-700">{{ $finalResultStats['avg_fin'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.scores.avg_final') }}</p>
                </div>
            </div>
        </div>

        {{-- Applications per Vacancy --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.vacancy_load') }}</h2>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('dashboard.scores.top_8_open') }}</p>
            </div>
            @if($vacancyLoad->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_open_vacancies') }}</p>
            @else
            @php $maxLoad = $vacancyLoad->max('applications_count') ?: 1; @endphp
            <div class="space-y-3 p-4">
                @foreach($vacancyLoad as $v)
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <span class="max-w-40 truncate text-xs text-gray-700">{{ $v->title }}</span>
                        <span class="ml-2 text-xs font-semibold text-gray-800">{{ $v->applications_count }}</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full bg-brand"
                             style="width: {{ round($v->applications_count / $maxLoad * 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ── Upcoming Schedules · Recent Applications ────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Upcoming Schedules --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.upcoming_schedules') }}</h2>
                <a href="{{ route('admin.schedules.index') }}"
                   class="text-xs font-medium text-brand hover:underline">{{ __('dashboard.actions.view_all') }} →</a>
            </div>
            @if($upcomingSchedules->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_schedules') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($upcomingSchedules as $schedule)
                <div class="flex items-start gap-4 px-5 py-3">
                    <div class="min-w-12 shrink-0 rounded-lg bg-brand-muted px-2.5 py-1.5 text-center">
                        <p class="text-xs font-bold text-brand">{{ $schedule->date?->format('d') }}</p>
                        <p class="text-xs text-brand/70">{{ $schedule->date?->format('M') }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800">{{ $schedule->title }}</p>
                        <p class="truncate text-xs text-gray-400">
                            {{ $schedule->start_time }}
                            @if($schedule->venue) · {{ $schedule->venue }} @endif
                            @if($schedule->vacancy) · {{ $schedule->vacancy->title }} @endif
                        </p>
                    </div>
                    @if($schedule->type)
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                        {{ $schedule->type->value === 'exam' ? 'bg-violet-100 text-violet-700' : 'bg-cyan-100 text-cyan-700' }}">
                        {{ __('dashboard.schedule.'.$schedule->type->value) }}
                    </span>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Recent Applications --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.recent_applications') }}</h2>
                <a href="{{ route('admin.applications.index') }}"
                   class="text-xs font-medium text-brand hover:underline">{{ __('dashboard.actions.view_all') }} →</a>
            </div>
            @if($recentApplications->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-400">{{ __('dashboard.empty.no_applications') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($recentApplications as $app)
                @php
                $sColors = [
                    'submitted'             => 'bg-blue-100 text-blue-700',
                    'under_review'          => 'bg-indigo-100 text-indigo-700',
                    'correction_required'   => 'bg-amber-100 text-amber-700',
                    'passed_screening'      => 'bg-teal-100 text-teal-700',
                    'failed_screening'      => 'bg-red-100 text-red-700',
                    'shortlisted_exam'      => 'bg-violet-100 text-violet-700',
                    'shortlisted_interview' => 'bg-cyan-100 text-cyan-700',
                    'selected'              => 'bg-green-100 text-green-700',
                    'waitlisted'            => 'bg-yellow-100 text-yellow-700',
                    'not_selected'          => 'bg-rose-100 text-rose-700',
                ];
                $sClass = $sColors[$app->status->value] ?? 'bg-gray-100 text-gray-600';
                @endphp
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-muted text-xs font-bold text-brand">
                        @if($canViewSensitive)
                        {{ mb_strtoupper(mb_substr($app->applicant?->full_name ?? '?', 0, 2)) }}
                        @else ?
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        @if($canViewSensitive)
                        <p class="truncate text-sm font-medium text-gray-800">{{ $app->applicant?->full_name }}</p>
                        @else
                        <p class="truncate text-sm italic text-gray-400">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-400">{{ $app->vacancy?->title }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sClass }}">
                            {{ $app->status->getLabel() }}
                        </span>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $app->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ── Recent Audit Activity ────────────────────────────────────────── --}}
    @if($canViewAudit && $recentActivity->isNotEmpty())
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200/60">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-800">{{ __('dashboard.sections.recent_activity') }}</h2>
            <a href="{{ route('admin.audit-logs.index') }}"
               class="text-xs font-medium text-brand hover:underline">{{ __('dashboard.actions.view_all') }} →</a>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($recentActivity as $log)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                    {{ mb_strtoupper(mb_substr($log->user?->name ?? 'S', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-700">
                        <span class="font-medium">{{ $log->user?->name ?? __('dashboard.system') }}</span>
                        <span class="mx-1 text-gray-400">·</span>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-600">{{ $log->action }}</span>
                        <span class="mx-1 text-gray-400">on</span>
                        <span class="capitalize text-gray-600">{{ $log->module }}</span>
                    </p>
                </div>
                <span class="shrink-0 text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
