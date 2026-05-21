@extends('layouts.public')

@section('title', __('auth.reset_password'))

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">

        <div class="rounded-2xl bg-white shadow-lg ring-1 ring-gray-100 px-8 py-10">

            <div class="mb-8 text-center">
                @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
                @if($orgLogo)
                <img src="{{ Storage::url($orgLogo) }}" alt="{{ \App\Models\Setting::get('org.name') }}"
                     class="mx-auto mb-4 h-14 w-auto object-contain">
                @endif

                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('auth.reset_password') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('auth.reset_password_hint') }}</p>
            </div>

            @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('applicant.password.reset') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.new_password') }}
                    </label>
                    <input type="password" id="password" name="password"
                           required autocomplete="new-password"
                           class="block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-blue-500
                                  {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}">
                    @error('password')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.confirm_password') }}
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           required autocomplete="new-password"
                           class="block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-blue-500
                                  border-gray-300 bg-white">
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                               hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    {{ __('auth.reset_password_button') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                <a href="{{ route('applicant.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    ← {{ __('auth.back_to_login') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
