<div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl">
    @if($step > 1)
    <button type="button" @click="prevStep()"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
        {{ __('applicant.step_back') }}
    </button>
    @else
    <span></span>
    @endif

    <button type="button" @click="nextStep()"
            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition">
        {{ __('applicant.step_next') }}
    </button>
</div>
