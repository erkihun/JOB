@extends('layouts.admin')
@section('title', __('admin.reports_center.title'))
@section('content')
@php
    $locale = app()->getLocale();
    $vacancyTitle = fn ($v) => $v ? ($v->getTranslation('title', $locale, false) ?: $v->getTranslation('title', 'en', false)) : '—';
    $selectedVacancy = isset($filters['vacancy_id']) ? $vacancies->firstWhere('id', $filters['vacancy_id']) : null;
    $selectedStatus = isset($filters['status']) ? \App\Enums\ApplicationStatus::tryFrom($filters['status']) : null;
    $activeFilters = array_filter([
        $selectedVacancy ? ['label' => __('menus.vacancies'), 'value' => $selectedVacancy->code.' — '.$vacancyTitle($selectedVacancy), 'key' => 'vacancy_id'] : null,
        $selectedStatus ? ['label' => __('vacancies.status'), 'value' => $selectedStatus->getLabel(), 'key' => 'status'] : null,
        isset($filters['date_from']) ? ['label' => __('messages.date_from'), 'value' => et_date($filters['date_from']), 'key' => 'date_from'] : null,
        isset($filters['date_until']) ? ['label' => __('messages.date_until'), 'value' => et_date($filters['date_until']), 'key' => 'date_until'] : null,
    ]);
    $tone = fn (\App\Enums\ApplicationStatus $s) => match ($s->value) {
        'submitted', 'under_review', 'correction_required' => 'bg-amber-500',
        'failed_screening', 'not_selected' => 'bg-red-500',
        'selected' => 'bg-green-600',
        'withdrawn' => 'bg-gray-400',
        default => 'bg-brand',
    };
    $kpiCards = [
        'total'     => [__('dashboard.kpi.total_applications'), null, 'text-gray-900', 'bg-brand'],
        'in_review' => [__('messages.report_kpi_in_review'), __('messages.report_rate_of_total'), 'text-amber-700', 'bg-amber-500'],
        'passed'    => [__('dashboard.kpi.passed_screening'), __('messages.report_rate_pass'), 'text-green-700', 'bg-green-600'],
        'failed'    => [__('dashboard.kpi.failed_screening'), __('messages.report_rate_fail'), 'text-red-700', 'bg-red-500'],
        'selected'  => [__('messages.report_kpi_selected'), __('messages.report_rate_of_total'), 'text-brand', 'bg-brand'],
    ];
    $groupLabels = [
        'applicants' => __('messages.report_group_applicants'),
        'screening'  => __('messages.report_group_screening'),
        'assessment' => __('messages.report_group_assessment'),
        'system'     => __('messages.report_group_system'),
    ];
    $exportQuery = $filters;
@endphp

