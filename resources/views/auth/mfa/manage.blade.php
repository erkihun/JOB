@extends('layouts.public')

@section('title', __('auth.mfa_manage'))

@section('content')
@if(auth()->user()?->canAccessAdminArea())
    @include('partials.admin-theme')
@endif
<div class="mx-auto max-w-2xl px-4 py-10">
    <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('auth.mfa_manage') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('auth.mfa_manage_hint') }}</p>
            </div>
            @if($enabled)
                {{-- MFA already set up: user navigated here voluntarily, safe to go back to dashboard --}}
                <a href="{{ auth()->user()?->canAccessAdminArea() ? route('admin.dashboard') : route('applicant.dashboard') }}"
                   class="btn btn-secondary text-sm">
                    {{ __('messages.cancel') }}
                </a>
            @else
                {{-- MFA not yet set up: user was forced here by require2fa; cancel must log them out --}}
                <form method="POST" action="{{ auth()->user()?->canAccessAdminArea() ? route('admin.logout') : route('applicant.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary text-sm">
                        {{ __('messages.cancel') }}
                    </button>
                </form>
            @endif
        </div>

        @if(session('warning'))
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
        @endif
        @if(session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        @if($enabled)
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ __('auth.mfa_enabled_status') }}
            </div>

            @if(! empty($recoveryCodes))
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">{{ __('auth.recovery_codes_save') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach($recoveryCodes as $code)
                            <code class="rounded border border-amber-200 bg-white px-3 py-2 text-sm text-amber-900">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <form method="POST" action="{{ route('mfa.recovery-codes.regenerate') }}" class="space-y-3 rounded-lg border border-gray-200 p-4">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">{{ __('auth.current_password') }}</label>
                    <input type="password" name="current_password" required class="form-input">
                    <button type="submit" class="btn btn-secondary">{{ __('auth.regenerate_recovery_codes') }}</button>
                </form>

                <form method="POST" action="{{ route('mfa.disable') }}" class="space-y-3 rounded-lg border border-red-200 p-4">
                    @csrf
                    <label class="block text-sm font-medium text-gray-700">{{ __('auth.current_password') }}</label>
                    <input type="password" name="current_password" required class="form-input">
                    <button type="submit" class="btn btn-danger">{{ __('auth.disable_mfa') }}</button>
                </form>
            </div>
        @else
            <div class="space-y-6">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('auth.mfa_scan_qr') }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ __('auth.mfa_scan_qr_hint') }}</p>
                    <div class="mt-4 flex justify-center">
                        <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                            {!! $qrCodeSvg !!}
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ __('auth.mfa_manual_key') }}</p>
                    <p class="mt-2 select-all rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-center font-mono text-sm tracking-widest text-gray-700">
                        {{ implode(' ', str_split($secret, 4)) }}
                    </p>
                </div>

                <form method="POST" action="{{ route('mfa.enable') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="one_time_password" class="block text-sm font-medium text-gray-700">{{ __('auth.mfa_code') }}</label>
                        <input type="text" id="one_time_password" name="one_time_password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="form-input mt-1 text-center font-mono tracking-widest">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('auth.enable_mfa') }}</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
