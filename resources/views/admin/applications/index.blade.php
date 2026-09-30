@extends('layouts.admin')
@section('title', __('menus.applications'))

@section('content')
@php
    $stage = request('stage');
    $tabs = [
        'all'        => __('messages.all'),
        'awaiting'   => __('messages.report_kpi_in_review'),
        'passed'     => __('dashboard.kpi.passed_screening'),
        'failed'     => __('dashboard.kpi.failed_screening'),
        'assessment' => __('menus.exams_interviews'),
        'selected'   => __('messages.report_kpi_selected'),
    ];
    $filtersActive = request()->hasAny(['search', 'vacancy_id', 'institution_id', 'status']);
@endphp
<div class="space-y-6">
    <x-admin.page-header :title="__('menus.applications')"
                         :description="__('messages.apps_intro')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.applications')]]">
        @can('screening.view')
        <a href="{{ route('admin.screening.index') }}" class="btn btn-primary">{{ __('messages.apps_open_queue') }}</a>
        @endcan
    </x-admin.page-header>

    {{-- Stage tabs --}}
    <nav class="tabs" aria-label="{{ __('vacancies.status') }}">
        @foreach($tabs as $key => $label)
        @php $isActive = ($key === 'all' && ! $stage) || $stage === $key; @endphp
        <a href="{{ route('admin.applications.index', array_filter(array_merge(request()->except(['stage', 'page', 'status']), ['stage' => $key === 'all' ? null : $key]))) }}"
           class="tab {{ $isActive ? 'tab-active' : '' }}" @if($isActive) aria-current="page" @endif>
            {{ $label }} <span class="tab-count">{{ number_format($stageCounts[$key] ?? 0) }}</span>
        </a>
        @endforeach
    </nav>

    <x-admin.filters :reset="route('admin.applications.index', array_filter(['stage' => $stage]))" :active="$filtersActive">
        @if($stage)<input type="hidden" name="stage" value="{{ $stage }}">@endif
        <div class="lg:col-span-2">
            <label for="f-search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="f-search" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('messages.search_applicants_placeholder') }}" class="form-input">
        </div>
        <div>
            <label for="f-vacancy" class="form-label">{{ __('menus.vacancies') }}</label>
            <select id="f-vacancy" name="vacancy_id" class="form-select">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach($vacancies as $v)
                <option value="{{ $v->id }}" @selected(request('vacancy_id') === $v->id)>{{ $v->code }} — {{ $v->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-institution" class="form-label">{{ __('admin.institution_name') }}</label>
            <select id="f-institution" name="institution_id" class="form-select">
                <option value="">{{ __('applications.filter_by_institution') }}</option>
                @foreach($institutions as $inst)
                <option value="{{ $inst->id }}" @selected(request('institution_id') === $inst->id)>{{ $inst->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-status" class="form-label">{{ __('vacancies.status') }}</label>
            <select id="f-status" name="status" class="form-select">
                <option value="">{{ __('messages.all_statuses') }}</option>
                @foreach($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->getLabel() }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $applications->total(), ['count' => number_format($applications->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('messages.reference') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('messages.submitted') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($applications as $app)
                <tr class="table-row">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :name="$app->applicant?->full_name" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.applications.show', $app) }}" class="block truncate font-semibold text-gray-900 hover:text-brand">{{ $app->applicant?->full_name }}</a>
                                <p class="text-[13px] text-gray-600">{{ $canViewSensitive ? ($app->applicant?->phone ?? '—') : __('dashboard.restricted') }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="table-td hidden sm:table-cell">
                        <p class="text-gray-900">{{ $app->vacancy?->title }}</p>
                        <p class="text-[13px] text-gray-600"><span class="font-mono">{{ $app->vacancy?->code }}</span>@if($app->vacancy?->institution) · {{ $app->vacancy->institution->displayName() }}@endif</p>
                    </td>
                    <td class="table-td hidden font-mono text-[13px] text-gray-700 md:table-cell">{{ $app->reference_number }}</td>
                    <td class="table-td"><x-admin.status :status="$app->status" /></td>
                    <td class="table-td table-td-muted hidden tabular-nums lg:table-cell">{{ et_date($app->created_at) }}</td>
                    <td class="table-td">
                        <div class="table-actions">
                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a>
                            @can('screening.view')
                            <x-admin.row-menu>
                                <a href="{{ route('admin.screening.review', $app) }}" class="menu-item">{{ __('messages.review_application') }}</a>
                                @if($app->applicant)
                                <a href="{{ route('admin.applicants.show', $app->applicant) }}" class="menu-item">{{ __('messages.apps_view_applicant') }}</a>
                                @endif
                            </x-admin.row-menu>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-admin.empty :text="$filtersActive || $stage ? __('messages.empty_filtered') : __('messages.apps_empty_hint')" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
