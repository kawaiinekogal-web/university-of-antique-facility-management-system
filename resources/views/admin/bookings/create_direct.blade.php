@extends('layouts.admin')
@section('title', 'Create direct reservation · UA Facility Management System')

@section('admin-content')
@include('layouts.partials.page-kit')
{{-- Same building blocks as the College/Org Facilities Utilization Request (date picker, venue board,
     equipment tiles, summary ticket). Fields and names are unchanged from the original direct form. --}}
{{-- Venue board, equipment and summary are the shared request-form partials; the calendar and live summary
     are driven by admin.bookings.partials.booking-picker-script (shared with the edit page). --}}

<div class="pk-page drv-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/busalian-hall.jpg') }}')">
        <div>
            <p class="pk-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg>
                Priority booking
            </p>
            <h1>Create Direct Reservation</h1>
            <p class="pk-hero-sub">Book a facility right away for urgent, high-priority events. It's reserved as soon as you submit.</p>
        </div>
        <div class="pk-hero-actions">
            <a href="{{ route('admin.bookings.index') }}" class="pk-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2z"/></svg>
                All reservations
            </a>
        </div>
    </section>

    <div class="drv-notice" role="note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
        <p>Use this only for urgent, high-priority bookings (emergency meetings, high-profile events).
            Conflicting bookings will be set to <strong>Pending</strong> and their requesters notified.</p>
    </div>


    <div class="pk-form-layout">
        <form action="{{ route('admin.bookings.store-direct') }}" method="POST" class="pk-form-card" id="direct-form">
            @include('partials.form-errors', ['form' => 'direct-form'])
            @csrf

            {{-- Date & Time --}}
            <div class="pk-step">
                <span class="pk-step-num">01</span>
                <div>
                    <h2>Schedule</h2>
                    <p class="pk-step-intro">When the activity takes place.</p>
                    <div class="dp">
                        <div class="dp-cal">
                            <div class="dp-head">
                                <p class="dp-month" id="dp-month" aria-live="polite"></p>
                                <div class="dp-nav">
                                    <button type="button" id="dp-prev" aria-label="Previous month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button>
                                    <button type="button" id="dp-next" aria-label="Next month"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg></button>
                                </div>
                            </div>
                            <div class="dp-week" aria-hidden="true"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
                            <div class="dp-grid" id="dp-grid" role="group" aria-label="Choose the date of activity"></div>
                            <ul class="dp-legend" aria-hidden="true">
                                <li><i class="u-bg-slate-200"></i>Booked</li>
                                <li><i class="u-bg-ink"></i>Selected</li>
                                <li><i class="u-ring-ink"></i>Today</li>
                            </ul>
                            <p class="dp-status" id="dp-status" aria-live="polite"></p>
                        </div>

                        <div class="dp-side">
                            <div class="dp-fields">
                                <div class="dp-dates is-single">
                                    <div class="pk-field">
                                        <label for="date_activity" class="pk-label">Date of Activity <span class="pk-req">*</span></label>
                                        <input type="text" name="date_activity" id="date_activity" class="pk-input" required inputmode="numeric" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder="YYYY-MM-DD" value="{{ old('date_activity') }}">
                                    </div>
                                </div>
                                <div class="dp-times">
                                    <div class="pk-field">
                                        <label for="start_time" class="pk-label">Start Time <span class="pk-req">*</span></label>
                                        <input type="time" name="start_time" id="start_time" class="pk-input" required min="06:00" max="19:00" value="{{ old('start_time') }}">
                                    </div>
                                    <div class="pk-field">
                                        <label for="end_time" class="pk-label">End Time <span class="pk-req">*</span></label>
                                        <input type="time" name="end_time" id="end_time" class="pk-input" required min="06:00" max="19:00" value="{{ old('end_time') }}">
                                    </div>
                                </div>
                            </div>
                            <p class="pk-help">Pick a day on the calendar or type it. Reservations must be between 6:00 AM and 7:00 PM.</p>
                            <div class="dp-dayinfo" id="dp-dayinfo" aria-live="polite">
                                <h3>On this day</h3>
                                <p>Pick a date to see what's already booked.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.request-form.venues', ['step' => '02', 'scheduleStep' => 1, 'facilities' => collect($facilities)])

            {{-- Requester + purpose (same fields as before, restyled) --}}
            <div class="pk-step">
                <span class="pk-step-num">03</span>
                <div>
                    <h2>Requester &amp; purpose</h2>
                    <p class="pk-step-intro">Who this urgent booking is for and why.</p>

                    <div class="drv-fields">
                        <div class="pk-field">
                            <label class="pk-label" for="requester_name">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                <span>Requester Name / Unit <span class="pk-req">*</span></span>
                            </label>
                            <input type="text" name="requester_name" id="requester_name" class="pk-input" required value="{{ old('requester_name') }}" placeholder="e.g. CCIS Dean" aria-describedby="requester_help">
                            <p class="pk-help" id="requester_help">Ex: "CCIS Dean", "CEA Program Head", "GSU Office", or specific office/organization.</p>
                        </div>

                        <div class="pk-field">
                            <label class="pk-label" for="purpose">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
                                <span>Purpose <span class="pk-req">*</span></span>
                            </label>
                            <textarea name="purpose" id="purpose" rows="3" class="pk-input" required aria-describedby="purpose_help">{{ old('purpose') }}</textarea>
                            <p class="pk-help" id="purpose_help">What the event is, in a sentence or two.</p>
                        </div>

                        @include('partials.number-of-persons')
                    </div>
                </div>
            </div>

            @include('partials.request-form.equipment', ['step' => '04'])

            <div class="pk-form-actions">
                <a href="{{ route('admin.bookings.index') }}" class="pk-btn-outline">Cancel</a>
                <button type="submit" class="pk-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9z"/></svg>
                    Create Priority Booking
                </button>
            </div>
        </form>

        <aside class="pk-aside" aria-label="Booking summary">
            @include('partials.request-form.summary', ['label' => 'Booking summary', 'rows' => [
                ['Venues', 'sum-venues', 'None selected'], ['Requester', 'sum-requester', '—'], ['Purpose', 'sum-purpose', '—'],
                ['Persons', 'sum-persons', '—'], ['Equipment', 'sum-equip', 'None'],
            ]])
        </aside>
    </div>
