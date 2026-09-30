@extends('layouts.admin')
@section('title', $vacancy->title)

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('admin.vacancies.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.vacancies') }}</a>
            <h1 class="mt-1 page-title">{{ $vacancy->title }}</h1>
            <p class="font-mono text-sm text-gray-600">{{ $vacancy->code }}</p>
        </div>
        <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="btn btn-outline">
            {{ __('messages.edit') }}
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body lg:col-span-2">
            <div class="mb-4 flex items-center gap-2">
                <h2 class="card-title">{{ __('vacancies.description') }}</h2>
            </div>
            <div class="prose prose-sm max-w-none text-gray-700">
                {!! nl2br(e($vacancy->description)) !!}
            </div>
            @if($vacancy->qualification_requirements)
            <div class="mt-6 flex items-center gap-2">
                <h2 class="card-title">{{ __('vacancies.qualification_requirements') }}</h2>
            </div>
            <div class="prose prose-sm mt-3 max-w-none text-gray-700">
                {!! nl2br(e($vacancy->qualification_requirements)) !!}
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="space-y-3 card card-body">
                <div class="flex items-center gap-2">
                    <h2 class="card-title">{{ __('vacancies.details') }}</h2>
                </div>
                @php
                $fields = [
                    __('vacancies.status')           => $vacancy->status->getLabel(),
                    __('vacancies.department')        => $vacancy->department ?? '—',
                    __('vacancies.employment_type')   => $vacancy->employment_type?->getLabel() ?? '—',
                    __('vacancies.positions')          => $vacancy->number_of_positions,
                    __('vacancies.education_level')    => $vacancy->education_level?->getLabel() ?? '—',
                    __('vacancies.minimum_experience') => ($vacancy->minimum_experience ?? 0) . ' yrs',
                    __('vacancies.opening_date')       => et_date($vacancy->announcement->opening_date),
                    __('vacancies.closing_date')       => et_date($vacancy->announcement->closing_date),
                    __('vacancies.applications')       => $vacancy->applications->count(),
                ];
                @endphp
                @foreach($fields as $label => $value)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">{{ $label }}</span>
                    <span class="font-medium text-gray-800">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Applications table --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <div class="flex items-center gap-2">
                <h2 class="card-title">{{ __('menus.applications') }}</h2>
            </div>
            <a href="{{ route('admin.applications.index', ['vacancy_id' => $vacancy->id]) }}" class="link-action">{{ __('dashboard.actions.view_all') }} →</a>
        </div>
        @if($vacancy->applications->isEmpty())
        <p class="px-5 py-8 text-center text-sm text-gray-600">{{ __('messages.no_records') }}</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">{{ __('messages.applicant') }}</th>
                        <th class="table-th">{{ __('vacancies.status') }}</th>
                        <th class="table-th">{{ __('messages.submitted') }}</th>
                        <th class="table-th-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($vacancy->applications->take(15) as $app)
                                        <tr class="table-row">
                        <td class="table-td font-medium text-gray-900">{{ $app->applicant?->full_name }}</td>
                        <td class="table-td"><x-admin.status :status="$app->status" /></td>
                        <td class="table-td text-gray-500">{{ et_date($app->created_at) }}</td>
                        <td class="table-td text-right">
                            <a href="{{ route('admin.applications.show', $app) }}" class="link-action">{{ __('messages.view') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
