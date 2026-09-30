@extends('layouts.admin')
@section('title', __('messages.slider_edit'))

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('messages.slider_edit')"
                         :description="__('messages.slider_form_intro')"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.hero_slider'), 'url' => route('admin.hero-sliders.index')], ['label' => $heroSlider->getTranslation('title', 'en', false) ?: __('messages.edit')]]" />

    <form method="POST" action="{{ route('admin.hero-sliders.update', $heroSlider) }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf @method('PUT')
        @include('admin.hero-sliders._form')
    </form>
</div>
@endsection
