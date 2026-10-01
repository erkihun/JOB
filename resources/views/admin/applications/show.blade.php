@extends('layouts.admin')
@section('title', $application->reference_number)

@section('content')
@php
    $applicant = $application->applicant;
    $restricted = __('dashboard.restricted');
    // Recruitment data as captured with this application (frozen after the deadline),
    // so later profile edits never rewrite what was judged.
    $hasSnapshot = ! empty($application->profile_snapshot);
    $snap = fn (string $key) => $application->snapshotValue($key);
    $gender = \App\Enums\Gender::tryFrom((string) $snap('gender'));
    $level = \App\Enums\EducationLevel::tryFrom((string) $snap('education_level'));
    $dob = $snap('date_of_birth');
    $personal = [
        __('fields.gender')            => $gender?->getLabel(),
        __('fields.date_of_birth')     => $dob ? et_date(\Illuminate\Support\Carbon::parse($dob)) : null,
        __('fields.national_id')       => $canViewSensitive ? $applicant?->national_id : $restricted,
        __('fields.nationality')       => $snap('nationality'),
        __('fields.phone')             => $canViewSensitive ? $snap('phone') : $restricted,
        __('fields.email')             => $canViewSensitive ? $snap('email') : $restricted,
        __('fields.disability_status') => $snap('disability_status') ? __('applicant.disability_yes') : __('applicant.disability_no'),
    ];
    $education = [
        __('fields.education_level') => $level?->getLabel(),
        __('fields.field_of_study')  => $application->field_of_study ?: $snap('field_of_study'),
        __('fields.university_name') => $snap('university_name'),
        __('fields.graduation_year') => $snap('graduation_year'),
        __('fields.gpa')             => $application->cgpa ?? $snap('gpa'),
    ];
    $docs = $application->documents;
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="$applicant?->full_name ?? $application->reference_number"
                         :description="$application->reference_number.' · '.$application->vacancy?->title"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('menus.applications'), 'url' => route('admin.applications.index')], ['label' => $application->reference_number]]">
        <x-admin.status :status="$application->status" />
        @if($applicant)
        <a href="{{ route('admin.applicants.show', $applicant) }}" class="btn btn-secondary">{{ __('messages.apps_view_applicant') }}</a>
        @endif
        @can('screening.view')
        <a href="{{ route('admin.screening.review', $application) }}" class="btn btn-primary">{{ __('messages.review_application') }}</a>
        @endcan
    </x-admin.page-header>

    <p class="rounded-xl border px-4 py-3 text-sm {{ $hasSnapshot ? 'border-brand/20 bg-brand-muted/50 text-gray-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
        <strong>{{ __('recruitment.application.snapshot') }}.</strong>
        {{ $hasSnapshot ? __('recruitment.application.snapshot_hint').' '.__('recruitment.application.snapshot_taken', ['date' => et_date($application->snapshot_taken_at, 'M d, Y H:i')]) : __('recruitment.application.no_snapshot') }}
    </p>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">
            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-3">
                        @if($applicant?->profile_photo_path)
                            <x-admin.avatar :name="$applicant->full_name" :photo="route('admin.applicants.photo', $applicant)" size="md" />
                        @else
                            <x-admin.avatar :name="$applicant?->full_name" size="md" />
                        @endif
                        <h2 class="card-title">{{ __('applicant.personal_info') }}</h2>
                    </div>
                </div>
                <dl class="dl-grid card-body">
                    @foreach($personal as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">{{ __('applicant.education_info') }}</h2></div>
                <dl class="dl-grid card-body">
                    @foreach($education as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <x-admin.table-card :title="__('applicant.application_documents')" :meta="trans_choice('messages.files_count', $docs->count(), ['count' => $docs->count()])">
                @if($docs->isEmpty())
                    <x-admin.empty :text="__('applicant.no_documents')" />
                @else
                <ul class="divide-y divide-gray-100">
                    @foreach($docs as $doc)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $doc->vacancyDocument?->document_name ?? $doc->original_name }}</p>
                            <p class="truncate text-xs text-gray-600">{{ $doc->original_name }} · {{ number_format(($doc->file_size ?? 0) / 1024, 0) }} KB</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @if(str_starts_with((string) $doc->file_type, 'image/') || $doc->file_type === 'application/pdf')
                            <a href="{{ route('admin.documents.preview', $doc) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">{{ __('messages.preview') }}</a>
                            @endif
                            <a href="{{ route('admin.documents.download', $doc) }}" class="btn btn-secondary btn-sm">{{ __('messages.download') }}</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @endif
            </x-admin.table-card>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('vacancies.vacancy') }}</h2>
                @if($application->vacancy)
                <a href="{{ route('admin.vacancies.show', $application->vacancy) }}" class="font-semibold text-brand hover:underline">{{ $application->vacancy->title }}</a>
                <p class="mt-0.5 font-mono text-xs text-gray-600">{{ $application->vacancy->code }}</p>
                @endif
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.submitted') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($application->submitted_at ?? $application->created_at) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('admin.institution_name') }}</dt><dd class="text-right font-semibold text-gray-900">{{ $application->vacancy?->institution?->displayName() ?? '—' }}</dd></div>
                    @if($application->screener)
                    <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ __('messages.screened_by') }}</dt><dd class="text-right font-semibold text-gray-900">{{ $application->screener->name }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Editing lock: administrative lock and time-boxed, audited reopening --}}
            @canany(['applications.lock', 'applications.unlock'])
            <section class="card card-body space-y-3" aria-labelledby="lock-heading">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="lock-heading" class="card-title">{{ __('recruitment.application.lock') }}</h2>
                    @if($application->locked_at)
                        <x-admin.status tone="danger" :label="__('recruitment.application.locked_badge')" />
                    @elseif($application->isReopened())
                        <x-admin.status tone="warning" :label="__('recruitment.application.reopened_badge', ['date' => et_date($application->reopened_until, 'M d, Y')])" />
                    @endif
                </div>
                @if($application->locked_at && $application->lock_reason)
                    <p class="text-xs text-gray-600">{{ et_date($application->locked_at) }} — {{ $application->lock_reason }}</p>
                @endif

                @can('applications.lock')
                @if(! $application->locked_at)
                <form method="POST" action="{{ route('admin.applications.lock', $application) }}" class="space-y-2"
                      onsubmit="return confirm(@js(__('recruitment.application.lock_hint')))">
                    @csrf
                    <label for="lock_reason" class="form-label">{{ __('recruitment.reason') }}</label>
                    <textarea id="lock_reason" name="lock_reason" rows="2" required minlength="5" maxlength="1000" class="form-textarea">{{ old('lock_reason') }}</textarea>
                    @error('lock_reason')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn-secondary btn-sm w-full justify-center">{{ __('recruitment.application.lock') }}</button>
                </form>
                @endif
                @endcan

                @can('applications.unlock')
                @if($canReopen)
                <form method="POST" action="{{ route('admin.applications.reopen', $application) }}" class="space-y-2 border-t border-gray-100 pt-3">
                    @csrf
                    <p class="text-sm font-semibold text-gray-900">{{ __('recruitment.application.reopen') }}</p>
                    <p class="text-xs text-gray-600">{{ __('recruitment.application.reopen_hint') }}</p>
                    <label for="reopened_until" class="form-label">{{ __('recruitment.application.reopen_until') }}</label>
                    <input type="date" id="reopened_until" name="reopened_until" required
                           min="{{ today()->addDay()->toDateString() }}" max="{{ today()->addDays(\App\Actions\Applications\ChangeApplicationLockAction::MAX_REOPEN_DAYS)->toDateString() }}"
                           value="{{ old('reopened_until') }}" class="form-input">
                    @error('reopened_until')<p class="form-error">{{ $message }}</p>@enderror
                    <label for="reopen_reason" class="form-label">{{ __('recruitment.reason') }}</label>
                    <textarea id="reopen_reason" name="reopen_reason" rows="2" required minlength="10" maxlength="1000"
                              placeholder="{{ __('recruitment.reason_placeholder') }}" class="form-textarea">{{ old('reopen_reason') }}</textarea>
                    @error('reopen_reason')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn-secondary btn-sm w-full justify-center">{{ __('recruitment.application.reopen') }}</button>
                </form>
                @endif
                @endcan
            </section>
            @endcanany

            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('messages.screening_history') }}</h2>
                @forelse($application->screeningReviews->sortByDesc('created_at') as $review)
                <div class="border-t border-gray-100 py-3 first:border-0 first:pt-0">
                    <div class="flex items-center justify-between gap-3">
                        <x-admin.status :status="$review->decision" />
                        <span class="text-xs text-gray-600">{{ et_date($review->created_at) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-600">{{ $review->reviewer?->name }}</p>
                    @if($review->remark)<p class="mt-1 text-sm text-gray-800">{{ $review->remark }}</p>@endif
                </div>
                @empty
                <p class="text-sm text-gray-600">{{ __('messages.apps_not_screened') }}</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
