@extends('layouts.admin')
@section('title', $vacancy->title)

@section('content')
@php
    $apps = $vacancy->applications;
    $countBy = fn (array $statuses) => $apps->filter(fn ($a) => in_array($a->status?->value, $statuses, true))->count();
    $pending = $countBy(['submitted', 'under_review', 'correction_required']);
    $passed = $countBy(['passed_screening', 'shortlisted_exam', 'exam_completed', 'shortlisted_interview', 'interview_completed', 'selected', 'waitlisted', 'not_selected']);
    $announcement = $vacancy->announcement;
    $details = [
        __('vacancies.department')         => $vacancy->department ?: '—',
        __('vacancies.employment_type')    => $vacancy->employment_type?->getLabel() ?? '—',
        __('vacancies.positions')          => $vacancy->number_of_positions,
        __('vacancies.education_level')    => $vacancy->education_level?->getLabel() ?? '—',
        __('vacancies.minimum_experience') => trans_choice('public.years_count', (int) ($vacancy->minimum_experience ?? 0), ['count' => (int) ($vacancy->minimum_experience ?? 0)]),
        __('vacancies.institution')        => $vacancy->institution?->name ?? '—',
    ];
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$vacancy->title"
                         :description="collect([$vacancy->code, $announcement?->code])->filter()->implode(' · ')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.vacancies'), 'url' => route('admin.vacancies.index')], ['label' => $vacancy->code]]">
        <x-admin.status :status="$vacancy->status" />
        @if($announcement?->isPublished() && $vacancy->status->value === 'open')
        <a href="{{ route('vacancies.show', $vacancy) }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('messages.ann_view_public') }}</a>
        @endif
        <a href="{{ route('admin.vacancies.edit', $vacancy) }}" class="btn btn-primary">{{ __('messages.edit') }}</a>
    </x-admin.page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('messages.report_summary') }}">
        <x-admin.stat :label="__('vacancies.applications')" :value="number_format($apps->count())" :href="route('admin.applications.index', ['vacancy_id' => $vacancy->id])" />
        <x-admin.stat :label="__('messages.report_kpi_in_review')" :value="number_format($pending)" :tone="$pending ? 'warn' : 'neutral'"
                      :hint="$pending ? __('messages.vac_open_queue') : null" :href="$pending ? route('admin.screening.index', ['vacancy_id' => $vacancy->id]) : null" />
        <x-admin.stat :label="__('dashboard.kpi.passed_screening')" :value="number_format($passed)" tone="good" />
        <x-admin.stat :label="__('messages.vac_application_period')" :value="et_date($announcement?->closing_date, 'M d')"
                      :hint="__('vacancies.opening_date').': '.et_date($announcement?->opening_date, 'M d, Y')" />
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('vacancies.description') }}</h2></div>
                <div class="card-body space-y-6">
                    <div class="prose prose-sm max-w-none text-gray-800">{!! nl2br(e($vacancy->description ?: '—')) !!}</div>
                    @if($vacancy->qualification_requirements)
                    <div>
                        <h3 class="mb-2 text-sm font-semibold text-gray-900">{{ __('vacancies.qualification_requirements') }}</h3>
                        <div class="prose prose-sm max-w-none text-gray-800">{!! nl2br(e($vacancy->qualification_requirements)) !!}</div>
                    </div>
                    @endif
                </div>
            </section>

            <x-admin.table-card :title="__('menus.applications')" :meta="trans_choice('messages.records_count', $apps->count(), ['count' => $apps->count()])">
                <x-slot:actions>
                    <a href="{{ route('admin.applications.index', ['vacancy_id' => $vacancy->id]) }}" class="link-action">{{ __('dashboard.actions.view_all') }} →</a>
                </x-slot:actions>
                @if($apps->isEmpty())
                    <x-admin.empty :text="__('messages.vac_no_applications')" />
                @else
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="table-header">
                        <tr>
                            <th class="table-th">{{ __('messages.applicant') }}</th>
                            <th class="table-th">{{ __('vacancies.status') }}</th>
                            <th class="table-th hidden sm:table-cell">{{ __('messages.submitted') }}</th>
                            <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($apps->sortByDesc('created_at')->take(15) as $app)
                        <tr class="table-row">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :name="$app->applicant?->full_name" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-900">{{ $app->applicant?->full_name }}</p>
                                        <p class="font-mono text-xs text-gray-600">{{ $app->reference_number }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td"><x-admin.status :status="$app->status" /></td>
                            <td class="table-td table-td-muted hidden sm:table-cell">{{ et_date($app->created_at) }}</td>
                            <td class="table-td"><div class="table-actions"><a href="{{ route('admin.applications.show', $app) }}" class="btn btn-secondary btn-sm">{{ __('messages.view') }}</a></div></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </x-admin.table-card>
        </div>

        <aside class="card card-body lg:sticky lg:top-24">
            <h2 class="card-title mb-4">{{ __('vacancies.details') }}</h2>
            <dl class="space-y-3 text-sm">
                @foreach($details as $label => $value)
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">{{ $label }}</dt>
                    <dd class="text-right font-semibold text-gray-900">{{ $value }}</dd>
                </div>
                @endforeach
            </dl>
            @if($announcement)
            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="mt-5 block rounded-lg bg-gray-50 px-3 py-2.5 text-sm hover:bg-gray-100">
                <span class="block text-[13px] font-semibold text-gray-600">{{ __('menus.announcements') }}</span>
                <span class="font-semibold text-brand">{{ $announcement->code }}</span>
                <span class="block text-xs text-gray-600">{{ et_date($announcement->opening_date) }} – {{ et_date($announcement->closing_date) }}</span>
            </a>
            @endif
        </aside>
    </div>
</div>
@endsection
