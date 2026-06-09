@props([
    'name',
    'label',
    'value'    => null,
    'required' => false,
    'class'    => '',
    'max'      => null,
    'min'      => null,
])

@php
    $hasServerError = $errors->has($name);
    $gcValue  = old($name, $value) ?? '';
    $safeMax  = $max ?? '';

    // Compute today's Ethiopian date in PHP — passed to JS so the calendar opens
    // on the right month/year from the very first render (no Alpine race condition).
    // Uses the shared, exact JDN-based service so JS and PHP always agree.
    $today   = now();
    $todayEt = \App\Services\EthiopianCalendar::fromGregorian($today->year, $today->month, $today->day);
    $todayEtYear  = $todayEt['year'];
    $todayEtMonth = $todayEt['month'];
    $todayEtDay   = $todayEt['day'];

    $safeMin  = $min ?? '';
    $initialViewYear  = $todayEtYear;
    $initialViewMonth = $todayEtMonth;

    $etMonths = ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'];
    $yearMin  = 1900;
    $yearMax  = $todayEtYear + 10;
@endphp

<div class="{{ $class }}"
     x-data="ethiopianDatepicker(@js($name), @js($gcValue), @js($safeMax), @js($safeMin), {{ $todayEtYear }}, {{ $todayEtMonth }}, {{ $todayEtDay }}, {{ $initialViewYear }}, {{ $initialViewMonth }})"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <label class="block text-sm font-medium text-gray-700">
        {!! $label !!}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    <input type="hidden" :name="fieldName" :value="gcValue">

    <div class="relative mt-1">
        <input type="text"
               :value="displayValue"
               @click="open = !open"
               readonly
               role="combobox"
               aria-haspopup="dialog"
               :aria-expanded="open"
               placeholder="{{ app()->getLocale() === 'am' ? 'ቀን ይምረጡ…' : 'Select date…' }}"
               :class="({{ $hasServerError ? 'true' : 'false' }} && !touched) || (touched && !!error)
                   ? 'border-red-400 bg-red-50' : 'border-gray-300 bg-white'"
               class="w-full cursor-pointer rounded-md border px-3 py-2 pr-9 text-sm shadow-sm transition focus:outline-none focus:ring-1 focus:ring-blue-500">

        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </span>

        {{-- Calendar dropdown --}}
        <div x-show="open"
             x-cloak
             role="dialog"
             aria-modal="false"
             aria-label="{{ app()->getLocale() === 'am' ? 'የቀን መምረጫ' : 'Date picker' }}"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute z-50 mt-1 w-72 rounded-xl border border-gray-200 bg-white shadow-2xl">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2.5">
                <button type="button" @click.stop="prevMonth()"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="flex items-center gap-1">
                    {{-- Month select — options pre-rendered in Blade (no x-for race condition) --}}
                    <select x-model.number="viewMonth"
                            class="rounded border-0 bg-transparent py-0.5 text-sm font-semibold text-gray-800 focus:ring-1 focus:ring-blue-400">
                        @foreach($etMonths as $mi => $mName)
                            <option value="{{ $mi + 1 }}">{{ $mName }}</option>
                        @endforeach
                    </select>

                    {{-- Year select — options pre-rendered in Blade (no x-for race condition) --}}
                    <select x-model.number="viewYear"
                            class="rounded border-0 bg-transparent py-0.5 text-sm font-semibold text-gray-800 focus:ring-1 focus:ring-blue-400">
                        @for($y = $yearMax; $y >= $yearMin; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <button type="button" @click.stop="nextMonth()"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Day-of-week headers --}}
            <div class="grid grid-cols-7 border-b border-gray-50 px-2 py-1">
                <template x-for="d in dayNames" :key="d">
                    <div class="py-1 text-center text-xs font-medium text-gray-400" x-text="d"></div>
                </template>
            </div>

            {{-- Day cells --}}
            <div class="grid grid-cols-7 gap-0.5 p-2">
                <template x-for="b in leadingBlanks" :key="'b'+b">
                    <div></div>
                </template>
                <template x-for="d in daysInMonth" :key="d">
                    <button type="button"
                            @click.stop="selectDay(d)"
                            :disabled="isDayDisabled(d)"
                            :aria-pressed="d === selDay && viewMonth === selMonth && viewYear === selYear"
                            :class="getDayClass(d)"
                            class="rounded-lg py-1.5 text-center text-sm leading-none transition disabled:cursor-not-allowed disabled:opacity-30">
                        <span x-text="d"></span>
                    </button>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between border-t border-gray-100 px-3 py-2">
                <button type="button" @click.stop="goToToday()"
                        class="text-xs font-medium text-blue-600 hover:text-blue-800 transition">
                    {{ app()->getLocale() === 'am' ? 'ዛሬ' : 'Today' }}
                    <span class="ml-1 text-gray-500">{{ $todayEtDay }} {{ $etMonths[$todayEtMonth - 1] }} {{ $todayEtYear }}</span>
                </button>
                <button type="button" @click.stop="clear()"
                        x-show="gcValue"
                        class="text-xs font-medium text-red-500 hover:text-red-700 transition">
                    {{ app()->getLocale() === 'am' ? 'አጽዳ' : 'Clear' }}
                </button>
            </div>
        </div>
    </div>

    @if($hasServerError)
    <p x-show="!touched" class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
    <p x-show="touched && !!error" x-text="error || ''" class="mt-1 text-xs text-red-600"></p>
</div>

