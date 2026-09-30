{{--
    Application progress tracker. variant: "bar" (compact, lists) | "full" (detail page).
      <x-applicant.tracker :application="$application" />
      <x-applicant.tracker :application="$application" variant="full" />
--}}
@props(['application', 'variant' => 'bar'])

@php
    $steps = \App\Support\ApplicationProgress::steps($application);
    $barColor = ['done' => 'bg-brand', 'current' => 'bg-accent', 'failed' => 'bg-red-600', 'pending' => 'bg-gray-200'];
    $textColor = ['done' => 'text-brand-dark font-semibold', 'current' => 'text-accent-dark font-bold', 'failed' => 'text-red-700 font-bold', 'pending' => 'text-gray-500'];
@endphp

@if($variant === 'full')
<ol {{ $attributes->merge(['class' => 'grid grid-cols-5 gap-2']) }} aria-label="{{ __('applicant.status_timeline') }}">
    @foreach($steps as $i => $step)
    <li class="relative flex flex-col items-center gap-2 text-center" @if($step['state'] === 'current') aria-current="step" @endif>
        @if(! $loop->last)
        <span class="absolute left-1/2 top-5 h-0.5 w-full {{ $step['state'] === 'done' ? 'bg-brand' : 'bg-gray-200' }}" aria-hidden="true"></span>
        @endif
        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold ring-4
            {{ match($step['state']) {
                'done' => 'bg-brand text-white ring-brand-muted',
                'current' => 'bg-accent text-white ring-accent-muted',
                'failed' => 'bg-red-600 text-white ring-red-100',
                default => 'bg-gray-100 text-gray-500 ring-white',
            } }}">
            @if($step['state'] === 'done')
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            @elseif($step['state'] === 'failed')
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            @else
                {{ $i + 1 }}
            @endif
        </span>
        <span class="text-xs sm:text-sm {{ $textColor[$step['state']] }}">{{ $step['label'] }}</span>
        @if($step['date'])<span class="-mt-1 text-xs text-gray-500">{{ et_date($step['date'], 'M d') }}</span>@endif
    </li>
    @endforeach
</ol>
@else
<div {{ $attributes->merge(['class' => 'grid grid-cols-5 gap-1.5']) }} role="img"
     aria-label="{{ __('applicant.status_timeline') }}: {{ collect($steps)->firstWhere('state', 'current')['label'] ?? $application->status->label() }}">
    @foreach($steps as $step)
    <div class="flex min-w-0 flex-col gap-1.5">
        <span class="h-1.5 rounded-full {{ $barColor[$step['state']] }}"></span>
        <span class="hidden truncate text-xs sm:block {{ $textColor[$step['state']] }}">{{ $step['label'] }}</span>
        @if($step['date'])<span class="-mt-1 hidden truncate text-[11px] text-gray-500 sm:block">{{ et_date($step['date'], 'M d') }}</span>@endif
    </div>
    @endforeach
</div>
@endif
