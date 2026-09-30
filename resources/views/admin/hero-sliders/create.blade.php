@extends('layouts.admin')
@section('title', __('messages.slider_add'))

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('messages.slider_add')"
                         :description="__('messages.slider_form_intro')"
                         :crumbs="[['label' => __('menus.system')], ['label' => __('menus.hero_slider'), 'url' => route('admin.hero-sliders.index')], ['label' => __('messages.create')]]" />

    <form method="POST" action="{{ route('admin.hero-sliders.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @include('admin.hero-sliders._form')
    </form>
</div>
@endsection
