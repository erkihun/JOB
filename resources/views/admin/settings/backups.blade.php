@extends('layouts.admin')
@section('title', __('backups.title'))

@section('content')
@php
    use Illuminate\Support\Number;

    $stateTone = ['healthy' => 'success', 'running' => 'info', 'failed' => 'danger', 'never' => 'gray', 'overdue' => 'warning'];
    $statusTone = ['success' => 'success', 'failed' => 'danger', 'queued' => 'info', 'running' => 'info', 'pruned' => 'gray', 'deleted' => 'gray'];
    $statTone = ['healthy' => 'good', 'running' => 'brand', 'failed' => 'bad', 'never' => 'warn', 'overdue' => 'warn'];
    $canManage = auth()->user()->can('backups.settings.manage');
    $canRun = auth()->user()->can('backups.run');
    $needsOtp = auth()->user()->hasTwoFactorEnabled();
    $formType = old('type');
@endphp
<div class="space-y-6" x-data="{ restoreUrl: '', restoreType: '', deleteUrl: '' }">

    <x-admin.page-header :title="__('backups.title')" :description="__('backups.intro')"
                         :crumbs="[['label' => __('menus.settings'), 'url' => route('admin.settings.index')], ['label' => __('backups.title')]]" />

    {{-- Failed backup warnings --}}
    @foreach($health as $type => $h)
        @if($h['state'] === 'failed')
        <div class="alert alert-danger" role="alert">
            <p>{{ __('backups.failed_warning', ['type' => __('backups.types.'.$type), 'message' => $h['last']->failure_message ?: '—']) }}</p>
        </div>
        @endif
    @endforeach

    {{-- Health --}}
    <section aria-labelledby="backup-health">
        <h2 id="backup-health" class="sr-only">{{ __('backups.health') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($health as $type => $h)
            <x-admin.stat :label="__('backups.'.$type)"
                          :value="__('backups.state.'.$h['state'])"
                          :tone="$statTone[$h['state']]"
                          :hint="__('backups.last_success').': '.($h['last_success']?->completed_at ? et_date($h['last_success']->completed_at, 'M d, Y H:i') : __('backups.never'))" />
            @endforeach
            <x-admin.stat :label="__('backups.storage_usage')" :value="Number::fileSize($usage, precision: 1)" tone="brand"
                          :hint="__('backups.destinations.local', ['disk' => $localDisk])" />
        </div>
    </section>

    {{-- Settings: one card per backup type --}}
    <div class="grid gap-6 xl:grid-cols-2">
        @foreach($health as $type => $h)
        @php
            $p = $h['preferences'];
            $old = fn (string $key, $default) => $formType === $type ? old($key, $default) : $default;
        @endphp
        <section class="card" aria-labelledby="backup-{{ $type }}">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                <div>
                    <h2 id="backup-{{ $type }}" class="card-title">{{ __('backups.'.$type) }}</h2>
                    <p class="mt-0.5 text-sm text-gray-600">{{ __('backups.'.$type.'_hint') }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-admin.status :tone="$stateTone[$h['state']]" :label="__('backups.state.'.$h['state'])" />
                    @if($canRun)
                    <form method="POST" action="{{ route('admin.backups.run', $type) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" @disabled($h['state'] === 'running')>{{ __('backups.backup_now') }}</button>
                    </form>
                    @endif
                </div>
            </div>

            <dl class="grid gap-3 border-b border-gray-100 px-5 py-4 text-sm sm:grid-cols-3 sm:px-6">
                <div><dt class="text-gray-600">{{ __('backups.last_backup') }}</dt>
                    <dd class="font-semibold text-gray-900">
                        @if($h['last'])
                            {{ et_date($h['last']->created_at, 'M d, Y H:i') }} · {{ __('backups.statuses.'.$h['last']->status) }}
                        @else {{ __('backups.never') }} @endif
                    </dd></div>
                <div><dt class="text-gray-600">{{ __('backups.last_success') }}</dt>
                    <dd class="font-semibold text-gray-900">{{ $h['last_success']?->completed_at ? et_date($h['last_success']->completed_at, 'M d, Y H:i') : __('backups.never') }}</dd></div>
                <div><dt class="text-gray-600">{{ __('backups.next_backup') }}</dt>
                    <dd class="font-semibold text-gray-900">{{ $h['next_run'] ? et_date(\Illuminate\Support\Carbon::instance($h['next_run']), 'M d, Y H:i') : __('backups.not_scheduled') }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('admin.backups.update') }}" class="space-y-4 p-5 sm:p-6">
                @csrf @method('PUT')
                <input type="hidden" name="type" value="{{ $type }}">
                <fieldset @disabled(! $canManage) class="space-y-4">
                    <label class="flex items-center gap-3 text-sm font-semibold text-gray-900">
                        <input type="checkbox" name="enabled" value="1" @checked($old('enabled', $p['enabled'])) class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                        {{ __('backups.enabled') }}
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="{{ $type }}-frequency" class="form-label">{{ __('backups.frequency') }}</label>
                            <select id="{{ $type }}-frequency" name="frequency" class="form-select">
                                @foreach(\App\Services\BackupService::FREQUENCIES as $f)
                                <option value="{{ $f }}" @selected($old('frequency', $p['frequency']) === $f)>{{ __('backups.frequencies.'.$f) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="{{ $type }}-time" class="form-label">{{ __('backups.time') }}</label>
                            <input type="time" id="{{ $type }}-time" name="time" value="{{ $old('time', $p['time']) }}" class="form-input">
                            <p class="form-hint">{{ __('backups.time_hint', ['tz' => config('app.timezone')]) }}</p>
                        </div>
                        <div>
                            <label for="{{ $type }}-retention" class="form-label">{{ __('backups.retention_days') }}</label>
                            <input type="number" id="{{ $type }}-retention" name="retention_days" min="1" max="3650" value="{{ $old('retention_days', $p['retention_days']) }}" class="form-input">
                        </div>
                        <div>
                            <label for="{{ $type }}-copies" class="form-label">{{ __('backups.max_copies') }}</label>
                            <input type="number" id="{{ $type }}-copies" name="max_copies" min="1" max="1000" value="{{ $old('max_copies', $p['max_copies']) }}" class="form-input">
                        </div>
                    </div>
                    <p class="form-hint -mt-2">{{ __('backups.retention_hint') }}</p>

                    <div>
                        <label for="{{ $type }}-destination" class="form-label">{{ __('backups.destination') }}</label>
                        <select id="{{ $type }}-destination" name="destination" class="form-select">
                            <option value="local" @selected($old('destination', $p['destination']) === 'local')>{{ __('backups.destinations.local', ['disk' => $localDisk]) }}</option>
                            <option value="s3" @selected($old('destination', $p['destination']) === 's3') @disabled(! $s3Available)>{{ __('backups.destinations.s3') }}</option>
                        </select>
                        @unless($s3Available)<p class="form-hint">{{ __('backups.s3_unavailable') }}</p>@endunless
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center gap-3 text-sm text-gray-800">
                            <input type="checkbox" name="compress" value="1" @checked($old('compress', $p['compress'])) class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            {{ __('backups.compress') }}
                        </label>
                        <label class="flex items-center gap-3 text-sm text-gray-800">
                            <input type="checkbox" name="encrypt" value="1" @checked($old('encrypt', $p['encrypt'])) class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            {{ __('backups.encrypt') }}
                        </label>
                        <p class="text-xs {{ $keyConfigured ? 'text-green-700' : 'text-amber-700' }}">{{ $keyConfigured ? __('backups.key_configured') : __('backups.key_missing') }}</p>
                        @if($type === 'database')
                        <label class="flex items-center gap-3 text-sm text-gray-800">
                            <input type="checkbox" name="include_database" value="1" @checked($old('include_database', $p['include_database'])) class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                            {{ __('backups.include_database') }}
                        </label>
                        @endif
                    </div>

                    @if($type === 'documents')
                    <fieldset>
                        <legend class="form-label">{{ __('backups.includes') }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach(\App\Services\BackupService::DOCUMENT_GROUPS as $group)
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800">
                                <input type="checkbox" name="includes[]" value="{{ $group }}" @checked(in_array($group, (array) $old('includes', $p['includes']), true)) class="h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                                {{ __('backups.groups.'.$group) }}
                            </label>
                            @endforeach
                        </div>
                        <p class="form-hint">{{ __('backups.excluded_note') }}</p>
                    </fieldset>
                    @endif

                    @if($formType === $type && $errors->any())
                    <ul class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                    @endif

                    @if($canManage)
                    <div class="flex justify-end border-t border-gray-100 pt-4">
                        <button type="submit" class="btn btn-primary">{{ __('backups.save') }}</button>
                    </div>
                    @endif
                </fieldset>
            </form>
        </section>
        @endforeach
    </div>

    {{-- History --}}
    <x-admin.table-card :title="__('backups.history')">
        @if($history->isEmpty())
            <x-admin.empty :text="__('backups.history_empty')" />
        @else
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="table-header">
                <tr>
                    <th class="table-th">{{ __('backups.type') }}</th>
                    <th class="table-th">{{ __('backups.status') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('backups.started') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('backups.completed') }}</th>
                    <th class="table-th-right">{{ __('backups.size') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('backups.initiated_by') }}</th>
                    <th class="table-th-right"><span class="sr-only">{{ __('messages.actions') }}</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($history as $backup)
                @php $restoreInfo = $backup->options['last_restore'] ?? null; @endphp
                <tr class="table-row align-top">
                    <td class="table-td">
                        <p class="font-semibold text-gray-900">{{ __('backups.types.'.$backup->type) }}</p>
                        <p class="text-xs text-gray-600">
                            {{ $backup->destination }}
                            @if($backup->options['encrypt'] ?? false) · {{ __('backups.encrypted') }} @endif
                            @if($backup->options['pre_restore'] ?? false) · {{ __('backups.pre_restore') }} @endif
                        </p>
                        @if($backup->checksum)<p class="font-mono text-[11px] text-gray-500" title="{{ __('backups.checksum') }}">{{ substr($backup->checksum, 0, 16) }}…</p>@endif
                    </td>
                    <td class="table-td">
                        <x-admin.status :tone="$statusTone[$backup->status] ?? 'gray'" :label="__('backups.statuses.'.$backup->status)" />
                        @if($backup->failure_message)<p class="mt-1 max-w-xs text-xs text-red-700">{{ \Illuminate\Support\Str::limit($backup->failure_message, 160) }}</p>@endif
                        @if($restoreInfo)
                            <p class="mt-1 text-xs {{ $restoreInfo['status'] === 'success' ? 'text-green-700' : 'text-red-700' }}">
                                {{ __($restoreInfo['status'] === 'success' ? 'backups.last_restore' : 'backups.last_restore_failed', ['date' => et_date(\Illuminate\Support\Carbon::parse($restoreInfo['at']), 'M d, Y H:i')]) }}
                            </p>
                        @endif
                    </td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $backup->started_at ? et_date($backup->started_at, 'M d, Y H:i') : '—' }}</td>
                    <td class="table-td table-td-muted hidden md:table-cell">{{ $backup->completed_at ? et_date($backup->completed_at, 'M d, Y H:i') : '—' }}</td>
                    <td class="table-td text-right tabular-nums">{{ $backup->size !== null ? Number::fileSize($backup->size, precision: 1) : '—' }}</td>
                    <td class="table-td table-td-muted hidden lg:table-cell">{{ $backup->initiator?->name ?? __('backups.scheduled') }}</td>
                    <td class="table-td">
                        @if($backup->status === 'success')
                        <div class="table-actions">
                            @can('backups.download')
                            <a href="{{ route('admin.backups.download', $backup) }}" class="btn btn-secondary btn-sm">{{ __('backups.download') }}</a>
                            @endcan
                            @can('backups.restore')
                            <button type="button" class="btn btn-secondary btn-sm"
                                    @click="restoreUrl = @js(route('admin.backups.restore', $backup)); restoreType = @js(__('backups.types.'.$backup->type)); $refs.restoreDialog.showModal()">{{ __('backups.restore') }}</button>
                            @endcan
                            @can('backups.delete')
                            <button type="button" class="btn btn-ghost btn-sm text-red-700"
                                    @click="deleteUrl = @js(route('admin.backups.destroy', $backup)); $refs.deleteDialog.showModal()">{{ __('backups.delete') }}</button>
                            @endcan
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
        <x-slot:footer>{{ $history->links() }}</x-slot:footer>
    </x-admin.table-card>

    {{-- Restore: explicit confirmation + password (+ authenticator code) --}}
    @can('backups.restore')
    <dialog x-ref="restoreDialog" class="w-full max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/60" aria-labelledby="restore-title">
        <form method="POST" :action="restoreUrl" class="space-y-4 p-6">
            @csrf
            <h2 id="restore-title" class="text-lg font-bold text-gray-900">{{ __('backups.restore_title') }}</h2>
            <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800" x-text="@js(__('backups.restore_warning')).replace(':type', restoreType)"></p>
            <div>
                <label for="restore-confirmation" class="form-label">{{ __('backups.restore_confirm_label') }}</label>
                <input id="restore-confirmation" name="confirmation" required autocomplete="off" pattern="RESTORE" class="form-input font-mono">
            </div>
            <div>
                <label for="restore-password" class="form-label">{{ __('backups.password') }}</label>
                <input type="password" id="restore-password" name="password" required autocomplete="current-password" class="form-input">
            </div>
            @if($needsOtp)
            <div>
                <label for="restore-otp" class="form-label">{{ __('backups.one_time_password') }}</label>
                <input id="restore-otp" name="one_time_password" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="form-input">
            </div>
            @endif
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="btn btn-secondary" @click="$refs.restoreDialog.close()">{{ __('backups.cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('backups.restore') }}</button>
            </div>
        </form>
    </dialog>
    @endcan

    @can('backups.delete')
    <dialog x-ref="deleteDialog" class="w-full max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/60" aria-labelledby="delete-title">
        <form method="POST" :action="deleteUrl" class="space-y-4 p-6">
            @csrf @method('DELETE')
            <h2 id="delete-title" class="text-lg font-bold text-gray-900">{{ __('backups.delete_title') }}</h2>
            <p class="text-sm text-gray-700">{{ __('backups.delete_warning') }}</p>
            <div>
                <label for="delete-password" class="form-label">{{ __('backups.password') }}</label>
                <input type="password" id="delete-password" name="password" required autocomplete="current-password" class="form-input">
            </div>
            @if($needsOtp)
            <div>
                <label for="delete-otp" class="form-label">{{ __('backups.one_time_password') }}</label>
                <input id="delete-otp" name="one_time_password" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="form-input">
            </div>
            @endif
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="btn btn-secondary" @click="$refs.deleteDialog.close()">{{ __('backups.cancel') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('backups.delete') }}</button>
            </div>
        </form>
    </dialog>
    @endcan
</div>
@endsection
