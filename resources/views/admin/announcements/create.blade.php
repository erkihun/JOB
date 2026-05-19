@extends('layouts.admin')
@section('title', __('messages.add_announcement'))

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.announcements.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
        ← {{ __('menus.announcements') }}
    </a>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.add_announcement') }}</h1>

    <form method="POST" action="{{ route('admin.announcements.store') }}">
        @csrf
        @include('admin.announcements._form')
    </form>
</div>
@endsection
