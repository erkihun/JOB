@extends('layouts.admin')
@section('title', __('menus.screening').': '.$application->reference_number)

@section('content')
<div class="space-y-6" x-data="{ previewOpen: false, previewUrl: '', previewName: '' }">

    <x-admin.page-header :title="$application->applicant?->full_name ?? $application->reference_number"
                         :description="__('messages.review_application').' · '.$application->reference_number.' · '.$application->vacancy?->title"
                         :crumbs="[['label' => __('menus.screening'), 'url' => route('admin.screening.index', array_filter(['vacancy_id' => $queueVacancyId ?? null]))], ['label' => $application->reference_number]]">
        <x-admin.status :status="$application->status" />
        <span class="badge badge-gray">{{ trans_choice('messages.queue_remaining', $queueRemaining ?? 0, ['count' => $queueRemaining ?? 0]) }}</span>
        @if($nextApplication ?? null)
            <a href="{{ route('admin.screening.review', array_filter(['application' => $nextApplication->id, 'vacancy_id' => $queueVacancyId ?? null])) }}"
               class="btn btn-secondary">
                {{ __('messages.skip_to_next') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        @endif
    </x-admin.page-header>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="space-y-5 lg:col-span-2">

            {{-- Personal Information --}}
            <div class="card card-body">
                <div class="mb-4 flex items-center gap-2">
                    <h2 class="card-title">{{ __('applicant.personal_info') }}</h2>
                </div>
                <div class="mb-5 flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-muted text-base font-semibold text-brand ring-1 ring-brand/10">
                        {{ mb_substr($application->applicant?->full_name ?? '?', 0, 2) }}
                    </div>
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ $application->applicant?->full_name }}</p>
                        <p class="text-sm text-gray-500">{{ $canViewSensitive ? ($application->applicant?->email ?? '--') : __('dashboard.restricted') }}</p>
                        @if($application->applicant?->applicant_code)
                            <p class="mt-0.5 font-mono text-xs text-gray-600">{{ $application->applicant->applicant_code }}</p>
                        @endif
                    </div>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @php
                        $personalFields = [
                            __('fields.gender')           => $application->applicant?->gender?->getLabel(),
                            __('fields.date_of_birth')    => et_date($application->applicant?->date_of_birth),
                            __('fields.nationality')      => $application->applicant?->nationality,
                            __('fields.national_id')      => $canViewSensitive ? $application->applicant?->national_id : __('dashboard.restricted'),
                            __('fields.phone')            => $canViewSensitive ? $application->applicant?->phone : __('dashboard.restricted'),
                            __('fields.alternative_phone')=> $canViewSensitive ? $application->applicant?->alternative_phone : __('dashboard.restricted'),
                        ];
                    @endphp
                    @foreach ($personalFields as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-600">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{-- Disability (always show status; show type only if true) --}}
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <dl class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-600">{{ __('fields.disability_status') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">
                                {{ $application->applicant?->disability_status ? __('applicant.disability_yes') : __('applicant.disability_no') }}
                            </dd>
                        </div>
                        @if($application->applicant?->disability_status)
                            <div>
                                <dt class="text-xs text-gray-600">{{ __('fields.disability_type') }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $application->applicant?->disability_type ?? '--' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Address --}}
            <div class="card card-body">
                <div class="mb-4 flex items-center gap-2">
                    <h2 class="card-title">{{ __('applicant.contact_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        __('fields.region')  => $application->applicant?->region,
                        __('fields.city')    => $application->applicant?->city,
                        __('fields.woreda')  => $application->applicant?->woreda,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-600">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($application->applicant?->address)
                    <div class="mt-3">
                        <dt class="text-xs text-gray-600">{{ __('fields.address') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $application->applicant->address }}</dd>
                    </div>
                @endif
            </div>

            {{-- Education --}}
            <div class="card card-body">
                <div class="mb-4 flex items-center gap-2">
                    <h2 class="card-title">{{ __('applicant.education_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        __('fields.education_level')  => $application->applicant?->education_level?->getLabel(),
                        __('fields.university_name')  => $application->applicant?->university_name,
                        __('fields.field_of_study')   => $application->applicant?->field_of_study,
                        __('fields.graduation_year')  => $application->applicant?->graduation_year,
                        __('fields.graduation_date')  => et_date($application->applicant?->graduation_date),
                        __('fields.gpa')              => $application->applicant?->gpa,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-600">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Work Experience --}}
            <div class="card card-body">
                <div class="mb-4 flex items-center gap-2">
                    <h2 class="card-title">{{ __('applicant.work_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        __('fields.work_experience_years')  => $application->applicant?->work_experience_years !== null ? $application->applicant->work_experience_years.' yrs' : null,
                        __('fields.work_experience_months') => $application->applicant?->work_experience_months !== null ? $application->applicant->work_experience_months.' mo' : null,
                        __('fields.current_employer')       => $application->applicant?->current_employer,
                        __('fields.current_position')       => $application->applicant?->current_position,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-600">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($application->applicant?->work_experience_summary)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <dt class="text-xs text-gray-600">{{ __('fields.work_experience_summary') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $application->applicant->work_experience_summary }}</dd>
                    </div>
                @endif
            </div>

            {{-- Documents: shown inline so screeners can read them without clicking --}}
            @php
                $isPreviewable = fn (?string $mime): bool => str_starts_with((string) $mime, 'image/') || $mime === 'application/pdf';
                $reviewDocs = collect();
                foreach ($application->documents ?? collect() as $doc) {
                    $reviewDocs->push([
                        'group' => __('applicant.application_documents'),
                        'name' => $doc->vacancyDocument?->document_name ?? $doc->original_name,
                        'file' => $doc->original_name,
                        'mime' => (string) $doc->file_type,
                        'size' => $doc->file_size ? number_format($doc->file_size / 1048576, 2).' MB' : null,
                        'preview' => route('admin.documents.preview', $doc),
                        'download' => route('admin.documents.download', $doc),
                    ]);
                }
                foreach ($application->applicant?->profileDocuments ?? collect() as $doc) {
                    $reviewDocs->push([
                        'group' => __('applicant.uploaded_documents'),
                        'name' => $doc->document_type ? \Illuminate\Support\Str::headline($doc->document_type) : $doc->original_name,
                        'file' => $doc->original_name,
                        'mime' => (string) $doc->file_type,
                        'size' => $doc->file_size_mb ? $doc->file_size_mb.' MB' : null,
                        'preview' => route('admin.profile-documents.preview', $doc),
                        'download' => route('admin.profile-documents.download', $doc),
                    ]);
                }
            @endphp
            <section class="card" aria-labelledby="documents-heading">
                <div class="card-header">
                    <h2 id="documents-heading" class="card-title">{{ __('applicant.uploaded_documents') }}</h2>
                    <span class="text-[13px] text-gray-600">{{ trans_choice('messages.files_count', $reviewDocs->count(), ['count' => $reviewDocs->count()]) }}</span>
                </div>
                <div class="card-body space-y-5">
                    @forelse($reviewDocs->groupBy('group') as $group => $docs)
                        <div class="space-y-4">
                            @if($reviewDocs->pluck('group')->unique()->count() > 1)
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-600">{{ $group }}</p>
                            @endif
                            @foreach($docs as $doc)
                            <article class="overflow-hidden rounded-xl border border-gray-200">
                                <header class="flex flex-wrap items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2.5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold {{ $doc['mime'] === 'application/pdf' ? 'bg-red-50 text-red-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ strtoupper(\Illuminate\Support\Str::afterLast($doc['mime'] ?: 'file', '/')) === 'JPEG' ? 'JPG' : strtoupper(\Illuminate\Support\Str::limit(\Illuminate\Support\Str::afterLast($doc['mime'] ?: 'file', '/'), 4, '')) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $doc['name'] }}</p>
                                        <p class="truncate text-xs text-gray-600">{{ $doc['file'] }}@if($doc['size']) · {{ $doc['size'] }}@endif</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($isPreviewable($doc['mime']))
                                        <button type="button" class="btn btn-secondary btn-sm"
                                                @click="previewUrl = @js($doc['preview']); previewName = @js($doc['name']); previewOpen = true">
                                            {{ __('messages.expand') }}
                                        </button>
                                        @endif
                                        <a href="{{ $doc['download'] }}" class="btn btn-secondary btn-sm" data-admin-no-spa>{{ __('menus.download') }}</a>
                                    </div>
                                </header>
                                @if($doc['mime'] === 'application/pdf')
                                    <iframe src="{{ $doc['preview'] }}#view=FitH" title="{{ $doc['name'] }}" loading="lazy" class="block h-[640px] w-full bg-gray-100"></iframe>
                                @elseif($isPreviewable($doc['mime']))
                                    <div class="flex justify-center bg-gray-100 p-3">
                                        <img src="{{ $doc['preview'] }}" alt="{{ $doc['name'] }}" loading="lazy" class="max-h-[640px] w-auto rounded-md bg-white object-contain shadow-sm">
                                    </div>
                                @else
                                    <p class="px-4 py-6 text-center text-sm text-gray-600">{{ __('messages.preview_unavailable') }}</p>
                                @endif
                            </article>
                            @endforeach
                        </div>
                    @empty
                        <x-admin.empty :title="__('applicant.no_documents')" />
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="card card-body">
                <div class="mb-3 flex items-center gap-2">
                    <h2 class="card-title">{{ __('vacancies.vacancy') }}</h2>
                </div>
                <p class="font-semibold text-gray-900">{{ $application->vacancy?->title }}</p>
                <p class="mt-0.5 font-mono text-xs text-gray-600">{{ $application->vacancy?->code }}</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('vacancies.positions') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->vacancy?->number_of_positions }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">{{ __('vacancies.minimum_experience') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->vacancy?->minimum_experience ?? 0 }} yrs</span>
                    </div>
                </div>
            </div>

            @if($eligibility?->hasRules)
            <div class="card card-body">
                <div class="mb-3 flex items-center gap-2">
                    <h2 class="card-title">{{ __('vacancies.eligibility_requirements') }}</h2>
                </div>
                <x-eligibility-result :result="$eligibility" subject="staff" />
                <ol class="mt-4 space-y-2 text-xs text-gray-600">
                    @foreach($application->vacancy->requirementGroups->where('is_active', true)->values() as $group)
                    <li>
                        <span class="font-semibold text-gray-800">{{ \App\Services\Eligibility\VacancyEligibilityChecker::optionLabel($loop->iteration, $group->title) }}:</span>
                        {{ $group->requirements->map->summary()->implode(' + ') }}
                    </li>
                    @endforeach
                </ol>
            </div>
            @endif

            <div id="screening-decision" class="card card-body">
                <div class="mb-4 flex items-center gap-2">
                    <h2 class="card-title">{{ __('messages.screening_decision') }}</h2>
                    @unless($application->awaitsScreeningDecision())
                        <span class="badge badge-gray ml-auto">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V7a4 4 0 118 0v4"/></svg>
                            {{ __('messages.decision_locked') }}
                        </span>
                    @endunless
                </div>
                @if($application->awaitsScreeningDecision())
                <form method="POST" action="{{ route('admin.screening.submit', $application) }}" class="space-y-4"
                      x-data="{ decision: @js(old('decision')) }">
                    @csrf
                    @if($queueVacancyId ?? null)
                        <input type="hidden" name="queue_vacancy_id" value="{{ $queueVacancyId }}">
                    @endif

                    {{-- Screening has exactly two outcomes: pass or fail --}}
                    <fieldset>
                        <legend class="form-label">{{ __('messages.decision') }} <span class="form-required">*</span></legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                                   :class="decision === 'passed' ? 'border-green-600 bg-green-50 text-green-800' : 'border-gray-200 text-gray-700 hover:border-gray-300'">
                                <input type="radio" name="decision" value="passed" x-model="decision" class="sr-only" @checked(old('decision') === 'passed')>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ __('messages.pass') }}
                            </label>
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 px-3 py-3 text-sm font-semibold transition"
                                   :class="decision === 'failed' ? 'border-red-600 bg-red-50 text-red-800' : 'border-gray-200 text-gray-700 hover:border-gray-300'">
                                <input type="radio" name="decision" value="failed" x-model="decision" class="sr-only" @checked(old('decision') === 'failed')>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                {{ __('messages.fail') }}
                            </label>
                        </div>
                        @error('decision')<p class="form-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div>
                        <label for="remark" class="form-label">
                            {{ __('messages.remarks') }}
                            <span x-show="decision === 'failed'" x-cloak class="form-required">*</span>
                        </label>
                        <textarea id="remark" name="remark" rows="4" :required="decision === 'failed'"
                                  class="form-textarea"
                                  placeholder="{{ __('messages.remarks_placeholder') }}">{{ old('remark') }}</textarea>
                        <p class="form-hint" x-show="decision === 'failed'" x-cloak>{{ __('messages.fail_remark_hint') }}</p>
                        @error('remark')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full justify-center">
                        {{ ($nextApplication ?? null) ? __('messages.save_and_next') : __('messages.submit_decision') }}
                    </button>
                    @if($nextApplication ?? null)
                        <p class="form-hint text-center">{{ __('messages.save_and_next_hint') }}</p>
                    @endif
                </form>
                @else
                    @php
                        $currentDecision = $application->screening_status;
                        $isPassed = $currentDecision?->value === 'passed';
                    @endphp
                    <div class="rounded-xl border-2 p-4 {{ $isPassed ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                        <p class="text-xs font-medium uppercase tracking-wide {{ $isPassed ? 'text-green-700' : 'text-red-700' }}">{{ __('messages.current_decision') }}</p>
                        <p class="mt-1 flex items-center gap-2 text-lg font-bold {{ $isPassed ? 'text-green-800' : 'text-red-800' }}">
                            @if($isPassed)
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ __('messages.pass') }}
                            @else
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                {{ __('messages.fail') }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-gray-600">
                            {{ $application->screener?->name ?? '—' }}@if($application->screened_at) · {{ et_date($application->screened_at) }}@endif
                        </p>
                        @if($application->screening_remark)
                            <p class="mt-2 text-sm text-gray-700">{{ $application->screening_remark }}</p>
                        @endif
                    </div>

                    @if(! $application->screeningDecisionChangeable())
                        <p class="form-hint mt-3">{{ __('messages.decision_change_stage_locked') }}</p>
                    @elseif(! ($canChangeDecision ?? false))
                        <p class="form-hint mt-3">{{ __('messages.decision_change_no_permission') }}</p>
                    @else
                        @php $targetDecision = $isPassed ? 'failed' : 'passed'; @endphp
                        <div class="mt-4" x-data="{ open: @js($errors->has('reason') || $errors->has('decision')) }">
                            <button type="button" class="btn btn-secondary w-full justify-center" x-show="!open" @click="open = true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ __('messages.change_decision') }}
                            </button>

                            <form method="POST" action="{{ route('admin.screening.change-decision', $application) }}" class="space-y-4"
                                  x-show="open" x-cloak
                                  onsubmit="return confirm(@js(__('messages.decision_change_confirm')))">
                                @csrf
                                <input type="hidden" name="decision" value="{{ $targetDecision }}">

                                <div class="alert alert-warning text-sm">
                                    {{ __('messages.decision_change_to', ['decision' => $targetDecision === 'passed' ? __('messages.pass') : __('messages.fail')]) }}
                                </div>
                                @error('decision')<p class="form-error">{{ $message }}</p>@enderror

                                <div>
                                    <label for="reason" class="form-label">{{ __('messages.decision_change_reason') }} <span class="form-required">*</span></label>
                                    <textarea id="reason" name="reason" rows="4" required minlength="10" class="form-textarea"
                                              placeholder="{{ __('messages.decision_change_reason_placeholder') }}">{{ old('reason') }}</textarea>
                                    <p class="form-hint">{{ __('messages.decision_change_reason_hint') }}</p>
                                    @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-secondary flex-1 justify-center" @click="open = false">{{ __('messages.cancel') }}</button>
                                    <button type="submit" class="btn {{ $targetDecision === 'passed' ? 'btn-primary' : 'btn-danger' }} flex-1 justify-center">
                                        {{ __('messages.save_changed_decision') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            </div>

            @if ($application->screeningReviews->isNotEmpty())
                <div class="card card-body">
                    <div class="mb-3 flex items-center gap-2">
                        <h2 class="card-title">{{ __('messages.screening_history') }}</h2>
                    </div>
                    <div class="space-y-3">
                        @foreach ($application->screeningReviews as $review)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium {{ $review->decision->value === 'passed' ? 'text-green-700' : ($review->decision->value === 'correction_required' ? 'text-orange-600' : 'text-red-600') }}">{{ $review->decision->getLabel() }}</span>
                                    <span class="text-xs text-gray-600">{{ et_date($review->created_at) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $review->reviewer?->name }}
                                    @if(in_array($review->previous_status, ['passed_screening', 'failed_screening'], true))
                                        <span class="badge badge-amber ml-1">{{ __('messages.decision_changed_badge') }}</span>
                                    @endif
                                </p>
                                @if ($review->remark)
                                    <p class="mt-1 text-xs text-gray-600">{{ $review->remark }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Document preview modal --}}
    <div
        x-cloak
        x-show="previewOpen"
        x-on:keydown.escape.window="previewOpen = false; previewUrl = ''; previewName = ''"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4"
    >
        <div class="flex h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <div class="min-w-0">
                    <h2 class="truncate text-sm font-semibold text-gray-900" x-text="previewName"></h2>
                </div>
                <button
                    type="button"
                    @click="previewOpen = false; previewUrl = ''; previewName = ''"
                    class="rounded-md border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                >
                    {{ __('messages.close') }}
                </button>
            </div>
            <div class="flex-1 bg-gray-100">
                <iframe x-bind:src="previewUrl" class="h-full w-full" title="Document preview"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection
