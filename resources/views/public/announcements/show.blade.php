@extends('layouts.public')
@section('title', $announcement->subject)

@section('content')

<div class="bg-blue-700 text-white py-10">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <a href="{{ route('announcements.index') }}" class="text-sm text-blue-200 hover:text-white mb-3 inline-block">
            ← {{ __('menus.announcements') }}
        </a>
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $announcement->subject }}</h1>
        <p class="mt-2 text-sm text-blue-200">{{ et_date($announcement->published_at, 'd M Y') }}</p>
    </div>
</div>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    @php $safeHtml = $announcement->renderableHtml(); @endphp
    <div id="announcement-body" class="rounded-xl border border-gray-100 bg-white p-6 sm:p-8 shadow-sm prose prose-sm max-w-none text-gray-700 announcement-content overflow-hidden">
        {!! $safeHtml !!}
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.announcement-content table').forEach(function (table) {
        if (table.parentNode.classList.contains('announcement-table-scroll')) return;
        var wrapper = document.createElement('div');
        wrapper.className = 'announcement-table-scroll';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
</script>
@endpush
@endsection
