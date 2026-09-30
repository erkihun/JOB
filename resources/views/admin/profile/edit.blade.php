@extends('layouts.admin')

@section('title', __('messages.edit_profile'))

@section('content')
@php
    $pe = $errors->getBag('profile');
    $pwErr = $errors->getBag('password');
    $photoUrl = $user->profile_photo ? asset('storage/'.$user->profile_photo) : '';
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    $roleName = fn (string $role) => \Illuminate\Support\Facades\Lang::has('messages.role_names.'.$role) ? __('messages.role_names.'.$role) : \Illuminate\Support\Str::headline($role);
    $has2fa = $user->hasTwoFactorEnabled();
    $nid = old('national_id', $user->national_id ?? '');
    $minLen = (int) ($passwordPolicy['min_length'] ?? 12);
    $input = fn (string $field) => 'form-input'.($pe->has($field) ? ' form-input-error' : '');
@endphp
<div class="space-y-6">

    <x-admin.page-header :title="__('messages.edit_profile')"
                         :description="__('messages.edit_profile_sub')"
                         :crumbs="[['label' => __('messages.my_account')], ['label' => __('messages.edit_profile')]]" />

    {{-- ── Identity card ── --}}
    <section class="card overflow-hidden">
        <div class="h-20 bg-brand/90" aria-hidden="true"></div>
        <div class="flex flex-col gap-4 px-5 pb-5 sm:flex-row sm:items-end sm:gap-5 sm:px-6">
            <div class="-mt-10 h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-white p-1 shadow-md ring-1 ring-gray-200">
                @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="" class="h-full w-full rounded-xl object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center rounded-xl bg-brand-muted text-2xl font-bold text-brand">{{ $initials }}</span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="truncate text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="truncate text-sm text-gray-600">{{ $user->email }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($user->getRoleNames() as $role)
                    <span class="badge badge-blue">{{ $roleName($role) }}</span>
                @endforeach
                <span class="badge {{ $has2fa ? 'badge-green' : 'badge-amber' }}">
                    {{ $has2fa ? __('messages.profile_2fa_on') : __('messages.profile_2fa_off') }}
                </span>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div class="space-y-6">

            {{-- ── Personal details ── --}}
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="card"
                  x-data="{
                      photo: @js($photoUrl),
                      originalEmail: @js(mb_strtolower($user->email)),
                      email: @js(old('email', $user->email)),
                      pick(e) { const f = e.target.files[0]; if (f) this.photo = URL.createObjectURL(f); },
                      get emailChanged() { return this.email.trim().toLowerCase() !== this.originalEmail; },
                  }">
                @csrf @method('PUT')
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                    <h2 class="card-title">{{ __('messages.profile_personal') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-600">{{ __('messages.profile_personal_hint') }}</p>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    @if($pe->any())
                    <div class="alert alert-danger" role="alert">{{ __('messages.profile_fix_errors') }}</div>
                    @endif

                    {{-- Photo --}}
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200">
                            <template x-if="photo"><img :src="photo" alt="" class="h-full w-full object-cover"></template>
                            <template x-if="!photo"><span class="flex h-full w-full items-center justify-center bg-brand-muted text-xl font-bold text-brand">{{ $initials }}</span></template>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ __('fields.profile_photo') }}</p>
                            <p class="text-xs text-gray-600">{{ __('messages.profile_photo_hint') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <label class="btn btn-secondary btn-sm cursor-pointer">
                                    {{ $photoUrl ? __('messages.profile_photo_change') : __('messages.profile_photo_upload') }}
                                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick($event)">
                                </label>
                                @if($photoUrl)
                                <button type="submit" form="remove-photo" class="btn btn-danger-soft btn-sm">{{ __('messages.profile_photo_remove') }}</button>
                                @endif
                            </div>
                            @if($pe->has('profile_photo'))<p class="form-error">{{ $pe->first('profile_photo') }}</p>@endif
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="form-label">{{ __('fields.name') }} <span class="form-required">*</span></label>
                            <input type="text" id="name" name="name" required autocomplete="name" value="{{ old('name', $user->name) }}" class="{{ $input('name') }}">
                            @if($pe->has('name'))<p class="form-error">{{ $pe->first('name') }}</p>@endif
                        </div>

                        <div>
                            <label for="email" class="form-label">{{ __('fields.email') }} <span class="form-required">*</span></label>
                            <input type="email" id="email" name="email" required autocomplete="email" x-model="email" class="{{ $input('email') }}">
                            <p class="form-hint">{{ __('messages.profile_email_hint') }}</p>
                            @if($pe->has('email'))<p class="form-error">{{ $pe->first('email') }}</p>@endif
                        </div>

                        <div>
                            <label for="username" class="form-label">{{ __('fields.username') }}</label>
                            <input type="text" id="username" name="username" autocomplete="username" value="{{ old('username', $user->username) }}" class="{{ $input('username') }}">
                            <p class="form-hint">{{ __('messages.profile_username_hint') }}</p>
                            @if($pe->has('username'))<p class="form-error">{{ $pe->first('username') }}</p>@endif
                        </div>

                        {{-- Confirm password when the sign-in email changes --}}
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:col-span-2"
                             x-show="emailChanged || {{ $pe->has('current_password') ? 'true' : 'false' }}" x-cloak>
                            <label for="current_password_email" class="form-label">{{ __('messages.profile_confirm_password_email') }} <span class="form-required">*</span></label>
                            <input type="password" id="current_password_email" name="current_password" autocomplete="current-password"
                                   :disabled="!emailChanged" class="{{ $input('current_password') }} bg-white">
                            @if($pe->has('current_password'))<p class="form-error">{{ $pe->first('current_password') }}</p>@endif
                        </div>

                        <div>
                            <label for="phone" class="form-label">{{ __('fields.phone') }}</label>
                            <div class="flex">
                                <span class="inline-flex select-none items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-sm font-semibold text-gray-600">+251</span>
                                <input type="tel" id="phone" name="phone" inputmode="tel" autocomplete="tel-national" maxlength="10"
                                       placeholder="911 234 567" value="{{ old('phone', \App\Support\EthiopianPhone::local($user->phone)) }}"
                                       oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)"
                                       class="{{ $input('phone') }} rounded-l-none">
                            </div>
                            @if($pe->has('phone'))<p class="form-error">{{ $pe->first('phone') }}</p>@endif
                        </div>

                        <div>
                            <label for="gender" class="form-label">{{ __('fields.gender') }}</label>
                            <select id="gender" name="gender" class="form-select">
                                <option value="">{{ __('applicant.select_placeholder') }}</option>
                                @foreach(\App\Enums\Gender::cases() as $g)
                                    <option value="{{ $g->value }}" @selected(old('gender', $user->gender?->value) === $g->value)>{{ $g->getLabel() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="national_id" class="form-label">{{ __('fields.national_id') }}</label>
                            <input type="text" id="national_id" name="national_id" inputmode="numeric" maxlength="19"
                                   placeholder="1234 5678 9012 3456" value="{{ $nid ? trim(chunk_split(preg_replace('/\D/', '', $nid), 4, ' ')) : '' }}"
                                   oninput="const d = this.value.replace(/\D/g, '').slice(0, 16); this.value = d.replace(/(.{4})(?=.)/g, '$1 ');"
                                   class="{{ $input('national_id') }} max-w-xs font-mono tracking-wider">
                            @if($pe->has('national_id'))<p class="form-error">{{ $pe->first('national_id') }}</p>@endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3 sm:px-6">
                    <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
                </div>
            </form>

            {{-- ── Password ── --}}
            <form method="POST" action="{{ route('admin.profile.password') }}" class="card" id="password"
                  x-data="{ pw: '', show: false,
                            get rules() { const v = this.pw; return { len: v.length >= {{ $minLen }}, lower: /[a-z]/.test(v), upper: /[A-Z]/.test(v), number: /[0-9]/.test(v), symbol: /[^a-zA-Z0-9]/.test(v) }; },
                            get score() { return Object.values(this.rules).filter(Boolean).length; } }">
                @csrf @method('PUT')
                <div class="border-b border-gray-100 px-5 py-4 sm:px-6">
                    <h2 class="card-title">{{ __('messages.profile_password') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-600">{{ __('messages.profile_password_hint') }}</p>
                </div>
                <div class="space-y-5 p-5 sm:p-6">
                    <div class="max-w-sm">
                        <label for="current_password" class="form-label">{{ __('messages.current_password') }} <span class="form-required">*</span></label>
                        <input :type="show ? 'text' : 'password'" id="current_password" name="current_password" required autocomplete="current-password"
                               class="form-input {{ $pwErr->has('current_password') ? 'form-input-error' : '' }}">
                        @if($pwErr->has('current_password'))<p class="form-error">{{ $pwErr->first('current_password') }}</p>@endif
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="new_password" class="form-label">{{ __('messages.new_password') }} <span class="form-required">*</span></label>
                            <input :type="show ? 'text' : 'password'" id="new_password" name="new_password" required autocomplete="new-password" x-model="pw"
                                   class="form-input {{ $pwErr->has('new_password') ? 'form-input-error' : '' }}">
                            @if($pwErr->has('new_password'))<p class="form-error">{{ $pwErr->first('new_password') }}</p>@endif
                        </div>
                        <div>
                            <label for="new_password_confirmation" class="form-label">{{ __('messages.confirm_password') }} <span class="form-required">*</span></label>
                            <input :type="show ? 'text' : 'password'" id="new_password_confirmation" name="new_password_confirmation" required autocomplete="new-password" class="form-input">
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <div class="mb-3 grid grid-cols-5 gap-1.5" aria-hidden="true">
                            <template x-for="i in 5" :key="i">
                                <span class="h-1.5 rounded-full" :class="i <= score ? (score >= 5 ? 'bg-green-600' : (score >= 3 ? 'bg-amber-500' : 'bg-red-500')) : 'bg-gray-200'"></span>
                            </template>
                        </div>
                        <ul class="grid gap-1.5 text-sm sm:grid-cols-2">
                            @foreach(['len' => __('messages.pw_rule_min', ['min' => $minLen]), 'lower' => __('applicant.pw_rule_lower'), 'upper' => __('applicant.pw_rule_upper'), 'number' => __('applicant.pw_rule_number'), 'symbol' => __('applicant.pw_rule_symbol')] as $rule => $text)
                            <li class="flex items-center gap-2" :class="rules.{{ $rule }} ? 'text-green-700' : 'text-gray-600'">
                                <span class="flex h-4 w-4 items-center justify-center rounded-full" :class="rules.{{ $rule }} ? 'bg-green-600 text-white' : 'border border-gray-400'">
                                    <svg x-show="rules.{{ $rule }}" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                {{ $text }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 border-t border-gray-100 bg-gray-50 px-5 py-3 sm:px-6">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" x-model="show" class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        {{ __('messages.show_passwords') }}
                    </label>
                    <button type="submit" class="btn btn-primary">{{ __('messages.profile_update_password') }}</button>
                </div>
            </form>
        </div>

        {{-- ── Sidebar ── --}}
        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="card card-body">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $has2fa ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </span>
                    <div>
                        <h2 class="card-title">{{ __('messages.profile_2fa') }}</h2>
                        <p class="mt-0.5 text-sm {{ $has2fa ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $has2fa ? __('messages.profile_2fa_on_hint') : __('messages.profile_2fa_off_hint') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('admin.two-factor.show') }}" class="btn {{ $has2fa ? 'btn-secondary' : 'btn-primary' }} mt-4 w-full justify-center">
                    {{ $has2fa ? __('messages.profile_2fa_manage') : __('messages.profile_2fa_setup') }}
                </a>
            </section>

            <section class="card card-body">
                <h2 class="card-title mb-3">{{ __('messages.profile_account') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('messages.profile_roles') }}</dt>
                        <dd class="mt-1 flex flex-wrap gap-1.5">
                            @forelse($user->getRoleNames() as $role)
                                <span class="badge badge-blue">{{ $roleName($role) }}</span>
                            @empty
                                <span class="text-gray-700">—</span>
                            @endforelse
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">{{ __('vacancies.status') }}</dt>
                        <dd><x-admin.status :status="$user->status" /></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">{{ __('messages.profile_member_since') }}</dt>
                        <dd class="font-medium text-gray-900">{{ et_date($user->created_at) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-500">{{ __('messages.profile_last_updated') }}</dt>
                        <dd class="font-medium text-gray-900">{{ et_diff_for_humans($user->updated_at) }}</dd>
                    </div>
                </dl>
                <p class="mt-4 border-t border-gray-100 pt-3 text-xs text-gray-500">{{ __('messages.profile_roles_hint') }}</p>
            </section>
        </aside>
    </div>

    @if($photoUrl)
    <form id="remove-photo" method="POST" action="{{ route('admin.profile.photo.destroy') }}" class="hidden"
          onsubmit="return confirm(@js(__('messages.profile_photo_remove_confirm')))">
        @csrf @method('DELETE')
    </form>
    @endif
</div>
@endsection
