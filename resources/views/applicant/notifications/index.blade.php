@extends('layouts.applicant')

@section('title', __('menus.notifications'))

@section('content')
@php
    $locale = app()->getLocale();
    $typeTone = [
        'screening_passed' => 'bg-green-50 text-green-800',
        'selected' => 'bg-green-50 text-green-800',
        'screening_failed' => 'bg-red-50 text-red-800',
        'not_selected' => 'bg-red-50 text-red-800',
        'correction_required' => 'bg-accent-muted text-accent-dark',
        'exam_invitation' => 'bg-accent-muted text-accent-dark',
        'interview_invitation' => 'bg-accent-muted text-accent-dark',
    ];
@endphp
<div class="space-y-6">
    <x-applicant.page-header :title="__('applicant.notifications_heading')" :description="__('applicant.messages_intro')" />

    @if($notifications->isEmpty())
        <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500" aria-hidden="true">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </span>
            <p class="font-bold text-gray-900">{{ __('applicant.no_notifications') }}</p>
            <p class="mt-1 text-sm text-gray-600">{{ __('applicant.no_notifications_hint') }}</p>
        </div>
    @else
        <ul class="space-y-3">
            @foreach($notifications as $notification)
            @php
                $type = $notification->type->value ?? (string) $notification->type;
                $isUnread = $notification->read_at === null;
                $app = $notification->application;
            @endphp
            <li class="rounded-2xl border bg-white p-5 {{ $isUnread ? 'border-brand/40 ring-1 ring-brand/15' : 'border-gray-200' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        @if($isUnread)<span class="h-2.5 w-2.5 shrink-0 rounded-full bg-accent" aria-label="{{ __('applicant.unread') }}"></span>@endif
                        <h2 class="text-base font-bold text-gray-900">{{ $notification->subject }}</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $typeTone[$type] ?? 'bg-gray-100 text-gray-700' }}">{{ $notification->type instanceof \App\Enums\NotificationType ? $notification->type->getLabel() : $type }}</span>
                        <time class="whitespace-nowrap text-xs text-gray-600" datetime="{{ $notification->created_at->toIso8601String() }}">{{ et_diff_for_humans($notification->created_at) }}</time>
                    </div>
                </div>
                <p class="mt-2 whitespace-pre-line text-[15px] leading-relaxed text-gray-800">{{ $notification->message }}</p>
                @if($app)
                <a href="{{ route('applicant.applications.show', $app) }}" class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-brand hover:underline">
                    {{ __('applicant.open_application', ['vacancy' => $app->vacancy ? ($app->vacancy->getTranslation('title', $locale, false) ?: $app->vacancy->getTranslation('title', 'en', false)) : $app->reference_number]) }} →
                </a>
                @endif
            </li>
            @endforeach
        </ul>

        <div>{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
