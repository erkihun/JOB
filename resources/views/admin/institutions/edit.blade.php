@extends('layouts.admin')
@section('title', __('admin.institution_edit'))

@section('content')
<div class="space-y-4">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.institutions.index') }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
            {{ __('admin.resource.institutions') }}
        </a>
        <svg class="h-4 w-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <h1 class="text-lg font-semibold text-gray-900">{{ __('admin.institution_edit') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.institutions.update', $institution) }}" class="space-y-6">
        @csrf @method('PUT')

        @include('admin.institutions._form')

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary btn">{{ __('messages.save') }}</button>
            <a href="{{ route('admin.institutions.index') }}" class="btn-secondary btn">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
