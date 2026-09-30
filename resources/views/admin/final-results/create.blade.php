@extends('layouts.admin')

@section('title', $result ? __('messages.edit_result') : __('messages.add_result'))

@section('content')
<div class="space-y-5" x-data="{
    examScore: '{{ old('exam_score', $result?->exam_score ?? $recordedExamScore ?? '') }}',
    interviewScore: '{{ old('interview_score', $result?->interview_score ?? $recordedInterviewScore ?? '') }}',
    practicalScore: '{{ old('practical_score', $result?->practical_score ?? $recordedPracticalScore ?? '') }}',
    examWeight: {{ (float) old('exam_weight', $examWeight) }},
    interviewWeight: {{ (float) old('interview_weight', $interviewWeight) }},
    practicalWeight: {{ (float) old('practical_weight', $practicalWeight) }},
    get weightTotal() {
        return Math.round(((parseFloat(this.examWeight) || 0) + (parseFloat(this.interviewWeight) || 0) + (parseFloat(this.practicalWeight) || 0)) * 100) / 100;
    },
    // Same rule as FinalResult::computeFinalScore(): missing scores are left out
    // and the remaining weights are scaled back up to 100.
    get finalScore() {
        let total = 0, used = 0;
        [[this.examScore, this.examWeight], [this.interviewScore, this.interviewWeight], [this.practicalScore, this.practicalWeight]]
            .forEach(([s, w]) => {
                const score = parseFloat(s), weight = parseFloat(w) || 0;
                if (!isNaN(score) && weight > 0) { total += score * (weight / 100); used += weight; }
            });
        return used === 0 ? '—' : (total * (100 / used)).toFixed(2);
    }
}">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.final-results.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="page-title">
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
            <div class="space-y-4 card card-body">
                <div class="flex items-center gap-2">
                    <h2 class="card-title">{{ __('messages.applicant') }}</h2>
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

                {{-- Weights (defaults from Settings › Result weights) --}}
                <div class="card card-body">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <h2 class="card-title">{{ __('messages.score_weights') }}</h2>
                        <span class="ml-auto rounded-full px-2.5 py-0.5 text-xs font-semibold"
                              :class="weightTotal === 100 ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900'"
                              x-text="@js(__('messages.weights_total')).replace(':total', weightTotal)"></span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach([
                            'exam' => __('messages.exam_weight'),
                            'interview' => __('messages.interview_weight'),
                            'practical' => __('messages.practical_weight'),
                        ] as $part => $label)
                        <div>
                            <label for="{{ $part }}_weight" class="form-label">{{ $label }} (%)</label>
                            <input type="number" id="{{ $part }}_weight" name="{{ $part }}_weight" x-model="{{ $part }}Weight"
                                   min="0" max="100" step="0.01"
                                   class="form-input @error($part.'_weight') form-input-error @enderror">
                            @error($part.'_weight')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                    </div>
                    <p class="form-hint" x-show="weightTotal !== 100" x-cloak>{{ __('messages.weights_should_total') }}</p>
                </div>

                {{-- Scores --}}
                <div class="card card-body">
                    <div class="mb-4 flex items-center gap-2">
                        <h2 class="card-title">{{ __('messages.scores') }}</h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach([
                            'exam' => __('messages.exam_score'),
                            'interview' => __('messages.interview_score'),
                            'practical' => __('messages.practical_score'),
                        ] as $part => $label)
                        <div>
                            <label for="{{ $part }}_score" class="form-label">{{ $label }} <span class="font-normal text-gray-600">(0–100)</span></label>
                            <input type="number" id="{{ $part }}_score" name="{{ $part }}_score" x-model="{{ $part }}Score"
                                   min="0" max="100" step="0.01" placeholder="—"
                                   :disabled="!(parseFloat({{ $part }}Weight) > 0)"
                                   class="form-input @error($part.'_score') form-input-error @enderror">
                            @error($part.'_score')<p class="form-error">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                        <div>
                            <p class="form-label">{{ __('messages.final_score') }}</p>
                            <div class="flex h-10 items-center rounded-lg border border-brand/30 bg-brand-muted px-3 text-lg font-bold tabular-nums text-brand" x-text="finalScore" aria-live="polite"></div>
                        </div>
                    </div>
                    <p class="form-hint">{{ __('messages.final_score_hint') }}</p>
                </div>

                {{-- Decision + Remarks --}}
                <div class="card card-body">
                    <div class="flex items-center gap-2 mb-4">
                        <h2 class="card-title">{{ __('messages.decision') }}</h2>
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
