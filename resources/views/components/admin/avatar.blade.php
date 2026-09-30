{{-- Photo or initials. size: sm (32px) | md (40px) | lg (64px) --}}
@props([
    'name' => '',
    'photo' => null,
    'size' => 'sm',
])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') ?: '?';
    $box = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-16 w-16 text-xl'][$size] ?? 'h-8 w-8 text-xs';
@endphp

@if($photo)
    <img src="{{ $photo }}" alt="" {{ $attributes->merge(['class' => "{$box} shrink-0 rounded-full object-cover ring-1 ring-gray-200"]) }}>
@else
    <span {{ $attributes->merge(['class' => "{$box} flex shrink-0 items-center justify-center rounded-full bg-brand-muted font-bold text-brand-dark"]) }} aria-hidden="true">{{ $initials }}</span>
@endif
