@extends('layouts.admin')
@section('title', __('messages.edit_user'))
@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('messages.edit_user')"
                         :description="$user->email"
                         :crumbs="[['label' => __('menus.access_control')], ['label' => __('menus.users'), 'url' => route('admin.users.index')], ['label' => $user->name]]" />
    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="max-w-3xl">
        @csrf @method('PUT')
        @include('admin.users._form')
    </form>
</div>
@endsection
