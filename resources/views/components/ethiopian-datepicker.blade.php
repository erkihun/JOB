@props([
    'name',
    'label',
    'value'    => null,
    'required' => false,
    'class'    => '',
    'max'      => null,
])

@php
    $hasServerError = $errors->has($name);
    $gcValue = old($name, $value) ?? '';
    $safeMax = $max ?? '';
@endphp

<div class="{{ $class }}"
     x-data="ethiopianDatepicker(@js($name), @js($gcValue), @js($safeMax))"
     x-init="init()"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <label class="block text-sm font-medium text-gray-700">
        {!! $label !!}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    {{-- Hidden Gregorian value submitted with the form --}}
    <input type="hidden" :name="fieldName" :value="gcValue">

    {{-- Visible display input --}}
    <div class="relative mt-1">
        <input type="text"
               :value="displayValue"
               @click="open = !open"
               readonly
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
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute z-50 mt-1 w-72 rounded-xl border border-gray-200 bg-white shadow-2xl">

            {{-- Month / Year header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2.5">
                <button type="button" @click.stop="prevMonth()"
                        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="flex items-center gap-1">
                    {{-- Month selector --}}
                    <select x-model.number="viewMonth"
                            @change.stop=""
                            class="rounded border-0 bg-transparent py-0.5 text-sm font-semibold text-gray-800 focus:ring-1 focus:ring-blue-400">
                        <template x-for="(m, i) in monthNames" :key="i">
                            <option :value="i + 1" x-text="m"></option>
                        </template>
                    </select>
                    {{-- Year selector --}}
                    <select x-model.number="viewYear"
                            @change.stop=""
                            class="rounded border-0 bg-transparent py-0.5 text-sm font-semibold text-gray-800 focus:ring-1 focus:ring-blue-400">
                        <template x-for="y in yearRange" :key="y">
                            <option :value="y" x-text="y"></option>
                        </template>
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
                {{-- Leading empty cells --}}
                <template x-for="b in leadingBlanks" :key="'b'+b">
                    <div></div>
                </template>
                {{-- Day buttons --}}
                <template x-for="d in daysInMonth" :key="d">
                    <button type="button"
                            @click.stop="selectDay(d)"
                            :disabled="isDayDisabled(d)"
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
                </button>
                <button type="button" @click.stop="clear()"
                        x-show="gcValue"
                        class="text-xs font-medium text-red-500 hover:text-red-700 transition">
                    {{ app()->getLocale() === 'am' ? 'አጽዳ' : 'Clear' }}
                </button>
            </div>
        </div>
    </div>

    {{-- Errors --}}
    @if($hasServerError)
    <p x-show="!touched" class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
    <p x-show="touched && !!error" x-text="error || ''" class="mt-1 text-xs text-red-600"></p>
</div>

@once
@push('scripts')
<script>
function ethiopianDatepicker(fieldName, gcInitialValue, maxGcValue) {
    return {
        fieldName,
        open: false,
        gcValue: '',
        displayValue: '',
        viewYear: 2016,
        viewMonth: 1,
        selYear: null,
        selMonth: null,
        selDay: null,
        touched: false,
        error: '',

        monthNames: ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'],
        dayNames: ['እሁ','ሰኞ','ማክ','ረቡ','ሐሙ','ዓርብ','ቅዳ'],

        get yearRange() {
            const years = [];
            for (let y = 2070; y >= 1900; y--) years.push(y);
            return years;
        },

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
            else {
                const t = new Date();
                const et = this.gcToEt(t.getFullYear(), t.getMonth() + 1, t.getDate());
                this.viewYear = et.year;
                this.viewMonth = et.month;
            }
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
            const isSel = d === this.selDay && this.viewMonth === this.selMonth && this.viewYear === this.selYear;
            const t = new Date();
            const te = this.gcToEt(t.getFullYear(), t.getMonth() + 1, t.getDate());
            const isToday = d === te.day && this.viewMonth === te.month && this.viewYear === te.year;
            if (this.isDayDisabled(d)) return 'text-gray-300';
            if (isSel) return 'bg-blue-600 text-white font-semibold';
            if (isToday) return 'bg-blue-50 text-blue-700 font-medium ring-1 ring-inset ring-blue-200';
            return 'text-gray-700 hover:bg-gray-100';
        },

        isDayDisabled(d) {
            if (!maxGcValue) return false;
            const gc = this.etToGc(this.viewYear, this.viewMonth, d);
            const pad = n => String(n).padStart(2,'0');
            const gcStr = `${gc.year}-${pad(gc.month)}-${pad(gc.day)}`;
            return gcStr > maxGcValue;
        },

        selectDay(d) {
            if (this.isDayDisabled(d)) return;
            this.selYear = this.viewYear; this.selMonth = this.viewMonth; this.selDay = d;
            this.touched = true; this.error = '';
            const gc = this.etToGc(this.viewYear, this.viewMonth, d);
            const pad = n => String(n).padStart(2,'0');
            this.gcValue = `${gc.year}-${pad(gc.month)}-${pad(gc.day)}`;
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
            const t = new Date();
            const et = this.gcToEt(t.getFullYear(), t.getMonth() + 1, t.getDate());
            this.viewYear = et.year; this.viewMonth = et.month;
            this.selectDay(et.day);
        },

        formatEt(y, m, d) {
            return `${d} ${this.monthNames[m - 1]} ${y}`;
        },

        gcToEt(gy, gm, gd) {
            const prev = gy - 1;
            const prevLeap = prev % 400 === 0 || (prev % 4 === 0 && prev % 100 !== 0);
            const nyDay = prevLeap ? 12 : 11;
            let etY = (gm > 9 || (gm === 9 && gd >= nyDay)) ? gy - 7 : gy - 8;
            const nyGcY = etY + 7;
            const p2 = nyGcY - 1;
            const p2Leap = p2 % 400 === 0 || (p2 % 4 === 0 && p2 % 100 !== 0);
            const ny = p2Leap ? 12 : 11;
            const diff = Math.round((new Date(gy, gm-1, gd) - new Date(nyGcY, 8, ny)) / 86400000);
            return { year: etY, month: Math.floor(diff / 30) + 1, day: (diff % 30) + 1 };
        },

        etToGc(ey, em, ed) {
            const nyGcY = ey + 7;
            const p = nyGcY - 1;
            const pLeap = p % 400 === 0 || (p % 4 === 0 && p % 100 !== 0);
            const nyDay = pLeap ? 12 : 11;
            const d = new Date(nyGcY, 8, nyDay);
            d.setDate(d.getDate() + (em - 1) * 30 + (ed - 1));
            return { year: d.getFullYear(), month: d.getMonth() + 1, day: d.getDate() };
        },
    };
}
</script>
@endpush
@endonce
