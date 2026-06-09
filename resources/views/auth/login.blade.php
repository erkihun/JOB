@extends('layouts.public')

@section('title', __('auth.unified_login'))

@section('content')
<div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
        <div class="rounded-2xl bg-white px-8 py-10 shadow-lg ring-1 ring-gray-100">
            <div class="mb-8 text-center">
                @php $orgLogo = \App\Models\Setting::get('org.logo', ''); @endphp
                @if($orgLogo)
                    <img src="{{ Storage::url($orgLogo) }}" alt="{{ \App\Models\Setting::get('org.name') }}"
                         class="mx-auto mb-4 h-14 w-auto object-contain">
                @endif
                <h1 class="text-2xl font-bold text-gray-900">{{ __('auth.unified_login') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('auth.unified_login_hint') }}</p>
            </div>

            @if(session('warning'))
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">
                        {{ __('fields.email') }}
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           autocomplete="email"
                           autofocus
                           required
                           class="block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
                        {{ __('fields.password') }}
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           autocomplete="current-password"
                           required
                           class="block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white' }}">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        {{ __('applicant.remember_me') }}
                    </label>
                    <a href="{{ route('applicant.password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        {{ __('auth.forgot_password') }}?
                    </a>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ __('auth.sign_in') }}
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-sm text-gray-500">
            {{ __('applicant.new_here') }}
            <a href="{{ route('applicant.register') }}" class="font-medium text-blue-600 hover:text-blue-500">
                {{ __('applicant.create_account') }}
            </a>
        </p>
    </div>
</div>
@endsection
