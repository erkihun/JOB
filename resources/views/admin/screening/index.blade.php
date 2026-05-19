@extends('layouts.admin')
@section('title', $pageTitle)

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-lg font-semibold text-gray-900">{{ $pageTitle }}</h1>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.screening.index') }}" class="btn {{ request()->routeIs('admin.screening.index') ? 'btn-accent' : 'btn-secondary' }}">
                {{ __('menus.screening') }}
            </a>
            <a href="{{ route('admin.screening.passed') }}" class="btn {{ request()->routeIs('admin.screening.passed') ? 'btn-accent' : 'btn-secondary' }}">
                {{ __('menus.passed_applicants') }}
            </a>
            <a href="{{ route('admin.screening.failed') }}" class="btn {{ request()->routeIs('admin.screening.failed') ? 'btn-accent' : 'btn-secondary' }}">
                {{ __('menus.failed_applicants') }}
            </a>

            @if(request()->routeIs('admin.screening.passed') || request()->routeIs('admin.screening.failed'))
                @php
                    $exportRoute = request()->routeIs('admin.screening.passed')
                        ? route('admin.screening.passed.export')
                        : route('admin.screening.failed.export');
                    $exportParams = array_filter(['vacancy_id' => request('vacancy_id')]);
                @endphp
                <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($exportParams, ['format' => 'excel'])) }}"
                   class="btn btn-outline">
                    &#8595; Excel
                </a>
                <a href="{{ $exportRoute }}?{{ http_build_query(array_merge($exportParams, ['format' => 'pdf'])) }}"
                   class="btn btn-outline">
                    &#8595; PDF
                </a>
            @endif
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="{{ __('messages.search') }}..."
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand sm:w-56"
        >
        <select name="vacancy_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand focus:ring-1 focus:ring-brand">
            <option value="">{{ __('messages.all_vacancies') }}</option>
            @foreach ($vacancies as $v)
                <option value="{{ $v->id }}" {{ request('vacancy_id') === $v->id ? 'selected' : '' }}>
                    {{ $v->code }} - {{ $v->title }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-navy">{{ __('messages.filter') }}</button>
        @if (request()->hasAny(['search', 'vacancy_id']))
            <a href="{{ $resetRoute }}" class="btn btn-secondary">{{ __('messages.reset') }}</a>
        @endif
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-xs">
            <thead class="table-header">
                <tr>
                    <th class="table-th whitespace-nowrap">{{ __('messages.applicant_code') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('messages.applicant') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('fields.gender') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('menus.vacancies') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('messages.vacancy_qualification') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('fields.education_level') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('fields.field_of_study') }}</th>
                    <th class="table-th whitespace-nowrap">{{ __('vacancies.status') }}</th>
                    <th class="table-th-right whitespace-nowrap">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($applications as $app)
                    @php
                        $badgeMap = [
                            'submitted'          => 'badge-blue',
                            'passed_screening'   => 'badge-green',
                            'failed_screening'   => 'badge-red',
                            'correction_required'=> 'badge-amber',
                        ];
                        $badgeClass = $badgeMap[$app->status->value] ?? 'badge-gray';

                        $vacQual = collect([
                            $app->vacancy?->field_of_study,
                            $app->vacancy?->minimum_experience !== null
                                ? $app->vacancy->minimum_experience.' yrs exp'
                                : null,
                        ])->filter()->implode(' · ');
                    @endphp
                    <tr class="table-row">
                        <td class="table-td font-mono text-gray-500">
                            {{ $app->applicant?->applicant_code ?? '--' }}
                        </td>
                        <td class="table-td">
                            <p class="font-medium text-gray-900 whitespace-nowrap">{{ $app->applicant?->full_name ?? '--' }}</p>
                        </td>
                        <td class="table-td text-gray-700 whitespace-nowrap">
                            {{ $app->applicant?->gender?->getLabel() ?? '--' }}
                        </td>
                        <td class="table-td">
                            <p class="font-medium text-gray-800 whitespace-nowrap">{{ $app->vacancy?->title ?? '--' }}</p>
                            <p class="text-gray-400 font-mono">{{ $app->vacancy?->code }}</p>
                        </td>
                        <td class="table-td text-gray-600">
                            {{ $vacQual ?: '--' }}
                        </td>
                        <td class="table-td text-gray-700 whitespace-nowrap">
                            {{ $app->applicant?->education_level?->getLabel() ?? '--' }}
                        </td>
                        <td class="table-td text-gray-700">
                            {{ $app->applicant?->field_of_study ?? '--' }}
                        </td>
                        <td class="table-td">
                            <span class="{{ $badgeClass }} whitespace-nowrap">{{ $app->status->getLabel() }}</span>
                        </td>
                        <td class="table-td text-right whitespace-nowrap">
                            <a href="{{ route('admin.screening.review', $app) }}"
                               class="btn btn-accent" style="padding:0.25rem 0.75rem;font-size:0.75rem;">
                                {{ __('messages.review') }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">{{ $emptyText }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @if ($applications->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $applications->links() }}</div>
        @endif
    </div>
</div>
@endsection
