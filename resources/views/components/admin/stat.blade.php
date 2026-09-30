{{--
    KPI tile. tone colours the hint: neutral | good | warn | bad | brand.
      <x-admin.stat :label="__('…')" :value="number_format($n)" :hint="…" tone="warn" :href="route(…)" />
--}}
@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
    'href' => null,
])

@php
    $hintColor = match ($tone) {
        'good' => 'text-green-700',
        'warn' => 'text-amber-700',
        'bad' => 'text-red-700',
        'brand' => 'text-brand',
        default => 'text-gray-600',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'stat block'.($href ? ' transition hover:border-brand/40 hover:shadow-md' : '')]) }}>
    <p class="stat-label">{{ $label }}</p>
    <p class="stat-value">{{ $value }}</p>
    @if($hint)<p class="stat-hint {{ $hintColor }}">{{ $hint }}</p>@endif
</{{ $tag }}>
