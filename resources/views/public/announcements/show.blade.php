@extends('layouts.public')
@section('title', $announcement->subject)

@section('content')

<div class="bg-blue-700 text-white py-10">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <a href="{{ route('announcements.index') }}" class="text-sm text-blue-200 hover:text-white mb-3 inline-block">
            ← {{ __('menus.announcements') }}
        </a>
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $announcement->subject }}</h1>
        <p class="mt-2 text-sm text-blue-200">{{ $announcement->published_at->format('d M Y') }}</p>
    </div>
</div>

<div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
    @php $safeHtml = $announcement->renderableHtml(); @endphp
    <div class="rounded-xl border border-gray-100 bg-white p-8 shadow-sm prose prose-sm max-w-none text-gray-700">
        {!! $safeHtml !!}
    </div>
</div>
@endsection
