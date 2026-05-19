@extends('layouts.admin')
@section('title', __('vacancies.create_vacancy'))

@section('content')
<form method="POST" action="{{ route('admin.vacancies.store') }}" class="space-y-4">
    @csrf
    @include('admin.vacancies._form')
</form>
@endsection