@once
@push('scripts')
<script>
function ethiopianDatepicker(fieldName, gcInitialValue, maxGcValue, minGcValue, todayEtYear, todayEtMonth, todayEtDay, initialViewYear, initialViewMonth) {
    return {
        fieldName,
        open: false,
        gcValue: '',
        displayValue: '',
        viewYear: initialViewYear,
        viewMonth: initialViewMonth,
        selYear: null, selMonth: null, selDay: null,
        touched: false, error: '',

        dayNames: ['እሁ','ሰኞ','ማክ','ረቡ','ሐሙ','ዓርብ','ቅዳ'],
        monthNames: ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'],

        get daysInMonth() {
            if (this.viewMonth === 13) return this.isEtLeap(this.viewYear) ? 6 : 5;
            return 30;
        },

        get leadingBlanks() {
            const gc = this.etToGc(this.viewYear, this.viewMonth, 1);
            return new Date(gc.year, gc.month - 1, gc.day).getDay();
        },

        isEtLeap(y) { return y % 4 === 3; },

        init() {
            if (gcInitialValue) this.setFromGc(gcInitialValue);
        },

        setFromGc(str) {
            const p = str.split('-');
            if (p.length !== 3) return;
            const et = this.gcToEt(+p[0], +p[1], +p[2]);
            this.selYear = et.year; this.selMonth = et.month; this.selDay = et.day;
            this.viewYear = et.year; this.viewMonth = et.month;
            this.gcValue = str;
            this.displayValue = this.formatEt(et.year, et.month, et.day);
        },

        getDayClass(d) {
            const isSel   = d === this.selDay   && this.viewMonth === this.selMonth   && this.viewYear === this.selYear;
            const isToday = d === todayEtDay     && this.viewMonth === todayEtMonth   && this.viewYear === todayEtYear;
            if (this.isDayDisabled(d)) return 'text-gray-300';
            if (isSel)   return 'bg-blue-600 text-white font-semibold';
            if (isToday) return 'bg-blue-50 text-blue-700 font-medium ring-1 ring-inset ring-blue-200';
            return 'text-gray-700 hover:bg-gray-100';
        },

        isDayDisabled(d) {
            const gc  = this.etToGc(this.viewYear, this.viewMonth, d);
            const pad = n => String(n).padStart(2, '0');
            const gcStr = `${gc.year}-${pad(gc.month)}-${pad(gc.day)}`;
            if (maxGcValue && gcStr > maxGcValue) return true;
            if (minGcValue && gcStr < minGcValue) return true;
            return false;
        },

        selectDay(d) {
            if (this.isDayDisabled(d)) return;
            this.selYear = this.viewYear; this.selMonth = this.viewMonth; this.selDay = d;
            this.touched = true; this.error = '';
            const gc  = this.etToGc(this.viewYear, this.viewMonth, d);
            const pad = n => String(n).padStart(2, '0');
            this.gcValue      = `${gc.year}-${pad(gc.month)}-${pad(gc.day)}`;
            this.displayValue = this.formatEt(this.viewYear, this.viewMonth, d);
            this.open = false;
        },

        clear() {
            this.selYear = this.selMonth = this.selDay = null;
            this.gcValue = ''; this.displayValue = ''; this.touched = true; this.open = false;
        },

        prevMonth() {
            if (this.viewMonth === 1) { this.viewMonth = 13; this.viewYear--; }
            else this.viewMonth--;
        },

        nextMonth() {
            if (this.viewMonth === 13) { this.viewMonth = 1; this.viewYear++; }
            else this.viewMonth++;
        },

        goToToday() {
            this.viewYear = todayEtYear; this.viewMonth = todayEtMonth;
        },

        formatEt(y, m, d) { return `${d} ${this.monthNames[m - 1]} ${y}`; },

        // ── Exact integer JDN conversion (no Date/float, no DST drift) ──────
        // Mirrors App\Services\EthiopianCalendar so client and server agree.
        _ethiopicEpoch: 1724221,

        gToJdn(y, m, d) {
            return Math.floor((1461 * (y + 4800 + Math.floor((m - 14) / 12))) / 4)
                + Math.floor((367 * (m - 2 - 12 * Math.floor((m - 14) / 12))) / 12)
                - Math.floor((3 * Math.floor((y + 4900 + Math.floor((m - 14) / 12)) / 100)) / 4)
                + d - 32075;
        },

        jdnToG(j) {
            let l = j + 68569;
            const n = Math.floor((4 * l) / 146097);
            l = l - Math.floor((146097 * n + 3) / 4);
            const i = Math.floor((4000 * (l + 1)) / 1461001);
            l = l - Math.floor((1461 * i) / 4) + 31;
            const k = Math.floor((80 * l) / 2447);
            const d = l - Math.floor((2447 * k) / 80);
            l = Math.floor(k / 11);
            const m = k + 2 - 12 * l;
            const y = 100 * (n - 49) + i + l;
            return { year: y, month: m, day: d };
        },

        etToJdn(y, m, d) {
            return this._ethiopicEpoch + 365 * (y - 1) + Math.floor(y / 4) + 30 * (m - 1) + (d - 1);
        },

        jdnToEt(j) {
            const r = j - this._ethiopicEpoch;
            const year = Math.floor((4 * r + 1463) / 1461);
            const doy = r - (365 * (year - 1) + Math.floor(year / 4));
            return { year, month: Math.floor(doy / 30) + 1, day: (doy % 30) + 1 };
        },

        gcToEt(gy, gm, gd) {
            return this.jdnToEt(this.gToJdn(gy, gm, gd));
        },

        etToGc(ey, em, ed) {
            return this.jdnToG(this.etToJdn(ey, em, ed));
        },
    };
}
</script>
@endpush
@endonce
