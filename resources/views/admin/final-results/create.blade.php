@extends('layouts.admin')

@section('title', $result ? __('messages.edit_result') : __('messages.add_result'))

@section('content')
<div class="space-y-5" x-data="{
    examScore: '{{ old('exam_score', $result?->exam_score ?? '') }}',
    interviewScore: '{{ old('interview_score', $result?->interview_score ?? '') }}',
    examWeight: {{ old('exam_weight', $examWeight) }},
    interviewWeight: {{ old('interview_weight', $interviewWeight) }},
    get finalScore() {
        const e = parseFloat(this.examScore);
        const i = parseFloat(this.interviewScore);
        const ew = parseFloat(this.examWeight) || 0;
        const iw = parseFloat(this.interviewWeight) || 0;
        const hasE = !isNaN(e);
        const hasI = !isNaN(i);
        if (!hasE && !hasI) return '—';
        let total = 0, usedWeight = 0;
        if (hasE) { total += e * (ew / 100); usedWeight += ew; }
        if (hasI) { total += i * (iw / 100); usedWeight += iw; }
        if (usedWeight === 0) return '—';
        return (total * (100 / usedWeight)).toFixed(2);
    },
    syncWeights() {
        const ew = parseFloat(this.examWeight);
        if (!isNaN(ew)) {
            this.interviewWeight = Math.max(0, 100 - ew);
        }
    }
}">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.final-results.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-semibold text-gray-900">
                {{ $result ? __('messages.edit_result') : __('messages.add_result') }}
            </h1>
            <p class="mt-0.5 text-sm text-gray-500">
                {{ $application->applicant?->full_name }} &middot; {{ $application->reference_number }}
            </p>
        </div>
    </div>

    <form method="POST"
          action="{{ $result
              ? route('admin.final-results.update', $application)
              : route('admin.final-results.store', $application) }}">
        @csrf
        @if($result)
            @method('PUT')
        @endif

        <div class="grid gap-5 lg:grid-cols-3">

            {{-- Left: Applicant info --}}
            <div class="space-y-4 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-0.5 rounded bg-accent"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.applicant') }}</h2>
                </div>
                <div class="space-y-2 text-sm text-gray-700">
                    <div><span class="font-medium">{{ __('fields.full_name') }}:</span> {{ $application->applicant?->full_name }}</div>
                    <div><span class="font-medium">{{ __('messages.reference') }}:</span> <span class="font-mono">{{ $application->reference_number }}</span></div>
                    <div><span class="font-medium">{{ __('menus.vacancies') }}:</span> {{ $application->vacancy?->title }}</div>
                    <div><span class="font-medium">{{ __('messages.submitted') }}:</span> {{ et_date($application->submitted_at) }}</div>
                </div>
            </div>

            {{-- Center & Right: Score entry --}}
            <div class="space-y-5 lg:col-span-2">

                {{-- Weight settings --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-4 w-0.5 rounded bg-brand"></div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.score_weights') }}</h2>
                        <span class="ml-auto text-xs text-gray-400">{{ __('messages.weight_hint') }}</span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('messages.exam_weight') }} (%)
                            </label>
                            <input type="number" name="exam_weight" x-model="examWeight"
                                   @input="syncWeights()"
                                   min="0" max="100" step="1"
                                   class="form-input mt-1 @error('exam_weight') border-red-500 @enderror">
                            @error('exam_weight')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('messages.interview_weight') }} (%)
                            </label>
                            <input type="number" name="interview_weight" x-model="interviewWeight"
                                   @input="examWeight = Math.max(0, 100 - parseFloat(interviewWeight) || 0)"
                                   min="0" max="100" step="1"
                                   class="form-input mt-1 @error('interview_weight') border-red-500 @enderror">
                            @error('interview_weight')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Scores --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-4 w-0.5 rounded bg-brand"></div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.scores') }}</h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('messages.exam_score') }} <span class="text-gray-400 font-normal">(0–100)</span>
                            </label>
                            <input type="number" name="exam_score" x-model="examScore"
                                   min="0" max="100" step="0.01"
                                   placeholder="—"
                                   class="form-input mt-1 @error('exam_score') border-red-500 @enderror">
                            @error('exam_score')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                {{ __('messages.interview_score') }} <span class="text-gray-400 font-normal">(0–100)</span>
                            </label>
                            <input type="number" name="interview_score" x-model="interviewScore"
                                   min="0" max="100" step="0.01"
                                   placeholder="—"
                                   class="form-input mt-1 @error('interview_score') border-red-500 @enderror">
                            @error('interview_score')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('messages.final_score') }}</label>
                            <div class="mt-1 flex h-10 items-center rounded-lg border border-gray-200 bg-gray-50 px-3 font-semibold text-gray-800" x-text="finalScore"></div>
                        </div>
                    </div>
                </div>

                {{-- Decision + Remarks --}}
                <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-4 w-0.5 rounded bg-brand"></div>
                        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">{{ __('messages.decision') }}</h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('messages.decision') }}</label>
                            <select name="decision" class="form-select mt-1 @error('decision') border-red-500 @enderror">
                                <option value="">— {{ __('messages.select') }} —</option>
                                <option value="selected"     @selected(old('decision', $result?->decision) === 'selected')>{{ __('messages.selected') }}</option>
                                <option value="waitlisted"   @selected(old('decision', $result?->decision) === 'waitlisted')>{{ __('messages.waitlisted') }}</option>
                                <option value="not_selected" @selected(old('decision', $result?->decision) === 'not_selected')>{{ __('messages.not_selected') }}</option>
                            </select>
                            @error('decision')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('messages.remarks') }}</label>
                            <textarea name="remarks" rows="2"
                                      class="form-textarea mt-1 @error('remarks') border-red-500 @enderror"
                                      placeholder="{{ __('messages.remarks_placeholder') }}">{{ old('remarks', $result?->remarks) }}</textarea>
                            @error('remarks')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary">{{ __('messages.save_changes') }}</button>
                    <a href="{{ route('admin.final-results.index') }}" class="btn btn-outline">{{ __('messages.cancel') }}</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
