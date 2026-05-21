@extends('layouts.public')

@section('title', __('auth.verify_otp'))

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

                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21.75 9v.906a2.25 2.25 0 01-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 001.183 1.981l6.478 3.488m8.839 2.51l-4.66-2.51m0 0l-1.023-.55a2.25 2.25 0 00-2.134 0l-1.022.55m0 0l-4.661 2.51m16.5 1.615a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V8.844a2.25 2.25 0 011.183-1.981l7.5-4.039a2.25 2.25 0 012.134 0l7.5 4.039a2.25 2.25 0 011.183 1.98V19.5z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('auth.verify_otp') }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('auth.otp_sent_to', ['email' => session('applicant_password_reset_email')]) }}
                </p>
            </div>

            @if(session('info'))
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-700">
                {{ session('info') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('applicant.password.verify-otp') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('auth.otp_code') }}
                    </label>
                    <input type="text" id="otp" name="otp"
                           inputmode="numeric" pattern="\d{6}" maxlength="6"
                           value="{{ old('otp') }}" required autofocus autocomplete="one-time-code"
                           placeholder="_ _ _ _ _ _"
                           class="block w-full rounded-lg border px-3 py-3 text-center text-2xl font-bold tracking-[0.5em] shadow-sm transition
                                  focus:outline-none focus:ring-2 focus:ring-blue-500
                                  {{ $errors->has('otp') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}">
                    <p class="mt-1.5 text-xs text-gray-400 text-center">{{ __('auth.otp_expires_hint') }}</p>
                    @error('otp')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm
                               hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    {{ __('auth.verify_otp_button') }}
                </button>
            </form>

            <div class="mt-5 text-center">
                <form method="POST" action="{{ route('applicant.password.email') }}">
                    @csrf
                    <input type="hidden" name="email" value="{{ session('applicant_password_reset_email') }}">
                    <button type="submit" class="text-sm text-blue-600 hover:text-blue-500 transition">
                        {{ __('auth.resend_otp') }}
                    </button>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-gray-500">
                <a href="{{ route('applicant.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    ← {{ __('auth.back_to_login') }}
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
