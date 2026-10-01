@extends('layouts.public')

@php
    $locale       = app()->getLocale();
    $tr           = fn (string $field) => $vacancy->getTranslation($field, $locale, false) ?: $vacancy->getTranslation($field, 'en', false);
    $title        = $tr('title');
    $description  = $tr('description');
    $requirements = $tr('qualification_requirements');
    $loc          = $tr('location');
    $closing      = $vacancy->announcement->closing_date;
    $daysLeft     = (int) today()->diffInDays($closing, false);
    $isPast       = $vacancy->isPastDeadline();
    $isUpcoming   = ! $isPast && app(\App\Services\Recruitment\RecruitmentTimelineService::class)->isUpcoming($vacancy->announcement);
    $opensOn      = __('recruitment.applicant.opens_on', ['date' => et_date($vacancy->announcement->opening_date, 'M d, Y')]);
    $isUrgent     = ! $isPast && $daysLeft <= 6;
    $institution  = $vacancy->institution;
    $isApplicant  = auth()->check() && auth()->user()->hasRole('applicant');
    $applyUrl     = $isApplicant
        ? route('applicant.applications.create', $vacancy)
        : route('login') . '?redirect=' . urlencode(request()->url());
    $showApply    = ! $alreadyApplied && ! $isPast && $canApply && (! auth()->check() || $isApplicant);
    $shareUrl     = urlencode(request()->url());
    $options      = $vacancy->requirementGroups->filter(fn ($g) => $g->requirements->isNotEmpty())->values();
    $deadlineText = $isPast ? __('public.closed')
        : ($daysLeft === 0 ? __('public.closes_today') : trans_choice('public.closes_in_days_count', $daysLeft, ['count' => $daysLeft]));

    $facts = array_values(array_filter([
        [__('vacancies.number_of_positions'), $vacancy->number_of_positions],
        [__('vacancies.employment_type'),     $vacancy->employment_type?->label()],
        [__('vacancies.field_of_study'),      $vacancy->field_of_study],
        [__('vacancies.min_experience'),      $vacancy->minimum_experience !== null ? trans_choice('public.years_count', (int) $vacancy->minimum_experience, ['count' => $vacancy->minimum_experience]) : null],
        [__('vacancies.salary_grade'),        $vacancy->salary_grade],
        [__('vacancies.opening_date'),        et_date($vacancy->announcement->opening_date, 'M d, Y')],
        [__('vacancies.closing_date'),        et_date($closing, 'M d, Y')],
    ], fn ($f) => filled($f[1])));

    $sections = array_filter([
        'overview'    => ($description || $requirements) ? __('public.section_overview') : null,
        'eligibility' => $options->isNotEmpty() ? __('public.section_who_can_apply') : null,
        'documents'   => $vacancy->requiredDocuments->isNotEmpty() ? __('vacancies.required_documents') : null,
        'how-to-apply' => __('public.how_it_works'),
    ]);
    $card = 'scroll-mt-40 rounded-2xl border border-gray-200 bg-white p-6 sm:p-8';
    $h2   = 'text-xl font-extrabold text-gray-900 sm:text-2xl';
@endphp

@section('title', $title)
@section('meta_description', Str::limit(trim(strip_tags((string) $description)), 160))

@section('content')

<x-public.page-header :title="$title"
                      :crumbs="[['label' => __('vacancies.job_vacancies'), 'url' => route('vacancies.index')], ['label' => Str::limit($title, 40)]]">
    <div class="mt-3 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-3">
            <p class="text-base text-gray-700 sm:text-lg">
                {{ collect([$vacancy->department, $institution?->name])->filter()->implode(' · ') }}
            </p>
            <ul class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-700">
                @if($vacancy->code)
                <li class="rounded-md bg-gray-100 px-2.5 py-1 font-mono">{{ $vacancy->code }}</li>
                @endif
                @if($loc)
                <li class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1"><x-public.icon name="map-pin" class="h-3.5 w-3.5 text-gray-500" />{{ $loc }}</li>
                @endif
                @if($vacancy->employment_type)
                <li class="rounded-md bg-gray-100 px-2.5 py-1">{{ $vacancy->employment_type->label() }}</li>
                @endif
                @if($vacancy->number_of_positions)
                <li class="rounded-md bg-gray-100 px-2.5 py-1">{{ trans_choice('public.positions_count', $vacancy->number_of_positions, ['count' => $vacancy->number_of_positions]) }}</li>
                @endif
                <li class="rounded-full px-2.5 py-1 font-bold {{ $isPast ? 'bg-red-50 text-red-700' : ($isUrgent ? 'bg-accent-muted text-accent-dark' : 'bg-brand-muted text-brand-dark') }}">{{ $deadlineText }}</li>
            </ul>
        </div>
        @if($showApply)
        <div class="hidden shrink-0 flex-col items-stretch gap-1.5 lg:flex lg:w-60">
            <a href="{{ $applyUrl }}"
               class="flex h-13 items-center justify-center gap-2 rounded-xl bg-accent-dark px-6 text-base font-extrabold text-white transition hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                {{ __('vacancies.apply_now') }}
                <x-public.icon name="arrow-right" class="h-4 w-4" />
            </a>
            <span class="text-center text-sm font-semibold {{ $isUrgent ? 'text-accent-dark' : 'text-gray-600' }}">{{ __('public.closes') }} {{ et_date($closing, 'M d, Y') }}</span>
        </div>
        @endif
    </div>
