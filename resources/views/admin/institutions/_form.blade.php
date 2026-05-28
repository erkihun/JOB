{{-- Shared create/edit form partial --}}
<div class="space-y-6">

    {{-- Basic Info --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6" style="box-shadow: var(--shadow-card)">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('admin.institution_basic_info') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">

            <div class="sm:col-span-2">
                <label class="form-label" for="name">{{ __('admin.institution_name') }} <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $institution->name) }}"
                       class="form-input @error('name') border-red-400 @enderror" required>
                @error('name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="short_name">{{ __('admin.institution_short_name') }}</label>
                <input type="text" id="short_name" name="short_name" value="{{ old('short_name', $institution->short_name) }}"
                       class="form-input @error('short_name') border-red-400 @enderror">
                @error('short_name')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="code">{{ __('admin.institution_code') }} <span class="text-red-500">*</span></label>
                <input type="text" id="code" name="code" value="{{ old('code', $institution->code) }}"
                       class="form-input font-mono uppercase @error('code') border-red-400 @enderror" required>
                @error('code')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="type">{{ __('admin.institution_type') }}</label>
                <input type="text" id="type" name="type" value="{{ old('type', $institution->type) }}"
                       class="form-input @error('type') border-red-400 @enderror"
                       placeholder="{{ __('admin.institution_type_placeholder') }}">
                @error('type')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="status">{{ __('admin.column.status') }} <span class="text-red-500">*</span></label>
                <select id="status" name="status" class="form-select @error('status') border-red-400 @enderror" required>
                    <option value="active" {{ old('status', $institution->status ?? 'active') === 'active' ? 'selected' : '' }}>
                        {{ __('admin.status_active') }}
                    </option>
                    <option value="inactive" {{ old('status', $institution->status) === 'inactive' ? 'selected' : '' }}>
                        {{ __('admin.status_inactive') }}
                    </option>
                </select>
                @error('status')<p class="form-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    {{-- Contact Info --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6" style="box-shadow: var(--shadow-card)">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('admin.institution_contact_info') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">

            <div>
                <label class="form-label" for="email">{{ __('admin.column.email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email', $institution->email) }}"
                       class="form-input @error('email') border-red-400 @enderror">
                @error('email')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="phone">{{ __('admin.column.phone') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $institution->phone) }}"
                       class="form-input @error('phone') border-red-400 @enderror">
                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="form-label" for="website">{{ __('admin.institution_website') }}</label>
                <input type="url" id="website" name="website" value="{{ old('website', $institution->website) }}"
                       class="form-input @error('website') border-red-400 @enderror"
                       placeholder="https://...">
                @error('website')<p class="form-error">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <label class="form-label" for="address">{{ __('admin.institution_address') }}</label>
                <textarea id="address" name="address" rows="3"
                          class="form-input @error('address') border-red-400 @enderror">{{ old('address', $institution->address) }}</textarea>
                @error('address')<p class="form-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

    {{-- Location / Google Map --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6" style="box-shadow: var(--shadow-card)"
         x-data="mapPicker({{ old('latitude', $institution->latitude ?? 9.0054) }}, {{ old('longitude', $institution->longitude ?? 38.7636) }}, {{ ($institution->latitude && $institution->longitude) ? 'true' : 'false' }})">

        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ __('admin.institution_location') }}</h2>
        <p class="mb-4 text-xs text-gray-400">{{ __('admin.institution_location_hint') }}</p>

        {{-- Search box --}}
        <div class="mb-3 flex gap-2">
            <input type="text" id="map-search"
                   placeholder="{{ __('admin.institution_map_search') }}"
                   class="form-input flex-1"
                   @keydown.enter.prevent="geocodeSearch()">
            <button type="button" @click="geocodeSearch()"
                    class="btn btn-navy shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                {{ __('messages.search') }}
            </button>
        </div>

        {{-- Map container --}}
        <div id="institution-map"
             class="w-full rounded-xl border border-gray-200 overflow-hidden"
             style="height: 360px;"
             x-ref="mapContainer">
        </div>

        <p class="mt-2 text-xs text-gray-400">{{ __('admin.institution_map_click_hint') }}</p>

        {{-- Coordinate inputs (hidden visually but part of form) --}}
        <div class="mt-4 grid grid-cols-2 gap-4">
            <div>
                <label class="form-label" for="latitude">{{ __('admin.institution_latitude') }}</label>
                <input type="number" id="latitude" name="latitude" step="0.0000001"
                       min="-90" max="90"
                       x-model="lat"
                       class="form-input font-mono @error('latitude') border-red-400 @enderror"
                       placeholder="9.0054">
                @error('latitude')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="form-label" for="longitude">{{ __('admin.institution_longitude') }}</label>
                <input type="number" id="longitude" name="longitude" step="0.0000001"
                       min="-180" max="180"
                       x-model="lng"
                       class="form-input font-mono @error('longitude') border-red-400 @enderror"
                       placeholder="38.7636">
                @error('longitude')<p class="form-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function mapPicker(initLat, initLng, hasPin) {
    return {
        lat: hasPin ? initLat : '',
        lng: hasPin ? initLng : '',
        map: null,
        marker: null,

        init() {
            this.$nextTick(() => this.initMap());
        },

        initMap() {
            const defaultCenter = { lat: initLat || 9.0054, lng: initLng || 38.7636 };

            this.map = new google.maps.Map(document.getElementById('institution-map'), {
                center: defaultCenter,
                zoom: hasPin ? 14 : 6,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
            });

            // Place existing pin if coordinates saved
            if (hasPin) {
                this.placeMarker(defaultCenter);
            }

            // Click on map to place/move pin
            this.map.addListener('click', (e) => {
                this.placeMarker(e.latLng);
                this.lat = parseFloat(e.latLng.lat().toFixed(7));
                this.lng = parseFloat(e.latLng.lng().toFixed(7));
            });
        },

        placeMarker(position) {
            if (this.marker) {
                this.marker.setPosition(position);
            } else {
                this.marker = new google.maps.Marker({
                    position,
                    map: this.map,
                    draggable: true,
                    title: '{{ addslashes($institution->name ?? "Institution") }}',
                });

                this.marker.addListener('dragend', (e) => {
                    this.lat = parseFloat(e.latLng.lat().toFixed(7));
                    this.lng = parseFloat(e.latLng.lng().toFixed(7));
                });
            }
            this.map.panTo(position);
        },

        geocodeSearch() {
            const query = document.getElementById('map-search').value.trim();
            if (!query) return;

            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: query }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const loc = results[0].geometry.location;
                    this.lat = parseFloat(loc.lat().toFixed(7));
                    this.lng = parseFloat(loc.lng().toFixed(7));
                    this.map.setCenter(loc);
                    this.map.setZoom(15);
                    this.placeMarker(loc);
                } else {
                    alert('{{ __("admin.institution_map_not_found") }}');
                }
            });
        },
    };
}
</script>
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key', '') }}&libraries=places&callback=Function.prototype">
</script>
@endpush
