@extends('layouts.admin')
@section('title', __('messages.edit_schedule'))
@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.schedules.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.schedules') }}</a>
    <h1 class="text-lg font-semibold text-gray-900">{{ $schedule->title }}</h1>
    <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}">
        @csrf @method('PUT')
        @include('admin.schedules._form')
    </form>
</div>
@endsection
