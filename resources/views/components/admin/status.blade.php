{{--
    One status badge for every status enum that exposes color()/getLabel()
    (ApplicationStatus, VacancyStatus, UserStatus, …) or a plain tone + label.
      <x-admin.status :status="$application->status" />
      <x-admin.status tone="success" :label="__('messages.active')" />
--}}
@props([
    'status' => null,
    'tone' => null,    // info | warning | success | danger | gray
    'label' => null,
])

@php
    $resolvedTone = $tone
        ?? (is_object($status) && method_exists($status, 'color') ? $status->color() : 'gray');
    $resolvedLabel = $label
        ?? (is_object($status) && method_exists($status, 'getLabel') ? $status->getLabel()
        : (is_object($status) && method_exists($status, 'label') ? $status->label()
        : (is_object($status) && property_exists($status, 'value') ? ucfirst(str_replace('_', ' ', $status->value)) : (string) $status)));
    $class = match ($resolvedTone) {
        'info', 'primary' => 'badge-info',
        'warning' => 'badge-warning',
        'success' => 'badge-success',
        'danger' => 'badge-danger',
        default => 'badge-gray',
    };
@endphp

<span {{ $attributes->merge(['class' => $class.' badge-dot']) }}>{{ $resolvedLabel }}</span>