<div class="space-y-6">

    <x-admin.page-header :title="__('admin.reports_center.title')"
                         :description="__('messages.report_intro')"
                         :crumbs="[['label' => __('menus.reports')], ['label' => __('admin.reports_center.title')]]">
        @if($reports->has('applicants'))
        <a href="{{ route('admin.reports.export', ['report' => 'applicants'] + $exportQuery) }}" class="btn btn-primary" data-admin-no-spa>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            {{ __('messages.report_export_view') }}
        </a>
        @endif
    </x-admin.page-header>

    {{-- ── Filters ── --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="card card-body">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1.3fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
            <div>
                <label for="vacancy_id" class="form-label">{{ __('menus.vacancies') }}</label>
                <select id="vacancy_id" name="vacancy_id" class="form-select">
                    <option value="">{{ __('messages.all_vacancies') }}</option>
                    @foreach($vacancies as $v)
                    <option value="{{ $v->id }}" @selected(($filters['vacancy_id'] ?? '') == $v->id)>{{ $v->code }} — {{ $vacancyTitle($v) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="form-label">{{ __('vacancies.status') }}</label>
                <select id="status" name="status" class="form-select">
                    <option value="">{{ __('messages.all_statuses') }}</option>
                    @foreach($statuses as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            @if($locale === 'am')
                <x-ethiopian-datepicker name="date_from" :label="__('messages.date_from')" :value="$filters['date_from'] ?? null"/>
                <x-ethiopian-datepicker name="date_until" :label="__('messages.date_until')" :value="$filters['date_until'] ?? null"/>
            @else
                <div>
                    <label for="date_from" class="form-label">{{ __('messages.date_from') }}</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-input">
                </div>
                <div>
                    <label for="date_until" class="form-label">{{ __('messages.date_until') }}</label>
                    <input type="date" id="date_until" name="date_until" value="{{ $filters['date_until'] ?? '' }}" class="form-input">
                </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1 justify-center lg:flex-none">{{ __('messages.filter') }}</button>
                @if($activeFilters)
                <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
                @endif
            </div>
        </div>

        @if($activeFilters)
        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 text-sm">
            <span class="font-semibold text-gray-600">{{ __('messages.report_showing') }}</span>
            @foreach($activeFilters as $f)
            <a href="{{ route('admin.reports.index', array_diff_key($filters, [$f['key'] => true])) }}"
               class="inline-flex items-center gap-1.5 rounded-full bg-brand-muted py-1 pl-3 pr-2 font-semibold text-brand-dark hover:bg-brand/15"
               title="{{ __('public.remove_filter') }}">
                <span class="text-brand-dark/70">{{ $f['label'] }}:</span> {{ $f['value'] }}
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </a>
            @endforeach
        </div>
        @endif
    </form>

    {{-- ── KPIs ── --}}
    <section aria-label="{{ __('messages.report_summary') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($kpiCards as $key => [$label, $rateLabel, $valueColor, $bar])
        <div class="card overflow-hidden">
            <div class="h-1 {{ $bar }}"></div>
            <div class="p-5">
                <p class="text-sm font-semibold text-gray-600">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold tabular-nums {{ $valueColor }}">{{ number_format($kpis[$key]['value']) }}</p>
                @if($rateLabel !== null)
                <p class="mt-1 text-sm text-gray-600"><span class="font-bold text-gray-800">{{ rtrim(rtrim(number_format($kpis[$key]['rate'], 1), '0'), '.') }}%</span> {{ $rateLabel }}</p>
                @else
                <p class="mt-1 text-sm text-gray-600">{{ trans_choice('messages.report_in_selection', $pipeline->count(), ['count' => $pipeline->count()]) }}</p>
                @endif
            </div>
        </div>
        @endforeach
    </section>

    @if($total === 0)
        <div class="card">
            <x-admin.empty :title="__('messages.report_no_data')" :text="__('messages.report_no_data_hint')" />
        </div>
    @else
    {{-- ── Pipeline + gender ── --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card card-body lg:col-span-2" aria-labelledby="pipeline-heading">
            <div class="mb-4 flex items-baseline justify-between gap-3">
                <h2 id="pipeline-heading" class="card-title">{{ __('messages.report_pipeline') }}</h2>
                <span class="text-sm text-gray-500">{{ trans_choice('messages.report_applications_count', $total, ['count' => number_format($total)]) }}</span>
            </div>
            <ul class="space-y-3">
                @foreach($pipeline as $row)
                <li>
                    <a href="{{ route('admin.reports.index', array_merge($filters, ['status' => $row['status']->value])) }}" class="group block">
                        <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-gray-800 group-hover:text-brand">{{ $row['status']->getLabel() }}</span>
                            <span class="tabular-nums text-gray-600"><span class="font-bold text-gray-900">{{ number_format($row['count']) }}</span> · {{ rtrim(rtrim(number_format($row['pct'], 1), '0'), '.') }}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full {{ $tone($row['status']) }}" style="width: {{ max(1.5, $row['pct']) }}%"></div>
                        </div>
                    </a>
                </li>
                @endforeach
            </ul>
        </section>

        <section class="card card-body" aria-labelledby="gender-heading">
            <h2 id="gender-heading" class="card-title mb-4">{{ __('messages.report_gender') }}</h2>
            @php $genderColors = ['male' => 'bg-brand', 'female' => 'bg-accent', 'other' => 'bg-gray-500', 'unknown' => 'bg-gray-300']; @endphp
            <div class="mb-4 flex h-3 overflow-hidden rounded-full bg-gray-100" aria-hidden="true">
                @foreach($gender as $g)
                <div class="{{ $genderColors[$g['gender']] ?? 'bg-gray-300' }}" style="width: {{ $g['pct'] }}%"></div>
                @endforeach
            </div>
            <ul class="space-y-3">
                @foreach($gender as $g)
                <li class="flex items-center justify-between gap-3 text-sm">
                    <span class="flex items-center gap-2 font-semibold text-gray-800">
                        <span class="h-3 w-3 rounded-sm {{ $genderColors[$g['gender']] ?? 'bg-gray-300' }}" aria-hidden="true"></span>
                        {{ in_array($g['gender'], ['male', 'female', 'other'], true) ? __('statuses.gender.'.$g['gender']) : __('messages.report_not_specified') }}
                    </span>
                    <span class="tabular-nums text-gray-600"><span class="font-bold text-gray-900">{{ number_format($g['count']) }}</span> · {{ $g['pct'] }}%</span>
                </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- ── Trend ── --}}
    <section class="card card-body" aria-labelledby="trend-heading">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
            <h2 id="trend-heading" class="card-title">{{ $trend['weekly'] ? __('messages.report_trend_weekly') : __('messages.report_trend_daily') }}</h2>
            <span class="text-sm text-gray-500">{{ et_date($trend['from']) }} – {{ et_date($trend['until']) }} · {{ trans_choice('messages.report_applications_count', $trend['sum'], ['count' => number_format($trend['sum'])]) }}</span>
        </div>
        @if($trend['sum'] === 0)
            <p class="py-8 text-center text-sm text-gray-500">{{ __('messages.report_no_trend') }}</p>
        @else
        @php $every = max(1, (int) ceil(count($trend['points']) / 8)); @endphp
        <div class="flex h-44 items-end gap-0.75" role="img"
             aria-label="{{ __('messages.report_trend_daily') }}: {{ trans_choice('messages.report_applications_count', $trend['sum'], ['count' => $trend['sum']]) }}">
            @foreach($trend['points'] as $p)
            <div class="group relative flex h-full flex-1 items-end">
                <div class="w-full rounded-t {{ $p['count'] > 0 ? 'bg-brand group-hover:bg-brand-dark' : 'bg-gray-100' }}"
                     style="height: {{ $p['count'] > 0 ? max(4, round($p['count'] / $trend['max'] * 100)) : 2 }}%"></div>
                <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-semibold text-white group-hover:block">
                    {{ et_date($p['date']) }}: {{ $p['count'] }}
                </span>
            </div>
            @endforeach
        </div>
        <div class="mt-2 flex gap-0.75 text-[11px] text-gray-500" aria-hidden="true">
            @foreach($trend['points'] as $i => $p)
            <span class="flex-1 truncate text-center">{{ $i % $every === 0 ? et_date($p['date'], 'M d') : '' }}</span>
            @endforeach
        </div>
        @endif
    </section>

    {{-- ── By vacancy ── --}}
    <section class="card overflow-hidden" aria-labelledby="vacancy-heading">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <h2 id="vacancy-heading" class="card-title">{{ __('messages.report_by_vacancy') }}</h2>
            @if($reports->has('vacancy-wise'))
            <a href="{{ route('admin.reports.export', ['report' => 'vacancy-wise'] + $exportQuery) }}" class="btn btn-secondary btn-sm" data-admin-no-spa>{{ __('messages.report_download_excel') }}</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('menus.vacancies') }}</th>
                        <th class="table-th text-right">{{ __('messages.report_col_applications') }}</th>
                        <th class="table-th text-right">{{ __('messages.pass') }}</th>
                        <th class="table-th text-right">{{ __('messages.fail') }}</th>
                        <th class="table-th w-56">{{ __('messages.report_col_pass_rate') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($byVacancy as $row)
                    <tr class="table-row">
                        <td class="table-td">
                            <a href="{{ route('admin.reports.index', array_merge($filters, ['vacancy_id' => $row['vacancy']?->id])) }}" class="font-semibold text-gray-900 hover:text-brand">{{ $vacancyTitle($row['vacancy']) }}</a>
                            <p class="font-mono text-xs text-gray-500">{{ $row['vacancy']?->code }}</p>
                        </td>
                        <td class="table-td text-right font-bold tabular-nums text-gray-900">{{ number_format($row['total']) }}</td>
                        <td class="table-td text-right tabular-nums text-green-700">{{ number_format($row['passed']) }}</td>
                        <td class="table-td text-right tabular-nums text-red-700">{{ number_format($row['failed']) }}</td>
                        <td class="table-td">
                            @if($row['pass_rate'] === null)
                                <span class="text-gray-500">{{ __('messages.report_not_screened') }}</span>
                            @else
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-red-100">
                                    <div class="h-full rounded-full bg-green-600" style="width: {{ $row['pass_rate'] }}%"></div>
                                </div>
                                <span class="w-10 text-right font-bold tabular-nums text-gray-800">{{ $row['pass_rate'] }}%</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

    {{-- ── Report library ── --}}
    @if($reports->isNotEmpty())
    <section aria-labelledby="library-heading">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 id="library-heading" class="text-lg font-bold text-gray-900">{{ __('messages.report_library') }}</h2>
                <p class="text-sm text-gray-600">{{ __('messages.report_library_hint') }}</p>
            </div>
        </div>
        <div class="space-y-5">
            @foreach($reports->groupBy('group') as $group => $items)
            <div>
                <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">{{ $groupLabels[$group] ?? $group }}</h3>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($items as $report)
                    <div class="card flex items-start gap-4 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-muted text-brand" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900">{{ $report['title'] }}</p>
                            <p class="mt-0.5 text-sm leading-snug text-gray-600">{{ $report['description'] }}</p>
                            <a href="{{ route('admin.reports.export', ['report' => $report['key']] + $exportQuery) }}" data-admin-no-spa
                               class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-brand hover:underline">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                                {{ __('messages.report_download_excel') }}
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Applications ── --}}
    <section class="card overflow-hidden" aria-labelledby="apps-heading">
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
            <h2 id="apps-heading" class="card-title">{{ __('messages.report_applications') }}</h2>
            <span class="text-sm text-gray-500">{{ trans_choice('messages.report_applications_count', $applications->total(), ['count' => number_format($applications->total())]) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('messages.applicant') }}</th>
                        <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                        <th class="table-th hidden md:table-cell">{{ __('messages.reference') }}</th>
                        <th class="table-th">{{ __('vacancies.status') }}</th>
                        <th class="table-th hidden lg:table-cell">{{ __('messages.submitted') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($applications as $app)
                    <tr class="table-row">
                        <td class="table-td font-medium text-gray-900">{{ $app->applicant?->full_name }}</td>
                        <td class="table-td hidden text-gray-700 sm:table-cell">
                            {{ $vacancyTitle($app->vacancy) }}
                            @if($app->vacancy?->announcement)
                                <p class="mt-0.5 text-xs text-gray-500">{{ et_date($app->vacancy->announcement->opening_date) }} – {{ et_date($app->vacancy->announcement->closing_date) }}</p>
                            @endif
                        </td>
                        <td class="table-td hidden font-mono text-xs text-gray-600 md:table-cell">{{ $app->reference_number }}</td>
                        <td class="table-td"><x-admin.status :status="$app->status" /></td>
                        <td class="table-td hidden text-gray-600 lg:table-cell">{{ et_date($app->created_at) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5"><x-admin.empty /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
        <div class="border-t border-gray-100 px-4 py-3">{{ $applications->links() }}</div>
        @endif
    </section>
</div>
@endsection
