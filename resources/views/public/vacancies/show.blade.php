@extends('layouts.public')

@php
    $locale       = app()->getLocale();
    $tr           = fn (string $field) => $vacancy->getTranslation($field, $locale, false) ?: $vacancy->getTranslation($field, 'en', false);
    $title        = $tr('title');
    $description  = $tr('description');
    $requirements = $tr('qualification_requirements');
    $loc          = $tr('location');
    $daysLeft     = (int) today()->diffInDays($vacancy->announcement->closing_date, false);
    $isPast       = $daysLeft < 0;
    $isUrgent     = ! $isPast && $daysLeft <= 6;
    $institution  = $vacancy->institution;
    $isApplicant  = auth()->check() && auth()->user()->hasRole('applicant');
    $applyUrl     = $isApplicant
        ? route('applicant.applications.create', $vacancy)
        : route('login') . '?redirect=' . urlencode(request()->url());
    $showApply    = ! $alreadyApplied && ! $isPast && $canApply && (! auth()->check() || $isApplicant);
    $shareUrl     = urlencode(request()->url());
@endphp

@section('title', $title)
@section('meta_description', Str::limit(trim(strip_tags((string) $description)), 160))

@section('content')

<x-public.page-header :title="$title"
                      :crumbs="[['label' => __('vacancies.job_vacancies'), 'url' => route('vacancies.index')], ['label' => Str::limit($title, 40)]]">
    <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-medium">
        @if($institution)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
            <x-public.icon name="building" class="h-3.5 w-3.5 text-white/60" />
            {{ $institution->name }}
        </span>
        @endif
        @if($vacancy->department)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
            <x-public.icon name="office" class="h-3.5 w-3.5 text-white/60" />
            {{ $vacancy->department }}
        </span>
        @endif
        @if($loc)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
            <x-public.icon name="map-pin" class="h-3.5 w-3.5 text-white/60" />
            {{ $loc }}
        </span>
        @endif
        @if($vacancy->employment_type)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15">
            <x-public.icon name="briefcase" class="h-3.5 w-3.5 text-white/60" />
            {{ $vacancy->employment_type->label() }}
        </span>
        @endif
        @if($vacancy->code)
        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 font-mono ring-1 ring-white/15">
            <x-public.icon name="hashtag" class="h-3.5 w-3.5 text-white/60" />
            {{ $vacancy->code }}
        </span>
        @endif
    </div>
