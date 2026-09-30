<div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50/70 px-5 py-4 sm:px-8">
    @if($step > 1)
    <button type="button" @click="prevStep()"
            class="inline-flex h-12 items-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-[15px] font-bold text-gray-800 transition hover:bg-gray-50">
        <x-public.icon name="arrow-left" class="h-4 w-4" />
        {{ __('applicant.step_back') }}
    </button>
    @else
    <a href="{{ route('login') }}" class="text-[15px] font-bold text-brand hover:underline">{{ __('applicant.have_account_short') }}</a>
    @endif

    <button type="button" @click="nextStep()"
            class="inline-flex h-12 items-center gap-2 rounded-xl bg-brand px-6 text-[15px] font-bold text-white transition hover:bg-brand-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
        {{ __('applicant.step_next') }}
        <x-public.icon name="arrow-right" class="h-4 w-4" />
    </button>
</div>
