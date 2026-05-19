@extends('layouts.admin')
@section('title', __('messages.add') . ' ' . __('menus.hero_slider'))

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.hero-sliders.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
        ← {{ __('menus.hero_slider') }}
    </a>
    <h1 class="text-lg font-semibold text-gray-900">{{ __('messages.add') }} {{ __('menus.hero_slider') }}</h1>

    <form method="POST" action="{{ route('admin.hero-sliders.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.hero-sliders._form')
    </form>
</div>
@endsection