</x-public.page-header>

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8 {{ $showApply ? 'pb-28 lg:pb-10' : '' }}">
    <div class="lg:grid lg:grid-cols-3 lg:gap-8">

        {{-- ── Main column ── --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Key facts --}}
            <section class="rounded-2xl border border-gray-200 bg-white shadow-card" aria-labelledby="facts-heading">
                <h2 id="facts-heading" class="sr-only">{{ __('public.key_details') }}</h2>
                <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-gray-100 sm:grid-cols-3">
                    @php
                        $facts = array_filter([
                            ['icon' => 'users',    'label' => __('vacancies.number_of_positions'), 'value' => $vacancy->number_of_positions],
                            ['icon' => 'academic', 'label' => __('vacancies.field_of_study'),      'value' => $vacancy->field_of_study],
                            ['icon' => 'clock',    'label' => __('vacancies.min_experience'),      'value' => $vacancy->minimum_experience !== null
                                ? $vacancy->minimum_experience . ' ' . ($locale === 'am' ? __('public.years') : ($vacancy->minimum_experience === 1 ? 'year' : 'years'))
                                : null],
                            ['icon' => 'currency', 'label' => __('vacancies.salary_grade'),        'value' => $vacancy->salary_grade],
                            ['icon' => 'calendar', 'label' => __('vacancies.opening_date'),        'value' => et_date($vacancy->announcement->opening_date, 'M d, Y')],
                            ['icon' => 'calendar', 'label' => __('vacancies.closing_date'),        'value' => et_date($vacancy->announcement->closing_date, 'M d, Y')],
                        ], fn ($f) => filled($f['value']));
                    @endphp
                    @foreach($facts as $fact)
                    <div class="bg-white p-4 sm:p-5">
                        <dt class="flex items-center gap-1.5 text-xs font-medium text-gray-500">
                            <x-public.icon :name="$fact['icon']" class="h-3.5 w-3.5 text-gray-400" />
                            {{ $fact['label'] }}
                        </dt>
                        <dd class="mt-1.5 text-sm font-semibold text-gray-900">{{ $fact['value'] }}</dd>
                    </div>
                    @endforeach
                    {{-- Fill the last row so the grid never shows a grey hole --}}
                    @for($i = 0; $i < (3 - count($facts) % 3) % 3; $i++)
                    <div class="hidden bg-white sm:block" aria-hidden="true"></div>
                    @endfor
                    @if(count($facts) % 2 === 1)
                    <div class="bg-white sm:hidden" aria-hidden="true"></div>
                    @endif
                </dl>
            </section>

            {{-- Description --}}
            @if($description)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card sm:p-8" aria-labelledby="desc-heading">
                <h2 id="desc-heading" class="flex items-center gap-2.5 text-lg font-bold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-muted text-brand"><x-public.icon name="document" /></span>
                    {{ __('vacancies.description') }}
                </h2>
                <div class="prose prose-sm mt-4 max-w-none leading-relaxed text-gray-700 sm:prose-base">
                    {!! nl2br(e($description)) !!}
                </div>
            </section>
            @endif

            {{-- Requirements --}}
            @if($requirements)
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card sm:p-8" aria-labelledby="req-heading">
                <h2 id="req-heading" class="flex items-center gap-2.5 text-lg font-bold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-muted text-brand"><x-public.icon name="clipboard-check" /></span>
                    {{ __('vacancies.requirements') }}
                </h2>
                <div class="prose prose-sm mt-4 max-w-none leading-relaxed text-gray-700 sm:prose-base">
                    {!! nl2br(e($requirements)) !!}
                </div>
            </section>
            @endif

            {{-- Eligibility requirement options (alternatives) --}}
            @php $options = $vacancy->requirementGroups->filter(fn ($g) => $g->requirements->isNotEmpty())->values(); @endphp
            @if($options->isNotEmpty())
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card sm:p-8" aria-labelledby="eligibility-heading">
                <h2 id="eligibility-heading" class="flex items-center gap-2.5 text-lg font-bold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-muted text-brand"><x-public.icon name="academic" /></span>
                    {{ __('vacancies.eligibility_requirements') }}
                </h2>
                @if($options->count() > 1)
                <p class="mt-2 text-sm text-gray-500">{{ __('vacancies.eligibility_any_option_hint') }}</p>
                @endif

                <ol class="mt-5 space-y-3">
                    @foreach($options as $group)
                    @if(! $loop->first)
                    <li class="relative flex items-center justify-center" aria-hidden="true">
                        <span class="absolute inset-x-0 h-px bg-gray-200"></span>
                        <span class="relative rounded-full bg-white px-3 text-xs font-bold uppercase tracking-widest text-accent">{{ __('vacancies.or') }}</span>
                    </li>
                    @endif
                    <li class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-sm font-bold text-brand">
                            {{ \App\Services\Eligibility\VacancyEligibilityChecker::optionLabel($loop->iteration, $group->title) }}
                        </p>
                        <ul class="mt-2 space-y-1.5">
                            @foreach($group->requirements as $req)
                            <li class="flex items-start gap-2 text-sm text-gray-700">
                                <x-public.icon name="check" class="mt-0.5 h-4 w-4 text-green-600" />
                                <span>
                                    {{ $req->summary() }}
                                    @if($req->notes)<span class="block text-xs text-gray-500">{{ $req->notes }}</span>@endif
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
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-card sm:p-8" aria-labelledby="docs-heading">
                <h2 id="docs-heading" class="flex items-center gap-2.5 text-lg font-bold text-gray-900">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-muted text-brand"><x-public.icon name="paperclip" /></span>
                    {{ __('vacancies.required_documents') }}
                </h2>
                <ul class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($vacancy->requiredDocuments as $doc)
                    <li class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $doc->is_required ? 'bg-white text-brand ring-1 ring-brand/20' : 'bg-white text-gray-400 ring-1 ring-gray-200' }}">
                            <x-public.icon name="document" class="h-3.5 w-3.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $doc->document_name }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                @if($doc->is_required)
                                <span class="rounded-full bg-red-50 px-2 py-0.5 font-semibold text-red-700">{{ __('public.required') }}</span>
                                @else
                                <span class="rounded-full bg-gray-200/70 px-2 py-0.5 font-medium text-gray-600">{{ __('public.optional') }}</span>
                                @endif
                                @if($doc->allowed_types)
                                <span>{{ implode(', ', array_map('strtoupper', $doc->allowed_types)) }}</span>
                                <span class="text-gray-300">·</span>
                                @endif
                                <span>{{ __('vacancies.detail_max') }} {{ $doc->max_size_mb }} MB</span>
                            </p>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif

            <a href="{{ route('vacancies.index') }}"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 transition hover:text-brand">
                <x-public.icon name="arrow-left" />
                {{ __('public.back_to_list') }}
            </a>
        </div>

        {{-- ── Sidebar ── --}}
        <aside class="mt-8 lg:mt-0">
            <div class="space-y-4 lg:sticky lg:top-24">

                {{-- Apply card --}}
                <div id="apply" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card">
                    {{-- Countdown --}}
                    <div class="border-b border-gray-100 p-5 {{ $isPast ? 'bg-red-50' : ($isUrgent ? 'bg-orange-50' : 'bg-brand-muted') }}">
                        @if($isPast)
                            <p class="flex items-center gap-2 text-sm font-semibold text-red-700">
                                <x-public.icon name="x-circle" class="h-5 w-5" />
                                {{ __('public.closed') }}
                            </p>
                        @else
                            <p class="text-xs font-semibold uppercase tracking-wide {{ $isUrgent ? 'text-orange-700' : 'text-brand' }}">{{ __('public.deadline') }}</p>
                            <p class="mt-1 flex items-baseline gap-2">
                                @if($daysLeft === 0)
                                <span class="text-2xl font-extrabold text-orange-700">{{ __('public.closes_today') }}</span>
                                @else
                                <span class="text-3xl font-extrabold tabular-nums {{ $isUrgent ? 'text-orange-700' : 'text-gray-900' }}">{{ $daysLeft }}</span>
                                <span class="text-sm font-medium text-gray-600">{{ __('public.days_left') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-gray-600">{{ et_date($vacancy->announcement->closing_date, 'M d, Y') }} · {{ et_diff_for_humans($vacancy->announcement->closing_date) }}</p>
                        @endif
                    </div>

                    <div class="space-y-3 p-5">
                        @if($alreadyApplied)
                            <div class="flex items-start gap-2.5 rounded-xl bg-green-50 px-4 py-3 ring-1 ring-green-200">
                                <x-public.icon name="check-circle" class="h-5 w-5 text-green-600" />
                                <p class="text-sm font-semibold text-green-800">{{ __('vacancies.already_applied') }}</p>
                            </div>
                            <a href="{{ route('applicant.applications.index') }}"
                               class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                {{ __('menus.my_applications') }}
                            </a>
                        @elseif($isPast)
                            <p class="text-sm text-red-700">{{ __('vacancies.deadline_passed') }}</p>
                        @elseif($canApply)
                            @if(! auth()->check() || $isApplicant)
                            <a href="{{ $applyUrl }}"
                               class="flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-accent/20 transition hover:bg-accent-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                                {{ __('vacancies.apply_now') }}
                                <x-public.icon name="arrow-right" />
                            </a>
                            @endif
                            @guest
                            <p class="text-center text-xs text-gray-500">
                                {{ __('vacancies.login_to_apply') }} ·
                                <a href="{{ route('applicant.register') }}" class="font-semibold text-brand hover:underline">{{ __('menus.register') }}</a>
                            </p>
                            @endguest
                        @else
                            <div class="rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-600 ring-1 ring-gray-200">
                                {{ __('vacancies.vacancy_not_open') }}
                            </div>
                        @endif

                        @if($institution)
                        <div class="flex items-center gap-3 rounded-xl bg-gray-50 p-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand ring-1 ring-gray-200">
                                <x-public.icon name="building" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-medium text-gray-500">{{ __('vacancies.recruiting_institution') }}</p>
                                <p class="truncate text-sm font-semibold text-gray-900" title="{{ $institution->name }}">{{ $institution->name }}</p>
                            </div>
                            @if($institution->latitude && $institution->longitude)
                            <a href="https://www.google.com/maps?q={{ $institution->latitude }},{{ $institution->longitude }}"
                               target="_blank" rel="noopener noreferrer"
                               class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-white hover:text-brand"
                               title="{{ __('admin.institution_open_in_maps') }}">
                                <x-public.icon name="map-pin" />
                                <span class="sr-only">{{ __('admin.institution_open_in_maps') }}</span>
                            </a>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Share --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-card"
                     x-data="{ copied: false, copy() { navigator.clipboard.writeText(window.location.href).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                    <p class="mb-3 text-xs font-semibold text-gray-600">{{ __('vacancies.share_vacancy') }}</p>
                    <div class="flex gap-2">
                        <button type="button" @click="copy()"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50">
                            <x-public.icon name="link" class="h-3.5 w-3.5" x-show="!copied" />
                            <x-public.icon name="check" class="h-3.5 w-3.5 text-green-600" x-show="copied" x-cloak />
                            <span x-text="copied ? @js(__('vacancies.link_copied')) : @js(__('vacancies.copy_link'))">{{ __('vacancies.copy_link') }}</span>
                        </button>
                        <a href="https://t.me/share/url?url={{ $shareUrl }}&text={{ urlencode($title) }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600"
                           aria-label="Telegram">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
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
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 px-4 py-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] backdrop-blur lg:hidden">
    <div class="mx-auto flex max-w-7xl items-center gap-3">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $title }}</p>
            <p class="text-xs {{ $isUrgent ? 'font-semibold text-orange-700' : 'text-gray-500' }}">
                {{ $daysLeft === 0 ? __('public.closes_today') : __('public.closes_in_days', ['days' => $daysLeft]) }}
            </p>
        </div>
        <a href="{{ $applyUrl }}"
           class="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-accent px-5 py-3 text-sm font-bold text-white transition hover:bg-accent-dark">
            {{ __('vacancies.apply_now') }}
        </a>
    </div>
</div>
@endif
@endsection
