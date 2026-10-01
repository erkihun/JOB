@extends('layouts.public')

@section('title', __('recruitment.applicant.registration_closed_title'))

@section('content')
<x-public.page-header :title="__('applicant.register_heading')"
                      :crumbs="[['label' => __('public.create_account')]]"
                      width="max-w-3xl" />

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <section class="rounded-2xl border border-accent/30 bg-white p-6 sm:p-8" role="status" aria-labelledby="registration-closed-heading">
        <div class="flex items-start gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-accent-muted text-accent-dark" aria-hidden="true">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            </span>
            <div class="min-w-0">
                <h2 id="registration-closed-heading" class="text-xl font-extrabold text-gray-900">{{ __('recruitment.applicant.registration_closed_title') }}</h2>
                <p class="mt-2 text-[15px] font-semibold text-accent-dark">{{ __('recruitment.errors.registration_closed') }}</p>
                <p class="mt-2 text-[15px] text-gray-700">{{ __('recruitment.applicant.registration_closed_body') }}</p>
                @if($nextAnnouncement)
                    <p class="mt-3 rounded-xl bg-brand-muted px-4 py-3 text-[15px] font-bold text-brand-dark">
                        {{ __('recruitment.applicant.registration_next', ['date' => et_date($nextAnnouncement->opening_date, 'M d, Y')]) }}
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600">{{ __('recruitment.applicant.registration_have_account') }}</p>
            <div class="flex gap-2">
                <a href="{{ route('announcements.index') }}" class="inline-flex h-11 items-center rounded-xl border border-gray-300 px-4 text-sm font-bold text-gray-900 hover:bg-gray-50">{{ __('menus.announcements') }}</a>
                <a href="{{ route('login') }}" class="inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-bold text-white hover:bg-brand-dark">{{ __('applicant.sign_in_link') }}</a>
            </div>
        </div>
    </section>
</div>
@endsection
