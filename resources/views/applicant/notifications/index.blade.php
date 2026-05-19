@extends('layouts.applicant')

@section('title', __('menus.notifications'))

@section('content')
<div class="space-y-4">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">{{ __('applicant.notifications_heading') }}</h1>
    </div>

    @if($notifications->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-14 text-center">
            <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-gray-500 text-sm">{{ __('applicant.no_notifications') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
            @php
                $typeColors = [
                    'application_submitted' => 'border-blue-200 bg-blue-50/40',
                    'screening_result'      => 'border-green-200 bg-green-50/40',
                    'correction_required'   => 'border-orange-200 bg-orange-50/40',
                    'exam_invitation'       => 'border-purple-200 bg-purple-50/40',
                    'interview_invitation'  => 'border-indigo-200 bg-indigo-50/40',
                    'selection_result'      => 'border-green-200 bg-green-50/40',
                ];
                $colorClass = $typeColors[$notification->type->value ?? $notification->type] ?? 'border-gray-200 bg-gray-50/40';
                $isUnread = $notification->read_at === null;
            @endphp
            <div class="rounded-xl border {{ $colorClass }} {{ $isUnread ? 'ring-1 ring-blue-200 shadow-sm' : '' }} p-4 bg-white transition">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            @if($isUnread)
                            <span class="inline-block h-2 w-2 rounded-full bg-blue-500 shrink-0"></span>
                            @endif
                            <p class="text-sm font-semibold text-gray-900">{{ $notification->subject }}</p>
                        </div>
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $notification->message }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif

</div>
@endsection
