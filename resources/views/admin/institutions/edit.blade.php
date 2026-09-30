@extends('layouts.admin')
@section('title', __('admin.institution_edit'))

@section('content')
<div class="space-y-6">
    <x-admin.page-header :title="__('admin.institution_edit')"
                         :crumbs="[['label' => __('menus.recruitment')], ['label' => __('admin.resource.institutions'), 'url' => route('admin.institutions.index')], ['label' => $institution->short_name ?: $institution->code]]" />

    <form method="POST" action="{{ route('admin.institutions.update', $institution) }}" class="space-y-6">
        @csrf @method('PUT')

        @include('admin.institutions._form')

        <div class="card flex flex-wrap items-center justify-end gap-2 px-5 py-4">
            <a href="{{ route('admin.institutions.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
</div>
@endsection
