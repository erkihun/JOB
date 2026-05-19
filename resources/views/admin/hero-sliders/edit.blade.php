@extends('layouts.admin')
@section('title', __('messages.edit') . ': ' . $heroSlider->getTranslation('title', 'en', false))

@section('content')
<div class="space-y-4">
    <a href="{{ route('admin.hero-sliders.index') }}" class="text-sm font-medium text-brand hover:text-brand-dark">
        ← {{ __('menus.hero_slider') }}
    </a>
    <h1 class="text-lg font-semibold text-gray-900">
        {{ __('messages.edit') }}: {{ $heroSlider->getTranslation('title', 'en', false) }}
    </h1>

    <form method="POST" action="{{ route('admin.hero-sliders.update', $heroSlider) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('admin.hero-sliders._form')
    </form>
</div>
@endsection
