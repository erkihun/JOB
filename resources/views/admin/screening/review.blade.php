@extends('layouts.admin')
@section('title', __('menus.screening').': '.$application->reference_number)

@section('content')
<div class="space-y-6" x-data="{ previewOpen: false, previewUrl: '', previewName: '' }">

    <div>
        <a href="{{ route('admin.screening.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.screening') }}</a>
        <h1 class="mt-1 text-lg font-semibold text-gray-900">{{ __('messages.review_application') }}: {{ $application->reference_number }}</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="space-y-5 lg:col-span-2">

            {{-- Personal Information --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.personal_info') }}</h2>
                </div>
                <div class="mb-5 flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-brand-muted text-base font-semibold text-brand ring-1 ring-brand/10">
                        {{ mb_substr($application->applicant?->full_name ?? '?', 0, 2) }}
                    </div>
                    <div>
                        <p class="text-base font-semibold text-gray-900">{{ $application->applicant?->full_name }}</p>
                        <p class="text-sm text-gray-500">{{ $canViewSensitive ? ($application->applicant?->email ?? '--') : __('dashboard.restricted') }}</p>
                        @if($application->applicant?->applicant_code)
                            <p class="mt-0.5 font-mono text-xs text-gray-400">{{ $application->applicant->applicant_code }}</p>
                        @endif
                    </div>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @php
                        $personalFields = [
                            __('fields.gender')           => $application->applicant?->gender?->getLabel(),
                            __('fields.date_of_birth')    => et_date($application->applicant?->date_of_birth),
                            __('fields.nationality')      => $application->applicant?->nationality,
                            __('fields.ethnicity')        => $application->applicant?->ethnicity,
                            __('fields.national_id')      => $canViewSensitive ? $application->applicant?->national_id : __('dashboard.restricted'),
                            __('fields.phone')            => $canViewSensitive ? $application->applicant?->phone : __('dashboard.restricted'),
                            __('fields.alternative_phone')=> $canViewSensitive ? $application->applicant?->alternative_phone : __('dashboard.restricted'),
                        ];
                    @endphp
                    @foreach ($personalFields as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{-- Disability (always show status; show type only if true) --}}
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <dl class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-400">{{ __('fields.disability_status') }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">
                                {{ $application->applicant?->disability_status ? __('applicant.disability_yes') : __('applicant.disability_no') }}
                            </dd>
                        </div>
                        @if($application->applicant?->disability_status)
                            <div>
                                <dt class="text-xs text-gray-400">{{ __('fields.disability_type') }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $application->applicant?->disability_type ?? '--' }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Address --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.contact_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        __('fields.region')  => $application->applicant?->region,
                        __('fields.city')    => $application->applicant?->city,
                        __('fields.woreda')  => $application->applicant?->woreda,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($application->applicant?->address)
                    <div class="mt-3">
                        <dt class="text-xs text-gray-400">{{ __('fields.address') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $application->applicant->address }}</dd>
                    </div>
                @endif
            </div>

            {{-- Education --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-navy"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.education_info') }}</h2>
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
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- Work Experience --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.work_info') }}</h2>
                </div>
                <dl class="grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        __('fields.work_experience_years')  => $application->applicant?->work_experience_years !== null ? $application->applicant->work_experience_years.' yrs' : null,
                        __('fields.work_experience_months') => $application->applicant?->work_experience_months !== null ? $application->applicant->work_experience_months.' mo' : null,
                        __('fields.current_employer')       => $application->applicant?->current_employer,
                        __('fields.current_position')       => $application->applicant?->current_position,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs text-gray-400">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-800">{{ $value ?? '--' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if($application->applicant?->work_experience_summary)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <dt class="text-xs text-gray-400">{{ __('fields.work_experience_summary') }}</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ $application->applicant->work_experience_summary }}</dd>
                    </div>
                @endif
            </div>

            {{-- Documents --}}
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-brand"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('applicant.uploaded_documents') }}</h2>
                </div>
                @forelse ($application->applicant?->profileDocuments ?? collect() as $doc)
                    <div class="mb-2 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $doc->original_name }}</p>
                            <p class="text-xs text-gray-400">{{ $doc->document_type }} · {{ $doc->file_size_mb }} MB</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $previewable = str_starts_with((string) $doc->file_type, 'image/') || $doc->file_type === 'application/pdf';
                            @endphp
                            @if ($previewable)
                                <button
                                    type="button"
                                    @click="previewUrl = '{{ route('admin.profile-documents.preview', $doc) }}'; previewName = @js($doc->original_name); previewOpen = true"
                                    class="rounded-md border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700 transition hover:bg-orange-100"
                                >
                                    {{ __('dashboard.actions.view') }}
                                </button>
                            @endif
                            <a href="{{ route('admin.profile-documents.download', $doc) }}"
                               class="rounded-md border border-brand/20 bg-brand-muted px-3 py-1 text-xs font-medium text-brand transition hover:bg-blue-100">
                                {{ __('menus.download') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">{{ __('applicant.no_documents') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-5">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-3 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('vacancies.vacancy') }}</h2>
                </div>
                <p class="font-semibold text-gray-900">{{ $application->vacancy?->title }}</p>
                <p class="mt-0.5 font-mono text-xs text-gray-400">{{ $application->vacancy?->code }}</p>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('vacancies.positions') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->vacancy?->number_of_positions }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">{{ __('vacancies.minimum_experience') }}</span>
                        <span class="font-medium text-gray-800">{{ $application->vacancy?->minimum_experience ?? 0 }} yrs</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-navy"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.screening_decision') }}</h2>
                </div>
                <form method="POST" action="{{ route('admin.screening.submit', $application) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('messages.decision') }} <span class="text-red-500">*</span></label>
                        <div class="mt-2 space-y-2">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="decision" value="passed" {{ old('decision') === 'passed' ? 'checked' : '' }} class="h-4 w-4 text-green-600 focus:ring-green-500">
                                <span class="text-sm font-medium text-green-700">✓ {{ __('messages.pass') }}</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="decision" value="failed" {{ old('decision') === 'failed' ? 'checked' : '' }} class="h-4 w-4 text-red-500 focus:ring-red-500">
                                <span class="text-sm font-medium text-red-600">✕ {{ __('messages.fail') }}</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="decision" value="correction_required" {{ old('decision') === 'correction_required' ? 'checked' : '' }} class="h-4 w-4 text-orange-500 focus:ring-orange-500">
                                <span class="text-sm font-medium text-orange-600">{{ __('statuses.screening.correction_required') }}</span>
                            </label>
                        </div>
                        @error('decision')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="remark" class="block text-sm font-medium text-gray-700">{{ __('messages.remarks') }}</label>
                        <textarea id="remark" name="remark" rows="4"
                                  class="form-textarea mt-1"
                                  placeholder="{{ __('messages.remarks_placeholder') }}">{{ old('remark') }}</textarea>
                        @error('remark')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full justify-center">
                        {{ __('messages.submit_decision') }}
                    </button>
                </form>
            </div>

            @if ($application->screeningReviews->isNotEmpty())
                <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="mb-3 flex items-center gap-2">
                        <div class="h-4 w-0.5 rounded bg-navy"></div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.screening_history') }}</h2>
                    </div>
                    <div class="space-y-3">
                        @foreach ($application->screeningReviews as $review)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium {{ $review->decision->value === 'passed' ? 'text-green-700' : ($review->decision->value === 'correction_required' ? 'text-orange-600' : 'text-red-600') }}">{{ $review->decision->getLabel() }}</span>
                                    <span class="text-xs text-gray-400">{{ et_date($review->created_at) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $review->reviewer?->name }}</p>
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
                    {{ __('admin.actions.close') }}
                </button>
            </div>
            <div class="flex-1 bg-gray-100">
                <iframe x-bind:src="previewUrl" class="h-full w-full" title="Document preview"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection
