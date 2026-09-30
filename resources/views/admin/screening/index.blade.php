@extends('layouts.admin')
@section('title', $pageTitle)

@section('content')
@php
    $isQueue = request()->routeIs('admin.screening.index');
    $isPassed = request()->routeIs('admin.screening.passed');
    $tabs = [
        'admin.screening.index'  => __('menus.screening'),
        'admin.screening.passed' => __('menus.passed_applicants'),
        'admin.screening.failed' => __('menus.failed_applicants'),
    ];
    $descriptions = [
        'admin.screening.index'  => __('messages.scr_queue_intro'),
        'admin.screening.passed' => __('messages.scr_passed_intro'),
        'admin.screening.failed' => __('messages.scr_failed_intro'),
    ];
    $current = collect(array_keys($tabs))->first(fn ($r) => request()->routeIs($r)) ?? 'admin.screening.index';
    $filtersActive = request()->hasAny(['search', 'vacancy_id']);
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$pageTitle" :description="$descriptions[$current]"
                         :crumbs="[['label' => __('menus.screening')], ['label' => $pageTitle]]">
        @unless($isQueue)
            @php
                $exportRoute = $isPassed ? route('admin.screening.passed.export') : route('admin.screening.failed.export');
                $exportParams = array_filter(['vacancy_id' => request('vacancy_id')]);
            @endphp
            <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($exportParams, ['format' => 'excel'])) }}" class="btn btn-secondary" data-admin-no-spa>{{ __('messages.report_download_excel') }}</a>
            <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($exportParams, ['format' => 'pdf'])) }}" class="btn btn-secondary" data-admin-no-spa>{{ __('messages.scr_download_pdf') }}</a>
        @endunless
    </x-admin.page-header>

    <nav class="tabs" aria-label="{{ __('menus.screening') }}">
        @foreach($tabs as $route => $label)
        <a href="{{ route($route, array_filter(['vacancy_id' => request('vacancy_id')])) }}" class="tab {{ $current === $route ? 'tab-active' : '' }}" @if($current === $route) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    <x-admin.filters :reset="$resetRoute" :active="$filtersActive">
        <div class="lg:col-span-2">
            <label for="search" class="form-label">{{ __('messages.search') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.search_applicants') }}" class="form-input">
        </div>
        <div class="lg:col-span-2">
            <label for="vacancy_id" class="form-label">{{ __('menus.vacancies') }}</label>
            <select id="vacancy_id" name="vacancy_id" class="form-select">
                <option value="">{{ __('messages.all_vacancies') }}</option>
                @foreach ($vacancies as $v)
                <option value="{{ $v->id }}" @selected(request('vacancy_id') === $v->id)>{{ $v->code }} — {{ $v->title }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.filters>

    <x-admin.table-card :meta="trans_choice('messages.records_count', $applications->total(), ['count' => number_format($applications->total())])">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('messages.applicant') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('menus.vacancies') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('fields.education_level') }}</th>
                    <th class="table-th hidden xl:table-cell">{{ __('messages.vacancy_qualification') }}</th>
                    <th class="table-th">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($applications as $app)
                @php
                    $vacQual = collect([
                        $app->vacancy?->field_of_study,
                        $app->vacancy?->minimum_experience !== null ? trans_choice('public.years_count', (int) $app->vacancy->minimum_experience, ['count' => $app->vacancy->minimum_experience]) : null,
                    ])->filter()->implode(' · ');
                @endphp
                <tr class="table-row">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            <x-admin.avatar :name="$app->applicant?->full_name" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ $app->applicant?->full_name ?? '—' }}</p>
                                <p class="text-[13px] text-gray-600"><span class="font-mono">{{ $app->applicant?->applicant_code ?? '—' }}</span>@if($app->applicant?->gender) · {{ $app->applicant->gender->getLabel() }}@endif</p>
                            </div>
                        </div>
                    </td>
                    <td class="table-td hidden md:table-cell">
                        <p class="text-gray-900">{{ $app->vacancy?->title ?? '—' }}</p>
                        <p class="font-mono text-[13px] text-gray-600">{{ $app->vacancy?->code }}</p>
                    </td>
                    <td class="table-td table-td-muted hidden lg:table-cell">
                        {{ $app->applicant?->education_level?->getLabel() ?? '—' }}
                        @if($app->applicant?->field_of_study)<p class="text-[13px]">{{ $app->applicant->field_of_study }}</p>@endif
                    </td>
                    <td class="table-td table-td-muted hidden xl:table-cell">{{ $vacQual ?: '—' }}</td>
                    <td class="table-td"><x-admin.status :status="$app->status" /></td>
                    <td class="table-td">
                        <div class="table-actions">
                            <a href="{{ route('admin.screening.review', array_filter(['application' => $app->id, 'vacancy_id' => request('vacancy_id')])) }}"
                               class="btn {{ $isQueue ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ $isQueue ? __('messages.review') : __('messages.view') }}</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-admin.empty :title="$emptyText" :text="$filtersActive ? __('messages.empty_filtered') : ($isQueue ? __('messages.scr_queue_empty_hint') : null)" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $applications->links() }}</x-slot:footer>
    </x-admin.table-card>
</div>
@endsection
