@extends('layouts.admin')
@section('title', __('messages.add_user'))
@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('messages.add_user')"
                         :crumbs="[['label' => __('menus.access_control')], ['label' => __('menus.users'), 'url' => route('admin.users.index')], ['label' => __('messages.create')]]" />
    <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf
        @include('admin.users._form')
    </form>
</div>
@endsection
