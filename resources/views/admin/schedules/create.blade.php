@extends('layouts.admin')
@section('title', __('messages.add_schedule'))
@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.schedules.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.schedules') }}</a>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.add_schedule') }}</h1>
    <form method="POST" action="{{ route('admin.schedules.store') }}">
        @csrf
        @include('admin.schedules._form')
    </form>
</div>
@endsection
