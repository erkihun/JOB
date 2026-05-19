@extends('layouts.admin')
@section('title', __('messages.edit_user'))
@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">← {{ __('menus.users') }}</a>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.edit_user') }}: {{ $user->name }}</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.users._form')
    </form>
</div>
@endsection
