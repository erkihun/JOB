{{--
    Recruitment lifecycle panel for one announcement.
    Expects: $announcement, $stage (RecruitmentStage), $remainingDays, $nextActions,
             $canCancel, $extensionBlocked (?string), $canExtend
    Every button here is only a shortcut: the server re-checks permission and the
    state machine (ChangeRecruitmentStageAction / ExtendRecruitmentDeadlineAction).
--}}
@php
    use App\Enums\RecruitmentStage;

    $steps = [
        RecruitmentStage::Open, RecruitmentStage::Closed, RecruitmentStage::Screening,
        RecruitmentStage::Exam, RecruitmentStage::Interview, RecruitmentStage::Finalized,
    ];
    $position = match ($stage) {
        RecruitmentStage::Draft, RecruitmentStage::Upcoming, RecruitmentStage::Cancelled => -1,
        default => array_search($stage, $steps, true),
    };
    $closingLabel = et_date($announcement->closing_date, 'M d, Y');
    $timing = match ($stage) {
        RecruitmentStage::Open => trans_choice('recruitment.days_remaining', $remainingDays ?? 0, ['count' => $remainingDays ?? 0]),
        RecruitmentStage::Upcoming => __('recruitment.opens_in', ['date' => et_date($announcement->opening_date, 'M d, Y')]),
        RecruitmentStage::Draft => __('recruitment.blocked.draft'),
        default => $announcement->closing_date ? __('recruitment.closed_on', ['date' => $closingLabel]) : '—',
    };
    $blockedText = fn (?string $key) => match ($key) {
        null => null,
        'recruitment.errors.close_before_deadline', 'recruitment.errors.screening_before_close' => $stage === RecruitmentStage::Upcoming
            ? __('recruitment.blocked.upcoming', ['date' => et_date($announcement->opening_date, 'M d, Y')])
            : __('recruitment.blocked.open', ['date' => $closingLabel]),
        default => __($key),
    };
@endphp

