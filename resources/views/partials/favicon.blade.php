@php
    $favicon = \App\Models\Setting::get('org.favicon', '');
    // A 96px WebP copy of the uploaded icon (the upload itself can be several hundred pixels).
    $faviconPath = \App\Support\OptimizedImage::favicon($favicon);
    $faviconType = $faviconPath !== $favicon ? 'image/webp' : null;
@endphp

@if ($favicon)
    {{-- One <link>: a second "shortcut icon" tag made browsers download the file twice. --}}
    <link rel="icon" href="{{ Storage::url($faviconPath) }}" @if($faviconType) type="{{ $faviconType }}" @endif>
@endif
