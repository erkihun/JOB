@extends('layouts.public')
@section('title', __('menus.announcements'))

@section('content')

{{-- Page header --}}
<div class="bg-blue-700 text-white py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold sm:text-3xl">{{ __('menus.announcements') }}</h1>
    </div>
</div>

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @if($announcements->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 p-16 text-center text-gray-400">
            {{ __('messages.no_records') }}
        </div>
    @else
        <div class="space-y-5">
            @foreach($announcements as $ann)
            <a href="{{ route('announcements.show', $ann) }}"
               class="group block rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md hover:border-blue-300 transition scroll-animate overflow-hidden"
               data-delay="{{ ($loop->index % 3) + 1 }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="text-base font-semibold text-gray-900 group-hover:text-blue-700">{{ $ann->subject }}</h2>
                    <span class="shrink-0 text-xs text-gray-400">{{ et_date($ann->published_at, 'd M Y') }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                    {{ strip_tags($ann->content) }}
                </p>
                <span class="mt-3 inline-block text-xs font-medium text-blue-600 group-hover:text-blue-800">
                    {{ __('messages.view') }} →
                </span>
            </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
@endsection
