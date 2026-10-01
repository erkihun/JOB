@extends('layouts.applicant')

@section('title', __('applicant.application_detail'))

@section('content')
@php
    $locale = app()->getLocale();
    $vacancy = $application->vacancy;
    $vacancyTitle = $vacancy->getTranslation('title', $locale, false) ?: $vacancy->getTranslation('title', 'en', false);
    $tone = [
        'success' => 'bg-green-50 text-green-800',
        'danger'  => 'bg-red-50 text-red-800',
        'warning' => 'bg-accent-muted text-accent-dark',
        'info'    => 'bg-brand-muted text-brand-dark',
    ][\App\Support\ApplicationProgress::tone($application->status)];
    $upcoming = $sessions->first(fn ($r) => $r->schedule->date->gte(today()));
    $facts = array_filter([
        __('applications.reference_number') => $application->reference_number,
        __('applicant.submitted_at')        => $application->submitted_at ? et_date($application->submitted_at, 'M d, Y H:i') : null,
        __('fields.field_of_study')         => $application->field_of_study,
        __('fields.graduation_date')        => $application->graduation_date ? et_date($application->graduation_date, 'd M Y') : null,
        __('fields.cgpa')                   => $application->cgpa !== null ? number_format($application->cgpa, 2) : null,
    ], fn ($v) => filled($v));
    $docTone = ['verified' => 'bg-green-50 text-green-800', 'rejected' => 'bg-red-50 text-red-800'];
    $editable = $application->isEditable();
    $closingDay = $vacancy->announcement?->closing_date;
