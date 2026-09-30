@extends('layouts.admin')

@section('title', __('dashboard.title'))

@section('content')
<div class="space-y-6">

    @php
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? __('messages.dash_morning') : ($hour < 17 ? __('messages.dash_afternoon') : __('messages.dash_evening'));
        $todo = array_values(array_filter([
            $stats['pending_screening'] > 0 && $user->hasPermissionTo('screening.view') ? [
                'n' => $stats['pending_screening'], 'tone' => 'bg-accent-muted text-accent-dark',
                'title' => __('messages.dash_todo_screening'), 'hint' => __('messages.dash_todo_screening_hint'), 'url' => route('admin.screening.index'),
            ] : null,
            $attention['closing_soon'] > 0 ? [
                'n' => $attention['closing_soon'], 'tone' => 'bg-accent-muted text-accent-dark',
                'title' => __('messages.dash_todo_closing'), 'hint' => __('messages.dash_todo_closing_hint'), 'url' => route('admin.vacancies.index'),
            ] : null,
            $attention['sessions_this_week'] > 0 ? [
                'n' => $attention['sessions_this_week'], 'tone' => 'bg-brand-muted text-brand-dark',
                'title' => __('messages.dash_todo_sessions'), 'hint' => __('messages.dash_todo_sessions_hint'), 'url' => route('admin.schedules.index'),
            ] : null,
            $attention['draft_announcements'] > 0 ? [
                'n' => $attention['draft_announcements'], 'tone' => 'bg-gray-100 text-gray-700',
                'title' => __('messages.dash_todo_drafts'), 'hint' => __('messages.dash_todo_drafts_hint'), 'url' => route('admin.announcements.index', ['state' => 'draft']),
            ] : null,
        ]));
        $firstName = \Illuminate\Support\Str::of($user->name)->before(' ');
    @endphp

    <x-admin.page-header :title="$greeting.', '.$firstName"
                         :description="et_date(now(), 'l, d F Y').' · '.trans_choice('messages.dash_open_vacancies', $stats['open_vacancies'], ['count' => $stats['open_vacancies']])">
        @if($user->hasPermissionTo('applications.view'))
        <a href="{{ route('admin.applications.index') }}" class="btn btn-secondary">{{ __('dashboard.quick_actions.view_applications') }}</a>
        @endif
        @if($user->hasPermissionTo('vacancies.create'))
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-secondary">{{ __('messages.add_announcement') }}</a>
        <a href="{{ route('admin.vacancies.create') }}" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4v16m8-8H4"/></svg>
            {{ __('dashboard.quick_actions.create_vacancy') }}
        </a>
        @endif
    </x-admin.page-header>

    {{-- ── Key numbers ─────────────────────────────────────────────────── --}}
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5" aria-label="{{ __('messages.report_summary') }}">
        <x-admin.stat :label="__('dashboard.kpi.open_vacancies')" :value="number_format($stats['open_vacancies'])"
                      :hint="trans_choice('messages.dash_of_total_vacancies', $stats['total_vacancies'], ['count' => number_format($stats['total_vacancies'])])"
                      :href="route('admin.vacancies.index')" />
        <x-admin.stat :label="__('dashboard.kpi.total_applicants')" :value="number_format($stats['total_applicants'])"
                      :hint="__('messages.dash_registered')" :href="route('admin.applicants.index')" />
        <x-admin.stat :label="__('dashboard.kpi.total_applications')" :value="number_format($stats['total_applications'])"
                      :hint="trans_choice('messages.dash_from_applicants', $stats['total_applicants'], ['count' => number_format($stats['total_applicants'])])"
                      :href="route('admin.applications.index')" />
        <x-admin.stat :label="__('dashboard.kpi.pending_screening')" :value="number_format($stats['pending_screening'])"
                      :tone="$stats['pending_screening'] > 0 ? 'warn' : 'good'"
                      :hint="$stats['pending_screening'] > 0 ? __('messages.dash_todo_screening_hint') : __('messages.dash_all_screened')"
                      :href="route('admin.screening.index')" />
        <x-admin.stat :label="__('dashboard.kpi.selected_applicants')" :value="number_format($stats['selected'])" tone="good"
                      :hint="trans_choice('messages.dash_passed_screening', $stats['passed_screening'], ['count' => number_format($stats['passed_screening'])])"
                      :href="route('admin.final-results.index')" />
    </section>

    {{-- ── Attention + pipeline ─────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-5">
        <section class="card overflow-hidden lg:col-span-2" aria-labelledby="todo-heading">
            <div class="card-header"><h2 id="todo-heading" class="card-title">{{ __('messages.dash_attention') }}</h2></div>
            @forelse($todo as $item)
            <a href="{{ $item['url'] }}" class="flex items-center gap-3 border-b border-gray-100 px-5 py-3.5 last:border-0 hover:bg-gray-50">
                <span class="flex h-8 min-w-10 items-center justify-center rounded-lg px-2 text-sm font-bold tabular-nums {{ $item['tone'] }}">{{ number_format($item['n']) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-gray-900">{{ $item['title'] }}</span>
                    <span class="block truncate text-[13px] text-gray-600">{{ $item['hint'] }}</span>
                </span>
                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
            @empty
            <div class="flex items-center gap-3 px-5 py-8 text-sm text-gray-700">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-green-50 text-green-700" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
                {{ __('messages.dash_all_clear') }}
            </div>
            @endforelse
        </section>

        <section class="card overflow-hidden lg:col-span-3" aria-labelledby="pipeline-heading">
            <div class="card-header">
                <div>
                    <h2 id="pipeline-heading" class="card-title">{{ __('dashboard.sections.pipeline') }}</h2>
                    <p class="card-description">{{ __('dashboard.pipeline_summary', ['count' => number_format($pipelineStages->sum('count')), 'stages' => $pipelineStages->count()]) }}</p>
                </div>
                @if($user->hasPermissionTo('reports.view'))
                <a href="{{ route('admin.reports.index') }}" class="link-action">{{ __('menus.reports') }} →</a>
                @endif
            </div>
            @if($pipelineStages->isEmpty())
            <x-admin.empty :text="__('dashboard.empty.no_pipeline_data')" />
            @else
            <div class="space-y-2.5 p-5">
                @foreach($pipelineStages as $stage)
                <div>
                    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                        <span class="text-gray-800">{{ $stage['label'] }}</span>
                        <span class="tabular-nums text-gray-600"><span class="font-semibold text-gray-900">{{ number_format($stage['count']) }}</span> · {{ $stage['pct'] }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                        <div class="{{ $stage['color'] }} h-full rounded-full" style="width: {{ max((float) $stage['pct'], $stage['count'] > 0 ? 1 : 0) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </section>
    </div>

    {{-- ── Demographics Row ─────────────────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Gender Distribution --}}
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.gender_distribution') }}</h2>
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
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.age_distribution') }}</h2>
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
                    <p class="text-xs text-gray-600">{{ __('dashboard.age.total_known_dob') }}: {{ number_format(array_sum($ageDist)) }}</p>
                </div>
            </div>
        </div>

        {{-- Disability Distribution (SVG Donut) --}}
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.disability_status') }}</h2>
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
                            <span class="text-xs font-normal text-gray-600">({{ $withoutPct }}%)</span>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-gray-600">{{ __('dashboard.disability.with') }}</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-800">
                            {{ number_format($disabilityDist['with']) }}
                            <span class="text-xs font-normal text-gray-600">({{ $withPct }}%)</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Exam & Interview Top Scorers ─────────────────────────────────── --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Exam Top Scorers --}}
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.exam_top_scorers') }}</h2>
                <p class="mt-0.5 text-xs text-gray-600">{{ __('dashboard.scores.top_10_exam') }}</p>
            </div>
            @if($examTopScorers->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_exam_scores') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($examTopScorers as $i => $scorer)
                @php $medals = ['🥇','🥈','🥉']; @endphp
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="w-6 shrink-0 text-center text-sm {{ $i < 3 ? 'font-bold' : 'text-gray-600 text-xs' }}">
                        {{ $i < 3 ? $medals[$i] : ($i + 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($canViewSensitive)
                        <p class="truncate text-sm font-medium text-gray-800">
                            {{ $scorer->application?->applicant?->full_name ?? '—' }}
                        </p>
                        @else
                        <p class="text-sm italic text-gray-600">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-600">{{ $scorer->schedule?->vacancy?->title ?? '—' }}</p>
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
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.interview_top_scorers') }}</h2>
                <p class="mt-0.5 text-xs text-gray-600">{{ __('dashboard.scores.top_10_interview') }}</p>
            </div>
            @if($interviewTopScorers->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_interview_scores') }}</p>
            @else
            <div class="divide-y divide-gray-50">
                @foreach($interviewTopScorers as $i => $scorer)
                @php $medals = ['🥇','🥈','🥉']; @endphp
                <div class="flex items-center gap-3 px-5 py-2.5">
                    <span class="w-6 shrink-0 text-center text-sm {{ $i < 3 ? 'font-bold' : 'text-gray-600 text-xs' }}">
                        {{ $i < 3 ? $medals[$i] : ($i + 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        @if($canViewSensitive)
                        <p class="truncate text-sm font-medium text-gray-800">
                            {{ $scorer->application?->applicant?->full_name ?? '—' }}
                        </p>
                        @else
                        <p class="text-sm italic text-gray-600">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-600">{{ $scorer->schedule?->vacancy?->title ?? '—' }}</p>
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
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.exam_by_gender') }}</h2>
            </div>
            @if($examPassByGender->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_exam_data') }}</p>
            @else
            <table class="w-full text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-medium text-gray-500">{{ __('dashboard.scores.gender') }}</th>
                        <th class="table-th-right">{{ __('dashboard.scores.count') }}</th>
                        <th class="table-th-right">{{ __('dashboard.scores.avg') }}</th>
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
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.final_results') }}</h2>
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
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.vacancy_load') }}</h2>
                <p class="mt-0.5 text-xs text-gray-600">{{ __('dashboard.scores.top_8_open') }}</p>
            </div>
            @if($vacancyLoad->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_open_vacancies') }}</p>
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
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.upcoming_schedules') }}</h2>
                <a href="{{ route('admin.schedules.index') }}"
                   class="link-action">{{ __('dashboard.actions.view_all') }} →</a>
            </div>
            @if($upcomingSchedules->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_schedules') }}</p>
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
                        <p class="truncate text-xs text-gray-600">
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
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h2 class="card-title">{{ __('dashboard.sections.recent_applications') }}</h2>
                <a href="{{ route('admin.applications.index') }}"
                   class="link-action">{{ __('dashboard.actions.view_all') }} →</a>
            </div>
            @if($recentApplications->isEmpty())
            <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('dashboard.empty.no_applications') }}</p>
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
                        <p class="truncate text-sm italic text-gray-600">{{ __('dashboard.restricted') }}</p>
                        @endif
                        <p class="truncate text-xs text-gray-600">{{ $app->vacancy?->title }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sClass }}">
                            {{ $app->status->getLabel() }}
                        </span>
                        <p class="mt-0.5 text-xs text-gray-600">{{ $app->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ── Recent Audit Activity ────────────────────────────────────────── --}}
    @if($canViewAudit && $recentActivity->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h2 class="card-title">{{ __('dashboard.sections.recent_activity') }}</h2>
            <a href="{{ route('admin.audit-logs.index') }}"
               class="link-action">{{ __('dashboard.actions.view_all') }} →</a>
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
                        <span class="mx-1 text-gray-600">·</span>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-gray-600">{{ $log->action }}</span>
                        <span class="mx-1 text-gray-600">on</span>
                        <span class="capitalize text-gray-600">{{ $log->module }}</span>
                    </p>
                </div>
                <span class="shrink-0 text-xs text-gray-600">{{ $log->created_at->diffForHumans() }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
