@props([
    'label',
    'tone' => 'blue',
])

@php
    $tones = [
        'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
        'orange' => 'border-orange-200 bg-orange-50 text-orange-700',
        'green' => 'border-green-200 bg-green-50 text-green-700',
        'red' => 'border-red-200 bg-red-50 text-red-700',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
        'gray' => 'border-gray-200 bg-gray-50 text-gray-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold '.($tones[$tone] ?? $tones['blue'])]) }}>
    @if(isset($icon)) {{ $icon }} @endif
    {{ $label }}
</span>
