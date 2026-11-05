{{-- Request form step: venue availability board (multi-select). Driven by partials.request-form.script
     (or admin.bookings.partials.booking-picker-script on the admin pages).
     Params: $step, $scheduleStep (for the "pick a date" hint), $facilities (ordered collection),
             $showRates (guests only: published rate + data-rental-fee), $isMine (optional fn(Facility): bool), $mineLabel,
             $afterView (optional view rendered under the board, e.g. the guest fee breakdown). --}}
@php
    $showRates ??= false;
    $isMine ??= null;
    $mineLabel ??= null;
    $selectedFacilityIds = array_map('intval', (array) old('facility_ids', old('facility_id') ? [old('facility_id')] : ($selectedFacilityIds ?? [])));
@endphp
<div class="pk-step">
    <span class="pk-step-num">{{ $step }}</span>
    <div>
        <h2>Venues <span class="pk-req u-text-danger">*</span></h2>
        <p class="pk-step-intro">Select facilities for the same schedule. Existing reservations block overlapping times; non-overlapping times remain available.</p>
        @if ($facilities->isEmpty())
            <p class="u-mb-4 u-rounded-xl u-border u-border-amber-200 u-bg-amber-50 u-p-4 u-text-sm" role="status">
                No venues are currently available to request. Please contact GSU Facility Management.
            </p>
        @endif
        <div class="vb-head">
            <span class="vb-day" id="vb-day">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>
                <span id="vb-day-text" data-idle="Pick a date in step {{ $scheduleStep }} to see booked times">Pick a date in step {{ $scheduleStep }} to see booked times</span>
            </span>
            <span class="vb-key" aria-hidden="true">
                <span><i class="u-bg-success"></i>Available</span>
                <span><i class="u-bg-warn"></i>Partly booked · AM or PM only</span>
                <span><i class="u-bg-danger"></i>Fully booked · whole day</span>
            </span>
        </div>
        <div class="vb-grid" role="group" aria-label="Venues">
            @foreach ($facilities as $facility)
                @php
                    $isUnavailable = in_array($facility->availability_status, ['unavailable', 'maintenance'], true);
                    $statusLabel = $facility->availability_status === 'maintenance' ? 'Under Maintenance' : 'Unavailable';
                    $owner = match ($facility->owner_type) { 'gsu' => 'GSU', 'college' => 'College (' . ($facility->owner_college ?? 'N/A') . ')', 'org' => 'Organization', default => ucfirst($facility->owner_type ?? '') };
                    $mine = $isMine && $isMine($facility);
                @endphp
                <div class="vb-card" data-name="{{ $facility->name }}">
                    <input type="checkbox" name="facility_ids[]" id="venue-{{ $facility->id }}" value="{{ $facility->id }}"
                           data-name="{{ $facility->name }}"
                           @if ($showRates) data-rental-fee="{{ (float) (\App\Support\VenueRates::rateFor($facility->name) ?? 0) }}" @if (\App\Support\VenueRates::isPerHead($facility->name)) data-per-head="1" @endif @endif
                           data-capacity="{{ \App\Support\VenueRates::seatingCapacityFor($facility->name) }}"
                           data-equipment="{{ json_encode(\App\Support\VenueRates::suggestedEquipmentFor($facility->name)) }}"
                           {{ in_array($facility->id, $selectedFacilityIds, true) ? 'checked' : '' }}
                           {{ $isUnavailable ? 'disabled' : '' }}>
                    <label for="venue-{{ $facility->id }}">
                        <span class="vb-top">
                            <span class="u-minw-0">
                                <span class="vb-name">{{ $facility->name }}</span>
                                <span class="vb-owner">{{ $owner }}@if ($mine)<span class="vb-mine">{{ $mineLabel }}</span>@endif @if ($showRates)<span class="vb-rate">{{ \App\Support\VenueRates::rateLabelFor($facility->name) ?? 'Rate set by GSU' }}</span>@endif</span>
                                @include('partials.facility-seating-capacity', ['facility' => $facility])
                            </span>
                            <span class="vb-count"><strong class="vb-count-num">–</strong><span>booked</span></span>
                        </span>
                        <span class="vb-slots" aria-live="polite"><span class="vb-idle">{{ $isUnavailable ? 'Not available for booking' : 'Pick a date to see times' }}</span></span>
                        <span class="vb-clash">This time conflicts with an existing reservation.</span>
                        <span class="vb-foot">
                            <span class="pk-pill {{ $isUnavailable ? ($facility->availability_status === 'maintenance' ? 'is-warn' : 'is-bad') : 'is-good' }} vb-status">{{ $isUnavailable ? $statusLabel : 'Available' }}</span>
                            <span class="u-row-6">
                                <span class="vb-pick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>Selected</span>
                            </span>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
        @error('facility_ids')<p class="pr-err">{{ $message }}</p>@enderror
        @error('facility_ids.*')<p class="pr-err">{{ $message }}</p>@enderror
        <p class="pr-err" id="facility-selection-error" role="alert" hidden>Select at least one venue.</p>
        @isset($afterView) @include($afterView) @endisset
    </div>
</div>
