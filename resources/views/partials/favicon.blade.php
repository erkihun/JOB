@php
    $favicon = \App\Models\Setting::get('org.favicon', '');
@endphp

@if ($favicon)
    <link rel="icon" href="{{ Storage::url($favicon) }}">
    <link rel="shortcut icon" href="{{ Storage::url($favicon) }}">
@endif