<section class="card" aria-labelledby="lifecycle-heading" x-data="{}">
    <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h2 id="lifecycle-heading" class="card-title">{{ __('recruitment.lifecycle') }}</h2>
            <p class="mt-0.5 text-sm text-gray-600">{{ __('recruitment.lifecycle_hint') }}</p>
        </div>
        <x-admin.status :tone="$stage->tone()" :label="$stage->label()" class="self-start sm:self-auto" />
    </div>

    <div class="space-y-5 p-5 sm:p-6">
        {{-- Stepper --}}
        @if($stage === RecruitmentStage::Cancelled)
            <p class="rounded-lg bg-gray-100 px-4 py-3 text-sm font-semibold text-gray-700" role="status">
                {{ __('recruitment.stage.cancelled') }} · {{ et_date($announcement->cancelled_at) }}
            </p>
        @else
        <ol class="grid grid-cols-3 gap-2 sm:grid-cols-6" aria-label="{{ __('recruitment.lifecycle') }}">
            @foreach($steps as $i => $step)
            @php
                $state = $position === false || $position < 0 ? 'todo' : ($i < $position ? 'done' : ($i === $position ? 'current' : 'todo'));
                $label = $i === 0 && in_array($stage, [RecruitmentStage::Draft, RecruitmentStage::Upcoming], true) ? $stage->label() : $step->label();
            @endphp
            <li class="rounded-lg border px-3 py-2 text-center text-xs font-semibold
                       {{ $state === 'current' ? 'border-brand bg-brand-muted text-brand-dark ring-1 ring-brand' : ($state === 'done' ? 'border-green-200 bg-green-50 text-green-800' : 'border-gray-200 text-gray-500') }}"
                @if($state === 'current') aria-current="step" @endif>
                <span class="block text-[11px] font-medium opacity-70">{{ $i + 1 }}</span>
                {{ $label }}
            </li>
            @endforeach
        </ol>
        @endif

        {{-- Key facts --}}
        <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
            <div><dt class="text-gray-600">{{ __('vacancies.opening_date') }}</dt><dd class="font-semibold text-gray-900">{{ et_date($announcement->opening_date, 'M d, Y') }}</dd></div>
            <div><dt class="text-gray-600">{{ __('vacancies.closing_date') }}</dt><dd class="font-semibold text-gray-900">{{ $closingLabel }}</dd></div>
            <div><dt class="text-gray-600">{{ __('recruitment.current_stage') }}</dt><dd class="font-semibold text-gray-900">{{ $timing }}</dd></div>
            <div><dt class="text-gray-600">{{ __('recruitment.applications_count') }}</dt><dd class="font-semibold tabular-nums text-gray-900">{{ number_format($announcement->applications_count ?? 0) }}</dd></div>
            <div><dt class="text-gray-600">{{ __('recruitment.exam_required') }}</dt><dd class="font-semibold text-gray-900">{{ $announcement->exam_required ? __('dashboard.yes') : __('dashboard.no') }}</dd></div>
        </dl>

        {{-- Next allowed actions --}}
        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-[13px] font-semibold text-gray-600">{{ __('recruitment.next_action') }}</h3>
            <div class="mt-3 space-y-3">
                @forelse($nextActions as $next)
                @php
                    $target = $next['target'];
                    $actionKey = match ($target->value) {
                        'closed' => 'close', 'screening' => 'start_screening', 'exam' => 'start_exam',
                        'interview' => 'start_interview', 'finalized' => 'finalize', default => $target->value,
                    };
                    $reason = ! $next['authorized'] ? __('messages.unauthorized') : $blockedText($next['blocked']);
                @endphp
                <form method="POST" action="{{ route('admin.announcements.transition', $announcement) }}"
                      class="flex flex-col gap-2 rounded-xl border border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between"
                      @if($target->value === 'finalized') onsubmit="return confirm(@js(__('recruitment.action_hint.finalize')))" @endif>
                    @csrf
                    <input type="hidden" name="target" value="{{ $target->value }}">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ __('recruitment.action.'.$actionKey) }}</p>
                        <p class="text-xs {{ $reason ? 'text-amber-700' : 'text-gray-600' }}">{{ $reason ?? __('recruitment.action_hint.'.$actionKey) }}</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm shrink-0" @disabled($reason !== null)
                            @if($reason) aria-disabled="true" title="{{ $reason }}" @endif>
                        {{ __('recruitment.action.'.$actionKey) }}
                    </button>
                </form>
                @empty
                <p class="text-sm text-gray-600">
                    {{ $stage === RecruitmentStage::Open ? __('recruitment.blocked.open', ['date' => $closingLabel])
                        : ($stage === RecruitmentStage::Upcoming ? __('recruitment.blocked.upcoming', ['date' => et_date($announcement->opening_date, 'M d, Y')])
                        : ($stage === RecruitmentStage::Draft ? __('recruitment.blocked.draft') : __('recruitment.no_next_action'))) }}
                </p>
                @endforelse
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                {{-- Deadline extension: separate, confirmed, reasoned action --}}
                @if($canExtend && in_array($announcement->status, ['published', 'closed'], true))
                    @if($extensionBlocked)
                        <p class="w-full text-xs text-amber-700">{{ __('recruitment.extension.unavailable', ['reason' => __($extensionBlocked)]) }}</p>
                    @else
                        <button type="button" class="btn btn-secondary btn-sm" @click="$refs.extendDialog.showModal()">{{ __('recruitment.action.extend_deadline') }}</button>
                    @endif
                @endif
                @if($canCancel)
                    <button type="button" class="btn btn-ghost btn-sm text-red-700" @click="$refs.cancelDialog.showModal()">{{ __('recruitment.action.cancel') }}</button>
                @endif
            </div>
        </div>
    </div>

    @if($canExtend && ! $extensionBlocked)
    <dialog x-ref="extendDialog" class="w-full max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/60" aria-labelledby="extend-title">
        <form method="POST" action="{{ route('admin.announcements.extend-deadline', $announcement) }}" class="space-y-4 p-6">
            @csrf
            <h2 id="extend-title" class="text-lg font-bold text-gray-900">{{ __('recruitment.extension.title') }}</h2>
            <p class="text-sm text-gray-600">{{ __('recruitment.extension.intro') }}</p>
            <p class="rounded-lg bg-gray-50 px-3 py-2 text-sm">{{ __('recruitment.extension.current_deadline') }}: <strong>{{ $closingLabel }}</strong></p>
            <div>
                <label for="new_closing_date" class="form-label">{{ __('recruitment.extension.new_deadline') }} <span class="form-required">*</span></label>
                <input type="date" id="new_closing_date" name="new_closing_date" required
                       min="{{ max($announcement->closing_date->copy()->addDay()->toDateString(), today()->toDateString()) }}"
                       value="{{ old('new_closing_date') }}" class="form-input @error('new_closing_date') form-input-error @enderror">
                @error('new_closing_date')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="extend_reason" class="form-label">{{ __('recruitment.reason') }} <span class="form-required">*</span></label>
                <textarea id="extend_reason" name="reason" rows="3" required minlength="10" maxlength="2000"
                          placeholder="{{ __('recruitment.reason_placeholder') }}"
                          class="form-textarea @error('reason') form-input-error @enderror">{{ old('reason') }}</textarea>
                @error('reason')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="extend_reference" class="form-label">{{ __('recruitment.extension.reference') }}</label>
                <input type="text" id="extend_reference" name="reference" maxlength="255" value="{{ old('reference') }}" class="form-input">
                <p class="form-hint">{{ __('recruitment.extension.reference_hint') }}</p>
            </div>
            <label class="flex items-start gap-2 text-sm text-gray-800">
                <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                <span>{{ __('recruitment.extension.confirm_label') }}</span>
            </label>
            @error('confirm')<p class="form-error">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="btn btn-secondary" @click="$refs.extendDialog.close()">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('recruitment.extension.submit') }}</button>
            </div>
        </form>
    </dialog>
    @endif

    @if($canCancel)
    <dialog x-ref="cancelDialog" class="w-full max-w-lg rounded-2xl p-0 shadow-2xl backdrop:bg-slate-900/60" aria-labelledby="cancel-title">
        <form method="POST" action="{{ route('admin.announcements.transition', $announcement) }}" class="space-y-4 p-6">
            @csrf
            <input type="hidden" name="target" value="cancelled">
            <h2 id="cancel-title" class="text-lg font-bold text-gray-900">{{ __('recruitment.action.cancel') }}</h2>
            <p class="text-sm text-red-700">{{ __('recruitment.action_hint.cancel') }}</p>
            <div>
                <label for="cancel_reason" class="form-label">{{ __('recruitment.reason') }} <span class="form-required">*</span></label>
                <textarea id="cancel_reason" name="reason" rows="3" required maxlength="2000"
                          placeholder="{{ __('recruitment.reason_placeholder') }}" class="form-textarea"></textarea>
            </div>
            <div class="flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" class="btn btn-secondary" @click="$refs.cancelDialog.close()">{{ __('messages.close') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('recruitment.confirm') }}</button>
            </div>
        </form>
    </dialog>
    @endif

    @if($errors->hasAny(['new_closing_date', 'reason', 'confirm']) && $canExtend && ! $extensionBlocked)
        <div x-init="$nextTick(() => $refs.extendDialog?.showModal())"></div>
    @endif
</section>
