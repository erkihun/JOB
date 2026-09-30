@extends('layouts.applicant')

@section('title', __('applicant.nav_dashboard'))

@section('content')
@php
    $locale = app()->getLocale();
    $title = fn ($vacancy) => $vacancy ? ($vacancy->getTranslation('title', $locale, false) ?: $vacancy->getTranslation('title', 'en', false)) : '—';
    $firstName = $applicant?->first_name ?: \Illuminate\Support\Str::of(auth()->user()->name)->before(' ');
    $tone = [
        'success' => 'bg-green-50 text-green-800',
        'danger'  => 'bg-red-50 text-red-800',
        'warning' => 'bg-accent-muted text-accent-dark',
        'info'    => 'bg-brand-muted text-brand-dark',
    ];

    // Most recent application that is still in progress (not closed).
    $latestActive = $applications->first(fn ($a) => ! in_array($a->status->value, ['failed_screening', 'not_selected', 'withdrawn'], true));

    // The single most useful next action, in priority order.
    $next = match (true) {
        $needsCorrection !== null => [
            'eyebrow' => __('applicant.next_action_needed'),
            'title'   => __('applicant.next_correct_title', ['vacancy' => $title($needsCorrection->vacancy)]),
            'text'    => __('applicant.next_correct_text'),
            'url'     => $needsCorrection->isEditable() ? route('applicant.applications.edit', $needsCorrection) : route('applicant.applications.show', $needsCorrection),
            'cta'     => __('applicant.next_correct_cta'),
            'date'    => null,
        ],
        $upcoming !== null => [
            'eyebrow' => __('applicant.next_step'),
            'title'   => $upcoming->schedule->type->getLabel().' — '.$title($upcoming->schedule->vacancy),
            'text'    => collect([et_date($upcoming->schedule->date, 'l'), $upcoming->schedule->start_time, $upcoming->schedule->venue])->filter()->implode(' · '),
            'url'     => route('applicant.applications.show', $upcoming->application),
            'cta'     => __('applicant.view_details'),
            'date'    => $upcoming->schedule->date,
        ],
        $latestActive !== null => [
            'eyebrow' => __('applicant.next_update'),
            'title'   => $latestActive->status->label().' — '.$title($latestActive->vacancy),
            'text'    => \App\Support\ApplicationProgress::nextText($latestActive),
            'url'     => route('applicant.applications.show', $latestActive),
            'cta'     => __('applicant.view_details'),
            'date'    => null,
        ],
        $applicant && $completionPct < 100 => [
            'eyebrow' => __('applicant.next_step'),
            'title'   => __('applicant.next_profile_title'),
            'text'    => __('applicant.next_profile_text', ['percent' => $completionPct]),
            'url'     => route('applicant.profile.edit'),
            'cta'     => __('applicant.complete_profile'),
            'date'    => null,
        ],
        $applicationStats['total'] === 0 => [
            'eyebrow' => __('applicant.next_step'),
            'title'   => __('applicant.next_apply_title'),
            'text'    => __('applicant.next_apply_text'),
            'url'     => route('applicant.vacancies.index'),
            'cta'     => __('applicant.browse_jobs'),
            'date'    => null,
        ],
        default => null,
    };