</div>

@include('admin.bookings.partials.booking-picker-script')
@include('partials.venue-equipment-suggest')
<script>
(function () {
    const form = document.getElementById('direct-form');
    const $ = (id) => document.getElementById(id);
    const { pickedVenues, venueBoxes, pad } = window.bkPicker;

    // ---- Live summary (display only) ----
    const fmtTime = (v) => {
        if (!v) return '';
        const [h, m] = v.split(':').map(Number);
        return ((h % 12) || 12) + ':' + pad(m) + (h < 12 ? ' AM' : ' PM');
    };
    function setText(el, text, empty) {
        el.textContent = text || empty;
        el.classList.toggle('is-empty', !text);
    }
    function render() {
        const d = $('date_activity').value;
        $('sum-date').textContent = d
            ? new Date(d + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' })
            : 'Pick a date';
        const s = fmtTime($('start_time').value), e = fmtTime($('end_time').value);
        $('sum-time').textContent = s || e ? (s || '…') + ' – ' + (e || '…') : 'Start – end time';
        setText($('sum-venues'), pickedVenues().join(', '), 'None selected');
        setText($('sum-requester'), $('requester_name').value.trim(), '—');
        const purpose = $('purpose').value.trim();
        setText($('sum-purpose'), purpose.length > 90 ? purpose.slice(0, 90) + '…' : purpose, '—');
        setText($('sum-persons'), $('number_of_persons').value, '—');
        const equip = [...form.querySelectorAll('.eq-count')]
            .filter(i => parseInt(i.value || '0', 10) > 0 && i.dataset.label)
            .map(i => i.value + ' × ' + i.dataset.label);
        setText($('sum-equip'), equip.join(', '), 'None');
        if (venueBoxes().some(b => b.checked)) $('facility-selection-error').hidden = true;
    }
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    // An empty venue choice on submit is reported by partials.form-errors
    render();
})();
</script>
@endsection
