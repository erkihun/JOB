@extends('layouts.admin')
@section('title', __('messages.edit') . ': ' . $announcement->subject)

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.announcements.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
        ← {{ __('menus.announcements') }}
    </a>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.edit') }}: {{ $announcement->subject }}</h1>

    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
        @csrf @method('PUT')
        @include('admin.announcements._form')
    </form>
</div>
@endsection