@endphp
<div class="space-y-6">

    <x-applicant.page-header :title="$vacancyTitle"
                             :description="collect([$application->reference_number, $vacancy->department, $vacancy->institution?->displayName(), $application->submitted_at ? __('applicant.applied_on', ['date' => et_date($application->submitted_at, 'M d, Y')]) : null])->filter()->implode(' · ')"
                             :crumbs="[['label' => __('applicant.nav_dashboard'), 'url' => route('applicant.dashboard')], ['label' => __('applicant.my_applications'), 'url' => route('applicant.applications.index')], ['label' => $application->reference_number]]">
        <span class="rounded-full px-3 py-1.5 text-sm font-bold {{ $tone }}">{{ $application->status->label() }}</span>
        @if($editable)
        <a href="{{ route('applicant.applications.edit', $application) }}" class="inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark">{{ __('applicant.edit_application') }}</a>
        @endif
    </x-applicant.page-header>

    {{-- Editing window: open until the closing day (or an authorised reopening), read-only afterwards. --}}
    @if($editable && $application->isReopened())
        <p class="rounded-xl border border-accent/30 bg-accent-muted px-4 py-3 text-sm font-semibold text-accent-dark" role="status">
            {{ __('recruitment.applicant.reopened_until', ['date' => et_date($application->reopened_until, 'M d, Y')]) }}
        </p>
    @elseif($editable && $closingDay)
        <p class="rounded-xl border border-brand/20 bg-brand-muted/60 px-4 py-3 text-sm font-semibold text-brand-dark" role="status">
            {{ __('recruitment.applicant.editable_until', ['date' => et_date($closingDay, 'M d, Y')]) }}
        </p>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800" role="status">
            @if($vacancy->isPastDeadline())
                <p class="font-bold text-gray-900">{{ __('recruitment.applicant.closed') }}</p>
            @endif
            <p>{{ __('recruitment.applicant.read_only') }}</p>
        </div>
    @endif

    {{-- Tracker + what happens next --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-7" aria-labelledby="progress-heading">
        <h2 id="progress-heading" class="sr-only">{{ __('applicant.status_timeline') }}</h2>
        <x-applicant.tracker :application="$application" variant="full" />
        <div class="mt-6 flex flex-col gap-1 rounded-xl border px-4 py-3.5 sm:flex-row sm:gap-3
                    {{ $application->status->value === 'correction_required' ? 'border-accent/30 bg-accent-muted' : 'border-brand/20 bg-brand-muted/60' }}">
            <strong class="shrink-0 text-sm {{ $application->status->value === 'correction_required' ? 'text-accent-dark' : 'text-brand-dark' }}">{{ __('applicant.what_happens_next') }}</strong>
            <p class="text-sm text-gray-800">{{ \App\Support\ApplicationProgress::nextText($application) }}</p>
        </div>
        @if($application->status->value === 'correction_required' && $application->screening_remark)
        <div class="mt-3 rounded-xl border border-accent/30 bg-white px-4 py-3 text-sm">
            <p class="font-bold text-accent-dark">{{ __('applicant.hr_remark') }}</p>
            <p class="mt-1 whitespace-pre-line text-gray-800">{{ $application->screening_remark }}</p>
        </div>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div class="space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="details-heading">
                <h2 id="details-heading" class="border-b border-gray-100 px-5 py-4 text-lg font-extrabold text-gray-900">{{ __('applicant.submitted_details') }}</h2>
                <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                    @foreach($facts as $label => $value)
                    <div>
                        <dt class="text-[13px] font-semibold text-gray-600">{{ $label }}</dt>
                        <dd class="mt-0.5 text-[15px] font-semibold text-gray-900 {{ $label === __('applications.reference_number') ? 'font-mono' : '' }}">{{ $value }}</dd>
                    </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="docs-heading">
                <h2 id="docs-heading" class="border-b border-gray-100 px-5 py-4 text-lg font-extrabold text-gray-900">{{ __('applicant.uploaded_documents') }}</h2>
                @forelse($application->documents as $document)
                <div class="flex flex-col gap-2 border-b border-gray-100 px-5 py-3.5 last:border-0 sm:flex-row sm:items-center sm:gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $document->vacancyDocument?->document_name ?: $document->original_name }}</p>
                        <p class="truncate text-[13px] text-gray-600">{{ $document->original_name }} · {{ number_format($document->file_size / 1024, 0) }} KB</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $docTone[$document->verification_status->value ?? ''] ?? 'bg-gray-100 text-gray-700' }}">{{ $document->verification_status->label() }}</span>
                        <a href="{{ route('applicant.documents.download', $document) }}" class="text-sm font-bold text-brand hover:underline">{{ __('messages.download') }}</a>
                    </div>
                </div>
                @empty
                <p class="px-5 py-5 text-sm text-gray-600">{{ __('applicant.no_documents') }}</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6">
            {{-- Exam / interview --}}
            @if($upcoming)
            @php $s = $upcoming->schedule; @endphp
            <section class="rounded-2xl bg-ink p-5 text-white" aria-labelledby="session-heading">
                <p class="text-[13px] font-extrabold uppercase tracking-wider text-brand-muted">{{ __('applicant.your_session', ['type' => $s->type->getLabel()]) }}</p>
                <h2 id="session-heading" class="mt-2 text-xl font-extrabold">{{ et_date($s->date, 'l, d F Y') }}</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-white/70">{{ __('applicant.time') }}</dt><dd class="font-bold">{{ $s->start_time }}@if($s->end_time) – {{ $s->end_time }}@endif</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-white/70">{{ __('applicant.venue') }}</dt><dd class="text-right font-bold">{{ $s->venue }}</dd></div>
                </dl>
                @if($s->instruction)
                <p class="mt-4 whitespace-pre-line border-t border-white/15 pt-3 text-sm text-white/80">{{ $s->instruction }}</p>
                @endif
            </section>
            @endif

            {{-- Past sessions with results --}}
            @php $past = $sessions->filter(fn ($r) => $r->schedule->date->lt(today())); @endphp
            @if($past->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="past-heading">
                <h2 id="past-heading" class="mb-3 font-extrabold text-gray-900">{{ __('applicant.past_sessions') }}</h2>
                @foreach($past as $r)
                <div class="flex items-center justify-between gap-3 border-t border-gray-100 py-2.5 text-sm first:border-0 first:pt-0">
                    <span><span class="font-semibold text-gray-900">{{ $r->schedule->type->getLabel() }}</span><span class="block text-xs text-gray-600">{{ et_date($r->schedule->date) }}</span></span>
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-bold text-gray-700">{{ __('messages.'.($r->status === 'passed' ? 'pass' : ($r->status === 'failed' ? 'fail' : $r->status))) }}</span>
                </div>
                @endforeach
            </section>
            @endif

            {{-- Messages about this application --}}
            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="app-msg-heading">
                <h2 id="app-msg-heading" class="border-b border-gray-100 px-5 py-4 font-extrabold text-gray-900">{{ __('applicant.messages_about') }}</h2>
                @forelse($messages as $message)
                <details class="group border-b border-gray-100 px-5 py-3 last:border-0">
                    <summary class="flex cursor-pointer list-none items-start justify-between gap-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">{{ $message->subject }}</span>
                            <span class="text-xs text-gray-600">{{ et_date($message->created_at) }}</span>
                        </span>
                        <svg class="mt-1 h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-800">{{ $message->message }}</p>
                </details>
                @empty
                <p class="px-5 py-4 text-sm text-gray-600">{{ __('applicant.no_messages_yet') }}</p>
                @endforelse
            </section>

            <a href="{{ route('applicant.vacancies.show', $vacancy) }}" class="block rounded-2xl border border-gray-200 bg-white px-5 py-4 text-sm hover:border-brand/40">
                <span class="block text-[13px] font-semibold text-gray-600">{{ __('applicant.vacancy_details') }}</span>
                <span class="font-bold text-brand">{{ $vacancyTitle }} →</span>
            </a>
        </aside>
    </div>
</div>
@endsection
