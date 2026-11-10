@extends('layouts.org')

@section('org-content')
@include('layouts.partials.page-kit')
@php
    $minActivityDate = now()->addDays(30)->toDateString(); // same rule as StoreFacilitiesUtilizationRequest
    // "Noted by" (required): a college Dean or Program Head, or someone else with their position
    $notedOld = (string) old('noted_signatory_id', '');
    $eventTypes = ['General assembly', 'Seminar', 'Meeting', 'Workshop', 'Sports event', 'Program', 'Outreach'];
@endphp

<div class="pk-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/paghiusa-hall.jpg') }}')">
        <div>
            <h1>Facilities Utilization Request</h1>
            <p class="pk-hero-sub">Submit a request for facility and equipment usage. Date of request will be recorded automatically after submission.</p>
        </div>
        <div class="pk-hero-actions">
            <a href="{{ route('org.requests.facilities.index') }}" class="pk-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/></svg>
                Facility requests
            </a>
        </div>
    </section>


    <div class="pk-form-layout">
        <form method="POST" action="{{ route('org.requests.facilities.store') }}" class="pk-form-card" id="request-form" enctype="multipart/form-data">
            @include('partials.form-errors', ['form' => 'request-form'])
            @csrf

            @include('partials.request-form.schedule', ['step' => '01'])

            @include('partials.request-form.venues', ['step' => '02', 'scheduleStep' => 1, 'facilities' => collect($coreFacilities ?? [])->values()])

            {{-- Purpose + Noted by --}}
            <div class="pk-step">
                <span class="pk-step-num">03</span>
                <div>
                    <h2>Purpose &amp; signatory</h2>
                    <p class="pk-step-intro">Tell GSU what the event is, then choose the Dean or Program Head who will note your request.</p>

                    @include('partials.request-form.purpose', ['eventTypes' => $eventTypes, 'placeholder' => 'e.g., General assembly — Orientation for new members'])

                    <div class="pp-block">
                        <p class="pp-title"><span class="pp-title-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Who will note it? <span class="pk-req u-text-danger">*</span></p>
                        <p class="pp-sub">Organization requests must be noted by a college <strong>Dean</strong> or <strong>Program Head</strong>, even if you're the Governor or another officer. Pick one and type their name.</p>

                        @error('noted_signatory_id')<p class="pk-error sg-error">{{ $message }}</p>@enderror
                        @error('noted_signatory_name')<p class="pk-error sg-error">{{ $message }}</p>@enderror
                        @error('noted_signatory_position')<p class="pk-error sg-error">{{ $message }}</p>@enderror

                        @php
                            $notedCards = [
                                'dean'         => ['Dean', 'DN', 'Name of the Dean'],
                                'program_head' => ['Program Head', 'PH', 'Name of the Program Head'],
                            ];
                        @endphp
                        <div class="sg-grid sg-grid--noted" role="radiogroup" aria-label="Noted by" aria-required="true">
                            @foreach ($notedCards as $value => [$title, $initials, $placeholder])
                                <div class="sg-card">
                                    <input type="radio" name="noted_signatory_id" id="sg-{{ $value }}" value="{{ $value }}" required {{ $notedOld === $value ? 'checked' : '' }}>
                                    <label for="sg-{{ $value }}">
                                        <span class="sg-avatar" aria-hidden="true">{{ $initials }}</span>
                                        <span class="u-minw-0"><span class="sg-name">{{ $title }}</span><span class="sg-role">College {{ strtolower($title) }}</span></span>
                                        <span class="sg-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                                        <span class="sg-type">
                                            <input type="text" name="noted_signatory_name" class="sg-type-input" data-noted-for="{{ $value }}" maxlength="255" autocomplete="name"
                                                   placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
                                                   value="{{ $notedOld === $value ? old('noted_signatory_name') : '' }}" {{ $notedOld === $value ? 'required' : 'disabled' }}>
                                        </span>
                                    </label>
                                </div>
                            @endforeach

                            <div class="sg-card">
                                <input type="radio" name="noted_signatory_id" id="sg-custom" value="custom" required {{ $notedOld === 'custom' ? 'checked' : '' }}>
                                <label for="sg-custom">
                                    <span class="sg-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></span>
                                    <span class="u-minw-0"><span class="sg-name">Someone else</span><span class="sg-role">Type position &amp; name</span></span>
                                    <span class="sg-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                                    <span class="sg-type sg-type--pair">
                                        <input type="text" name="noted_signatory_position" class="sg-type-input" data-noted-for="custom" maxlength="120" autocomplete="organization-title"
                                               placeholder="Position, e.g. OIC Dean" aria-label="Their position"
                                               value="{{ $notedOld === 'custom' ? old('noted_signatory_position') : '' }}" {{ $notedOld === 'custom' ? 'required' : 'disabled' }}>
                                        <input type="text" name="noted_signatory_name" class="sg-type-input" data-noted-for="custom" maxlength="255" autocomplete="name"
                                               placeholder="Their name" aria-label="Their name"
                                               value="{{ $notedOld === 'custom' ? old('noted_signatory_name') : '' }}" {{ $notedOld === 'custom' ? 'required' : 'disabled' }}>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.request-form.equipment', ['step' => '04'])

            @include('partials.request-form.documents', ['step' => '05'])

            {{-- Submit --}}
            <div class="pk-form-actions">
                <a href="{{ url()->previous() }}" class="pk-btn-outline">Cancel</a>
                <button type="submit" id="submitBtn" class="pk-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
                    <span>Submit Request</span>
                </button>
            </div>
        </form>

        <aside class="pk-aside" aria-label="Request summary">
            @include('partials.request-form.summary', ['label' => 'Request summary'])
        </aside>
    </div>
</div>

@include('partials.request-form.script', ['formId' => 'request-form'])
<script>
// Noted by (required): a college Dean or Program Head, or someone else with their position
(function () {
    const notedRadios = document.querySelectorAll('input[name="noted_signatory_id"]');

    // Only the picked card's boxes are sent (and required); focus its first box
    function updateNotedUI(focus) {
        const picked = document.querySelector('input[name="noted_signatory_id"]:checked');
        document.querySelectorAll('[data-noted-for]').forEach(box => {
            const on = !!picked && box.dataset.notedFor === picked.value;
            box.disabled = !on;
            box.required = on;
        });
        const first = picked && picked.closest('.sg-card').querySelector('.sg-type-input');
        if (focus && first) first.focus({ preventScroll: true });
    }
    notedRadios.forEach(r => r.addEventListener('change', () => updateNotedUI(true)));
    updateNotedUI(false);
})();
</script>
@include('partials.venue-equipment-suggest')
@include('partials.facility-time-availability')
@endsection