@endphp
<div class="space-y-6">

    {{-- Welcome --}}
    <x-applicant.page-header :title="__('applicant.welcome', ['name' => $firstName])"
                             :description="collect([
                                 $applicant?->applicant_code ? __('applicant.applicant_id').' '.$applicant->applicant_code : null,
                                 trans_choice('applicant.active_count', $applicationStats['active'] + $applicationStats['positive'], ['count' => $applicationStats['active'] + $applicationStats['positive']]),
                             ])->filter()->implode(' · ')">
        <a href="{{ route('applicant.vacancies.index') }}" class="inline-flex h-11 items-center gap-2 rounded-xl bg-accent-dark px-5 text-[15px] font-extrabold text-white transition hover:bg-accent">
            {{ __('public.find_jobs') }}
        </a>
    </x-applicant.page-header>

    {{-- Next step --}}
    @if($next)
    <section class="flex flex-col gap-5 rounded-2xl bg-ink p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-7" aria-labelledby="next-heading">
        <div class="flex items-center gap-4">
            @if($next['date'])
            <span class="flex w-16 shrink-0 flex-col items-center rounded-xl bg-white py-2 text-ink">
                <span class="text-2xl font-extrabold leading-none">{{ et_date($next['date'], 'd') }}</span>
                <span class="mt-0.5 text-xs font-extrabold uppercase">{{ et_date($next['date'], 'M') }}</span>
            </span>
            @endif
            <div class="min-w-0">
                <p class="text-[13px] font-extrabold uppercase tracking-wider text-brand-muted">{{ $next['eyebrow'] }}</p>
                <h2 id="next-heading" class="mt-1 text-xl font-extrabold leading-snug">{{ $next['title'] }}</h2>
                <p class="mt-1 text-[15px] text-white/75">{{ $next['text'] }}</p>
            </div>
        </div>
        <a href="{{ $next['url'] }}" class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl bg-white px-5 text-[15px] font-extrabold text-ink transition hover:bg-gray-100">{{ $next['cta'] }}</a>
    </section>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_21rem] lg:items-start">

        {{-- My applications --}}
        <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="apps-heading">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h2 id="apps-heading" class="text-lg font-extrabold text-gray-900">{{ __('applicant.my_applications') }}</h2>
                @if($applicationStats['total'] > 0)
                <a href="{{ route('applicant.applications.index') }}" class="text-sm font-bold text-brand hover:underline">{{ __('public.view_all') }}</a>
                @endif
            </div>
            @forelse($applications as $application)
            <a href="{{ route('applicant.applications.show', $application) }}" class="block border-b border-gray-100 px-5 py-4 transition last:border-0 hover:bg-gray-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-gray-900">{{ $title($application->vacancy) }}</p>
                        <p class="mt-0.5 text-[13px] text-gray-600"><span class="font-mono">{{ $application->reference_number }}</span>@if($application->submitted_at) · {{ __('applicant.applied_on', ['date' => et_date($application->submitted_at, 'M d, Y')]) }}@endif</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $tone[\App\Support\ApplicationProgress::tone($application->status)] }}">{{ $application->status->label() }}</span>
                </div>
                <x-applicant.tracker :application="$application" class="mt-3.5" />
            </a>
            @empty
            <div class="px-5 py-10 text-center">
                <p class="font-bold text-gray-900">{{ __('applicant.no_applications_yet') }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ __('applicant.start_applying') }}</p>
                <a href="{{ route('applicant.vacancies.index') }}" class="mt-4 inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark">{{ __('applicant.browse_jobs') }}</a>
            </div>
            @endforelse
        </section>

        <aside class="space-y-6">
            {{-- Profile strength --}}
            @if($applicant)
            <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="profile-heading">
                <div class="flex items-center gap-4">
                    <div class="relative h-16 w-16 shrink-0 rounded-full" style="background: conic-gradient(var(--color-brand) {{ $completionPct * 3.6 }}deg, #E3EAEC 0deg);" role="img" aria-label="{{ $completionPct }}%">
                        <span class="absolute inset-1.5 flex items-center justify-center rounded-full bg-white text-sm font-extrabold text-gray-900">{{ $completionPct }}%</span>
                    </div>
                    <div>
                        <h2 id="profile-heading" class="font-extrabold text-gray-900">{{ $completionPct === 100 ? __('applicant.profile_complete') : __('applicant.profile_percent', ['percent' => $completionPct]) }}</h2>
                        <p class="mt-0.5 text-[13px] text-gray-600">{{ __('applicant.profile_strength_hint') }}</p>
                    </div>
                </div>
                @if($completionMissing)
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach(array_slice($completionMissing, 0, 4) as $field)
                    <li class="flex items-center gap-2 font-medium text-accent-dark"><span class="h-1.5 w-1.5 rounded-full bg-accent" aria-hidden="true"></span>{{ $field }}</li>
                    @endforeach
                </ul>
                @endif
                <a href="{{ route($completionPct === 100 ? 'applicant.profile.show' : 'applicant.profile.edit') }}"
                   class="mt-4 flex h-11 items-center justify-center rounded-xl border border-gray-300 text-sm font-bold text-gray-900 transition hover:bg-gray-50">
                    {{ $completionPct === 100 ? __('applicant.view_profile') : __('applicant.complete_profile') }}
                </a>
            </section>
            @endif

            {{-- Messages --}}
            <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="msg-heading">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <h2 id="msg-heading" class="font-extrabold text-gray-900">{{ __('menus.notifications') }}</h2>
                    @if($unreadMessages > 0)
                    <span class="rounded-full bg-accent-dark px-2 py-0.5 text-xs font-bold text-white">{{ trans_choice('applicant.new_count', $unreadMessages, ['count' => $unreadMessages]) }}</span>
                    @endif
                </div>
                @forelse($messages as $message)
                <a href="{{ route('applicant.notifications.index') }}" class="flex gap-3 border-b border-gray-100 px-5 py-3 last:border-0 hover:bg-gray-50">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $message->read_at ? 'bg-gray-300' : 'bg-accent' }}" aria-hidden="true"></span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $message->subject }}</span>
                        <span class="text-xs text-gray-600">{{ et_diff_for_humans($message->created_at) }}</span>
                    </span>
                </a>
                @empty
                <p class="px-5 py-5 text-sm text-gray-600">{{ __('applicant.no_notifications') }}</p>
                @endforelse
            </section>
        </aside>
    </div>

    {{-- Suggested vacancies --}}
    @if($suggested->isNotEmpty())
    <section aria-labelledby="jobs-heading">
        <div class="mb-3 flex items-end justify-between gap-3">
            <h2 id="jobs-heading" class="text-xl font-extrabold text-gray-900">{{ __('applicant.suggested_jobs') }}</h2>
            <a href="{{ route('applicant.vacancies.index') }}" class="text-sm font-bold text-brand hover:underline">{{ __('applicant.browse_all') }}</a>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            @foreach($suggested as $vacancy)
            @php $days = (int) today()->diffInDays($vacancy->announcement->closing_date, false); @endphp
            <a href="{{ route('applicant.vacancies.show', $vacancy) }}" class="flex flex-col gap-1.5 rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand/50 hover:shadow-card-hover">
                <span class="text-base font-bold text-brand-dark">{{ $title($vacancy) }}</span>
                <span class="text-sm text-gray-600">{{ collect([$vacancy->department, trans_choice('public.positions_count', (int) $vacancy->number_of_positions, ['count' => (int) $vacancy->number_of_positions])])->filter()->implode(' · ') }}</span>
                <span class="mt-2 self-start rounded-full px-2.5 py-0.5 text-xs font-bold {{ $days <= 6 ? 'bg-accent-muted text-accent-dark' : 'bg-brand-muted text-brand-dark' }}">
                    {{ trans_choice('public.days_left_count', max($days, 0), ['count' => max($days, 0)]) }}
                </span>
            </a>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
