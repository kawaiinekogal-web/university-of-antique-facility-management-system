@extends('layouts.app')

@section('title', 'Request a Facility – UA Facility Management System')
{{-- Guest pages carry their own Back / Venue rates links, so the site's top bar is hidden --}}
@section('no-public-header', true)

@section('content')
@include('layouts.partials.page-kit')
@php
    $minActivityDate = now()->addDays(30)->toDateString(); // same rule as StoreFacilitiesUtilizationRequest
    // Event types guests pick most (the chips only prefill the purpose text)
    $eventTypes = ['Seminar', 'Meeting', 'Workshop', 'Assembly', 'Sports event', 'Celebration', 'Program'];
@endphp

<div class="pk-page pr">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/busalian-hall.jpg') }}')">
        <div>
            <h1>Request a Facility</h1>
            <p class="pk-hero-sub">No account needed. Fill in your details, pick a venue and date, then use your ticket code to track your request and complete payment after approval.</p>
        </div>
        <div class="pk-hero-actions">
            {{-- No top bar on this page: just a way back --}}
            <a href="{{ route('public.guest') }}" class="pk-btn-ghost" id="pr-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
                Back
            </a>
            <a href="{{ route('public.rates') }}" class="pk-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
                Venue rates
            </a>
        </div>
    </section>

    {{-- Official UA Header --}}
    <div class="ga-mast pr-mast">
        <img src="{{ asset('img/facilities/UA-logo.png') }}" alt="University of Antique seal">
        <div class="pr-mast-id">
            <p>Republic of the Philippines</p>
            <strong>UNIVERSITY OF ANTIQUE</strong>
            <p>Sibalom, Antique</p>
            <p class="ga-form-title">Application Form for Facilities Rental</p>
        </div>
        {{-- Paper-form references; display only (nothing here is submitted) --}}
        <dl class="pr-mast-meta">
            <div><dt>Form</dt><dd>GSU-FM-004 · Rev.0 / 03-26-26</dd></div>
            <div><dt>Ctrl. No.</dt><dd class="is-muted">Assigned after submission</dd></div>
            <div><dt>Date of Application</dt><dd>{{ date('F d, Y') }}</dd></div>
        </dl>
    </div>


    <div class="pk-form-layout">
        <form method="POST" action="{{ route('public.request.store') }}" enctype="multipart/form-data" class="pk-form-card" id="rental-form">
            @include('partials.form-errors', ['form' => 'rental-form'])
            @csrf
            <div class="pr-trap" aria-hidden="true"><label for="website">Website</label><input type="text" name="website" id="website" tabindex="-1" autocomplete="off"></div>

            {{-- Applicant --}}
            <div class="pk-step">
                <span class="pk-step-num">01</span>
                <div>
                    <h2>Applicant</h2>
                    <p class="pk-step-intro">Enter your name and the exact last name you will use with your ticket code to track this request.</p>
                    <div class="pk-grid-2">
                        <div class="pk-field">
                            <label class="pk-label" for="client_name">Full name <span class="pk-req">*</span></label>
                            <input type="text" name="client_name" id="client_name" required maxlength="120" pattern="\p{L}+([ '’\-]\p{L}+)*" title="Use letters, spaces, hyphens, or apostrophes only." autocomplete="name" class="pk-input" value="{{ old('client_name') }}" placeholder="First and last name" @error('client_name') aria-invalid="true" @enderror>
                            @error('client_name')<p class="pr-err">{{ $message }}</p>@enderror
                        </div>
                        <div class="pk-field">
                            <label class="pk-label" for="last_name">Last name for tracking <span class="pk-req">*</span></label>
                            <input type="text" name="last_name" id="last_name" required maxlength="120" pattern="\p{L}+([ '’\-]\p{L}+)*" title="Use letters, spaces, hyphens, or apostrophes only." autocomplete="family-name" class="pk-input" value="{{ old('last_name') }}" @error('last_name') aria-invalid="true" @enderror>
                            @error('last_name')<p class="pr-err">{{ $message }}</p>@enderror
                            <p class="pk-help">Enter it exactly as you will type it in the status tracker.</p>
                        </div>
                        <div class="pk-field">
                            <label class="pk-label" for="contact_number">Contact No. <span class="pk-req">*</span></label>
                            <input type="tel" name="contact_number" id="contact_number" required inputmode="numeric" maxlength="13" pattern="09[0-9]{2}-[0-9]{3}-[0-9]{4}" title="Enter a mobile number in the format 09XX-XXX-XXXX." class="pk-input" autocomplete="tel" value="{{ old('contact_number') }}" placeholder="09XX-XXX-XXXX" aria-describedby="contact-number-help" @error('contact_number') aria-invalid="true" @enderror>
                            @error('contact_number')<p class="pr-err">{{ $message }}</p>@enderror
                            <p class="pk-help" id="contact-number-help">Numbers only, in the format 09XX-XXX-XXXX.</p>
                        </div>
                        <div class="pk-field">
                            <label class="pk-label" for="email">Email Address <span class="pk-req">*</span></label>
                            <input type="email" name="email" id="email" required maxlength="160" autocomplete="email" class="pk-input" value="{{ old('email') }}" placeholder="name@example.com" aria-describedby="email-help" @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="pr-err">{{ $message }}</p>@enderror
                            <p class="pk-help" id="email-help">GSU will use this email to reach you about this request and your payment.</p>
                        </div>
                    </div>
                    <div class="u-stack-14 u-mt-14">
                        <div class="pk-field">
                            <label class="pk-label" for="organization">Organization/Agency <span class="pk-req">*</span></label>
                            <input type="text" name="organization" id="organization" required autocomplete="organization" class="pk-input" value="{{ old('organization') }}" placeholder="Company, agency or group you represent" @error('organization') aria-invalid="true" @enderror>
                            @error('organization')<p class="pr-err">{{ $message }}</p>@enderror
                        </div>
                        @include('partials.address-fields')
                    </div>
                </div>
            </div>

            @include('partials.request-form.schedule', ['step' => '02'])

            @include('partials.request-form.venues', [
                'step' => '03', 'scheduleStep' => 2, 'showRates' => true, 'afterView' => 'public.partials.venue-fee',
                'facilities' => collect($coreFacilities ?? [])->values(),
            ])

            {{-- Purpose + certification + Noted by --}}
            <div class="pk-step">
                <span class="pk-step-num">04</span>
                <div>
                    <h2>Purpose &amp; certification</h2>
                    <p class="pk-step-intro">Tell GSU what the event is, confirm the use of the venue and name your immediate head.</p>

                    @include('partials.request-form.purpose', ['eventTypes' => $eventTypes, 'placeholder' => 'e.g., Assembly — Barangay general assembly'])

                    <div class="ga-certify">
                        <p>I hereby certify that the venue shall be used solely for the purpose stated herein.</p>
                        <div class="pk-field">
                            <label class="pk-label" for="ga-rep">Client/Representative</label>
                            <input type="text" id="ga-rep" readonly class="pk-input" value="{{ old('client_name') }}" placeholder="Filled in from your name above">
                        </div>
                    </div>
                    <div class="pk-field u-mt-14">
                        <label class="pk-label" for="noted_signatory_custom">Noted by (Immediate Head) <span class="pk-req">*</span></label>
                        {{-- Guests always name their own head; the server expects noted_signatory_id = custom --}}
                        <input type="hidden" name="noted_signatory_id" value="custom">
                        <input type="text" name="noted_signatory_custom" id="noted_signatory_custom" required class="pk-input" autocomplete="name" value="{{ old('noted_signatory_custom') }}" placeholder="Full name" @error('noted_signatory_custom') aria-invalid="true" @enderror>
                        @error('noted_signatory_custom')<p class="pr-err">{{ $message }}</p>@enderror
                        @error('noted_signatory_id')<p class="pr-err">{{ $message }}</p>@enderror
                        <p class="pk-help">Full name of your organization's representative or immediate head.</p>
                    </div>
                    <p class="ga-small">Director/Unit Head and OIC-VP for Administration and Finance approval will be processed by the GSU office.</p>
                </div>
            </div>

            @include('partials.request-form.equipment', ['step' => '05'])

            {{-- Payment receipt + supporting documents --}}
            <div class="pk-step">
                <span class="pk-step-num">06</span>
                <div>
                    <h2>Documents</h2>
                    <p class="pk-step-intro">Upload supporting documents with your request. Payment receipt upload becomes available in ticket tracking after GSU approves your request.</p>
                    <label class="pk-drop">
                        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" data-empty="JPG, PNG, or PDF, max 10 files, 10MB each">
                        <span class="pk-drop-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.4 11.1l-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg></span>
                        <span><strong>Supporting Documents (Optional)</strong><span class="pk-drop-sub">JPG, PNG, or PDF; max 10 files, 10MB each</span></span>
                    </label>
                    @foreach ($errors->getMessages() as $key => $messages)
                        @if (str_starts_with($key, 'attachments'))
                            <p class="pr-err">{{ $messages[0] }}</p>
                        @endif
                    @endforeach
                    <div class="ga-pass" role="note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/></svg>
                        <span><strong>Note:</strong> This permit shall also serve as <strong>GATE PASS</strong> for the organizers.</span>
                    </div>
                </div>
            </div>

            {{-- Small screens: the summary sits below the form, so repeat the estimate right above Submit --}}
            <div class="pr-mtotal" aria-hidden="true">
                <span>Estimated venue total<small>Final amount is confirmed by GSU.</small></span>
                <strong id="sum-total-m">₱ 0.00</strong>
            </div>

            {{-- Submit Buttons --}}
            <div class="pk-form-actions">
                <a href="{{ route('home') }}" class="pk-btn-outline">Cancel</a>
                <button type="submit" id="submitBtn" class="pk-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                    <span>Submit Application</span>
                </button>
            </div>
        </form>

        <aside class="pk-aside" aria-label="Application summary">
            @include('partials.request-form.summary', ['label' => 'Application summary'])
            <div class="ga-estimate">
                <span>Estimated venue total</span>
                <strong id="sum-total">₱ 0.00</strong>
                <p>Final amount is confirmed by GSU.</p>
            </div>
        </aside>
    </div>
</div>

@include('partials.request-form.script', ['formId' => 'rental-form'])
<script>
(function () {
// Back: return to the previous page on this site, otherwise fall back to Guest Access
document.getElementById('pr-back').addEventListener('click', function (e) {
    try {
        if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) {
            e.preventDefault();
            history.back();
        }
    } catch (err) {}
});

// Rental fee estimate: picked venue card × number of days in the chosen range (final amount is confirmed by GSU)
const form = document.getElementById('rental-form');
function selectedVenues() { return Array.from(form.querySelectorAll('input[name="facility_ids[]"]:checked')); }
function bookingDays() {
    const s = form.querySelector('input[name="date_activity"]').value;
    const endInput = form.querySelector('input[name="date_end"]');
    const e = endInput && !endInput.disabled ? endInput.value : '';
    if (!s) return 1;
    if (!e || e <= s) return 1;
    return Math.round((new Date(e + 'T00:00') - new Date(s + 'T00:00')) / 864e5) + 1;
}
const peso = (n) => '₱ ' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function calculateTotal() {
    const venues = selectedVenues();
    // Per-head venues (classrooms, ₱50/head a day) are rate × number of persons; the rest are a flat daily rate
    const persons = parseInt(document.getElementById('number_of_persons').value, 10) || 0;
    const rentalFee = venues.reduce((total, venue) => total + (parseFloat(venue.dataset.rentalFee) || 0) * (venue.dataset.perHead ? persons : 1), 0);
    const startTime = form.querySelector('input[name="start_time"]').value;
    const endTime = form.querySelector('input[name="end_time"]').value;
    let hours = 0;
    if (startTime && endTime) {
        hours = Math.abs(new Date('2000-01-01 ' + endTime) - new Date('2000-01-01 ' + startTime)) / 36e5;
    }
    const days = bookingDays();
    const total = rentalFee * days;
    document.getElementById('facilityNameCell').textContent = venues.map(venue => venue.dataset.name).join(', ') || '-';
    document.getElementById('rentalFeeCell').textContent = peso(rentalFee);
    document.getElementById('daysCell').textContent = days;
    document.getElementById('hoursCell').textContent = hours.toFixed(1);
    document.getElementById('totalCell').textContent = peso(total);
}
form.addEventListener('input', calculateTotal);
form.addEventListener('change', calculateTotal);
calculateTotal();

// "Client/Representative" mirrors the name typed in step 01
document.getElementById('client_name').addEventListener('input', function () { document.getElementById('ga-rep').value = this.value; });

// The estimate shown beside (and, on small screens, above Submit) follows the fee breakdown
function syncEstimate() {
    document.getElementById('sum-total').textContent = document.getElementById('totalCell').textContent;
    document.getElementById('sum-total-m').textContent = document.getElementById('totalCell').textContent;
}
form.addEventListener('input', () => setTimeout(syncEstimate));
form.addEventListener('change', () => setTimeout(syncEstimate));
syncEstimate();
})();
</script>
@include('partials.venue-equipment-suggest')
@include('partials.facility-time-availability')
@include('partials.phone-number-mask', ['inputId' => 'contact_number'])
@endsection