</x-public.page-header>

{{-- In-page navigation --}}
@if(count($sections) > 1)
<nav aria-label="{{ __('public.on_this_page') }}" class="sticky top-16 z-30 border-b border-gray-200 bg-white/95 backdrop-blur lg:top-18">
    <ul class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 text-[15px] font-bold sm:px-6 lg:px-8">
        @foreach($sections as $id => $label)
        <li><a href="#{{ $id }}" class="block whitespace-nowrap border-b-[3px] border-transparent px-3 py-3.5 text-gray-600 transition hover:border-gray-300 hover:text-gray-900">{{ $label }}</a></li>
        @endforeach
    </ul>
</nav>
@endif

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 {{ $showApply ? 'pb-28 lg:pb-12' : 'pb-12' }}">
    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start lg:gap-8">

        {{-- ── Main column ── --}}
        <div class="space-y-5">

            @if($description || $requirements)
            <section id="overview" class="{{ $card }}" aria-labelledby="desc-heading">
                <h2 id="desc-heading" class="{{ $h2 }}">{{ __('public.about_the_role') }}</h2>
                @if($description)
                <div class="mt-4 text-base leading-relaxed text-gray-700">{!! nl2br(e($description)) !!}</div>
                @endif
                @if($requirements)
                <h3 class="mt-7 text-lg font-extrabold text-gray-900">{{ __('vacancies.requirements') }}</h3>
                <div class="mt-3 text-base leading-relaxed text-gray-700">{!! nl2br(e($requirements)) !!}</div>
                @endif
            </section>
            @endif

            {{-- Eligibility requirement options (alternatives) --}}
            @if($options->isNotEmpty())
            <section id="eligibility" class="{{ $card }}" aria-labelledby="eligibility-heading">
                <h2 id="eligibility-heading" class="{{ $h2 }}">{{ __('vacancies.eligibility_requirements') }}</h2>
                @if($options->count() > 1)
                <p class="mt-2 text-[15px] text-gray-600">{{ __('vacancies.eligibility_any_option_hint') }}</p>
                @endif

                <ol class="mt-5 grid gap-3 {{ $options->count() > 1 ? 'md:grid-cols-2' : '' }}">
                    @foreach($options as $group)
                    <li class="relative rounded-xl border p-5 {{ $loop->first ? 'border-brand/30 bg-brand-muted/50' : 'border-gray-200 bg-gray-50' }}">
                        @if(! $loop->first)
                        <span class="absolute -top-3 left-5 rounded-full bg-ink px-2.5 py-0.5 text-xs font-extrabold uppercase tracking-wider text-white md:-left-5 md:top-1/2 md:-translate-y-1/2">{{ __('vacancies.or') }}</span>
                        @endif
                        <p class="text-sm font-extrabold uppercase tracking-wide text-brand-dark">
                            {{ \App\Services\Eligibility\VacancyEligibilityChecker::optionLabel($loop->iteration, $group->title) }}
                        </p>
                        <ul class="mt-3 space-y-2">
                            @foreach($group->requirements as $req)
                            <li class="flex items-start gap-2.5 text-[15px] text-gray-800">
                                <x-public.icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                                <span>
                                    {{ $req->summary() }}
                                    @if($req->notes)<span class="block text-sm text-gray-600">{{ $req->notes }}</span>@endif
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach
                </ol>
            </section>
            @endif

            {{-- Required documents --}}
            @if($vacancy->requiredDocuments->isNotEmpty())
            <section id="documents" class="{{ $card }}" aria-labelledby="docs-heading">
                <h2 id="docs-heading" class="{{ $h2 }}">{{ __('vacancies.required_documents') }}</h2>
                <ul class="mt-5 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200">
                    @foreach($vacancy->requiredDocuments as $doc)
                    <li class="flex flex-col gap-2 px-4 py-3.5 sm:flex-row sm:items-center sm:gap-4">
                        <span class="flex min-w-0 flex-1 items-center gap-3">
                            <x-public.icon name="document" class="h-5 w-5 shrink-0 text-gray-400" />
                            <span class="text-[15px] font-bold text-gray-900">{{ $doc->document_name }}</span>
                        </span>
                        <span class="flex items-center gap-3 pl-8 sm:pl-0">
                            @if($doc->is_required)
                            <span class="rounded-full bg-red-50 px-2.5 py-0.5 text-[13px] font-bold text-red-700">{{ __('public.required') }}</span>
                            @else
                            <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-[13px] font-bold text-gray-600">{{ __('public.optional') }}</span>
                            @endif
                            <span class="text-sm text-gray-600">
                                @if($doc->allowed_types){{ implode(', ', array_map('strtoupper', $doc->allowed_types)) }} · @endif{{ __('vacancies.detail_max') }} {{ $doc->max_size_mb }} MB
                            </span>
                        </span>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- How to apply --}}
            <section id="how-to-apply" class="{{ $card }}" aria-labelledby="how-heading">
                <h2 id="how-heading" class="{{ $h2 }}">{{ __('public.how_it_works') }}</h2>
                <ol class="mt-5 space-y-4">
                    @foreach([__('public.apply_step_1'), __('public.apply_step_2'), __('public.apply_step_3')] as $step)
                    <li class="flex items-start gap-4">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-extrabold text-white">{{ $loop->iteration }}</span>
                        <span class="pt-1 text-base leading-relaxed text-gray-700">{{ $step }}</span>
                    </li>
                    @endforeach
                </ol>
            </section>

            <a href="{{ route('vacancies.index') }}"
               class="inline-flex items-center gap-1.5 text-[15px] font-bold text-brand hover:underline">
                <x-public.icon name="arrow-left" class="h-4 w-4" />
                {{ __('public.back_to_list') }}
            </a>
        </div>

        {{-- ── Summary sidebar ── --}}
        <aside class="mt-8 lg:mt-0">
            <div class="space-y-4 lg:sticky lg:top-36">
                <div id="apply" class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <div class="border-b p-5 {{ $isPast ? 'border-red-100 bg-red-50' : ($isUrgent ? 'border-accent/20 bg-accent-muted' : 'border-brand/15 bg-brand-muted') }}">
                        @if($isPast)
                            <p class="flex items-center gap-2 text-base font-bold text-red-700">
                                <x-public.icon name="x-circle" class="h-5 w-5" />
                                {{ __('recruitment.applicant.closed') }}
                            </p>
                        @elseif($isUpcoming)
                            <p class="text-[13px] font-extrabold uppercase tracking-wider text-gray-600">{{ __('recruitment.stage.upcoming') }}</p>
                            <p class="mt-1 text-lg font-extrabold text-gray-900">{{ $opensOn }}</p>
                        @else
                            <p class="text-[13px] font-extrabold uppercase tracking-wider {{ $isUrgent ? 'text-accent-dark' : 'text-brand-dark' }}">{{ __('public.deadline') }}</p>
                            <p class="mt-1 flex items-baseline gap-2">
                                @if($daysLeft === 0)
                                <span class="text-2xl font-extrabold text-accent-dark">{{ __('public.closes_today') }}</span>
                                @else
                                <span class="text-4xl font-extrabold tabular-nums {{ $isUrgent ? 'text-accent-dark' : 'text-ink' }}">{{ $daysLeft }}</span>
                                <span class="text-base font-bold text-gray-700">{{ __('public.days_left') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm text-gray-700">{{ et_date($closing, 'M d, Y') }} · {{ et_diff_for_humans($closing) }}</p>
                        @endif
                    </div>

                    <dl class="px-5 py-2">
                        @foreach($facts as [$label, $value])
                        <div class="flex justify-between gap-4 border-b border-gray-100 py-3 text-[15px] last:border-0">
                            <dt class="text-gray-600">{{ $label }}</dt>
                            <dd class="text-right font-bold text-gray-900">{{ $value }}</dd>
                        </div>
                        @endforeach
                    </dl>

                    <div class="space-y-3 border-t border-gray-100 p-5">
                        @if($alreadyApplied)
                            <div class="flex items-start gap-2.5 rounded-xl bg-green-50 px-4 py-3 ring-1 ring-green-200">
                                <x-public.icon name="check-circle" class="h-5 w-5 text-green-600" />
                                <p class="text-sm font-bold text-green-800">{{ __('vacancies.already_applied') }}</p>
                            </div>
                            <a href="{{ route('applicant.applications.index') }}"
                               class="flex h-12 w-full items-center justify-center rounded-xl border border-gray-300 text-[15px] font-bold text-gray-900 transition hover:bg-gray-50">
                                {{ __('menus.my_applications') }}
                            </a>
                        @elseif($isPast)
                            <p class="text-sm text-red-700">{{ __('vacancies.deadline_passed') }}</p>
                        @elseif($isUpcoming)
                            <div class="rounded-xl bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-800 ring-1 ring-gray-200">{{ $opensOn }}</div>
                        @elseif($canApply)
                            @if(! auth()->check() || $isApplicant)
                            <a href="{{ $applyUrl }}"
                               class="flex h-13 w-full items-center justify-center gap-2 rounded-xl bg-accent-dark text-base font-extrabold text-white transition hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                                {{ __('vacancies.apply_now') }}
                                <x-public.icon name="arrow-right" class="h-4 w-4" />
                            </a>
                            @endif
                            @guest
                            <p class="text-center text-sm text-gray-600">
                                {{ __('vacancies.login_to_apply') }} ·
                                <a href="{{ route('applicant.register') }}" class="font-bold text-brand hover:underline">{{ __('public.create_account') }}</a>
                            </p>
                            @endguest
                        @else
                            <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700 ring-1 ring-gray-200">
                                {{ __('vacancies.vacancy_not_open') }}
                            </div>
                        @endif

                        @if($institution && $institution->latitude && $institution->longitude)
                        <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                           target="_blank" rel="noopener noreferrer"
                           class="flex items-center gap-2 rounded-xl px-1 py-1 text-sm font-bold text-brand hover:underline">
                            <x-public.icon name="map-pin" class="h-4 w-4" />
                            {{ __('admin.institution_open_in_maps') }}
                        </a>
                        @endif
                    </div>
                </div>

                {{-- Share --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5"
                     x-data="{ copied: false, copy() { navigator.clipboard.writeText(window.location.href).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                    <p class="mb-3 text-[15px] font-extrabold text-gray-900">{{ __('vacancies.share_vacancy') }}</p>
                    <div class="flex gap-2">
                        <button type="button" @click="copy()"
                                class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-gray-300 px-3 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                            <x-public.icon name="link" class="h-4 w-4" x-show="!copied" />
                            <x-public.icon name="check" class="h-4 w-4 text-green-600" x-show="copied" x-cloak />
                            <span x-text="copied ? @js(__('vacancies.link_copied')) : @js(__('vacancies.copy_link'))">{{ __('vacancies.copy_link') }}</span>
                        </button>
                        <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ urlencode($title) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-300 text-gray-600 transition hover:bg-gray-50 hover:text-sky-600"
                           aria-label="Telegram">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-300 text-gray-600 transition hover:bg-gray-50 hover:text-blue-600"
                           aria-label="Facebook">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

{{-- Mobile sticky apply bar --}}
@if($showApply)
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white px-4 py-3 shadow-[0_-8px_24px_rgba(0,0,0,0.08)] lg:hidden">
    <div class="mx-auto flex max-w-7xl items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-bold text-gray-900">{{ $title }}</p>
            <p class="text-sm font-bold {{ $isUrgent ? 'text-accent-dark' : 'text-gray-600' }}">{{ $deadlineText }}</p>
        </div>
        <a href="{{ $applyUrl }}"
           class="inline-flex h-12 shrink-0 items-center rounded-xl bg-accent-dark px-6 text-base font-extrabold text-white transition hover:bg-accent">
            {{ __('vacancies.apply_now') }}
        </a>
    </div>
</div>
@endif
@endsection
