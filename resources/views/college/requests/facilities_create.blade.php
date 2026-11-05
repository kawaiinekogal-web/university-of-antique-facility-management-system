@extends('layouts.college')

@section('college-content')
@include('layouts.partials.page-kit')
@php
    $minActivityDate = now()->addDays(30)->toDateString(); // same rule as StoreFacilitiesUtilizationRequest
    // Signatory cards (same values the old dropdown sent: "dean:ID", "program_head:ID", ...)
    $signatoryCards = collect();
    // Team members with a position on the Users page come first (value "user:ID"; their real name is saved)
    foreach (($teamSignatories ?? []) as $member) {
        $signatoryCards->push(['value' => 'user:' . $member->id, 'name' => $member->name, 'role' => $member->position, 'unit' => null, 'team' => true]);
    }
    foreach ([['deans', 'dean', 'Dean'], ['programHeads', 'program_head', 'Program Head'], ['presidents', 'president', 'President'], ['advisers', 'adviser', 'Adviser']] as [$var, $prefix, $role]) {
        foreach (($$var ?? []) as $sig) {
            $signatoryCards->push(['value' => $prefix . ':' . $sig->id, 'name' => $sig->name, 'role' => $role, 'unit' => $sig->unit]);
        }
    }
    $notedOld = (string) old('noted_signatory_id', '');
    $eventTypes = ['Seminar', 'Meeting', 'Workshop', 'Ceremony', 'Thesis defense', 'Sports event', 'Program'];
@endphp

<div class="pk-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/paghiusa-hall.jpg') }}')">
        <div>
            <h1>Facilities Utilization Request</h1>
            <p class="pk-hero-sub">Submit a request for facility and equipment usage. Date of request will be recorded automatically after submission.</p>
        </div>
        <div class="pk-hero-actions">
            <a href="{{ route('college.requests.index') }}" class="pk-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13l3.5 7v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/></svg>
                My requests
            </a>
        </div>
    </section>


    <div class="pk-form-layout">
        <form method="POST" action="{{ route('college.requests.facilities.store') }}" class="pk-form-card" id="request-form" enctype="multipart/form-data">
            @include('partials.form-errors', ['form' => 'request-form'])
            @csrf

            @include('partials.request-form.schedule', ['step' => '01'])

            @php
                $myCollege = strtolower(trim((string) (auth()->user()->college_name ?? '')));
                $isMine = fn ($f) => $myCollege !== '' && strtolower(trim((string) $f->owner_college)) === $myCollege;
            @endphp
            {{-- Your college's facilities first, otherwise the order the controller gave --}}
            @include('partials.request-form.venues', [
                'step' => '02', 'scheduleStep' => 1, 'isMine' => $isMine, 'mineLabel' => 'Your college',
                'facilities' => collect($coreFacilities ?? [])->sortBy(fn ($f) => $isMine($f) ? 0 : 1)->values(),
            ])

            {{-- Purpose + Noted by --}}
            <div class="pk-step">
                <span class="pk-step-num">03</span>
                <div>
                    <h2>Purpose &amp; signatory</h2>
                    <p class="pk-step-intro">Tell GSU what the event is, then choose who will note your request.</p>

                    @include('partials.request-form.purpose', ['eventTypes' => $eventTypes, 'placeholder' => 'e.g., Seminar — Research colloquium for 4th-year CCIS students'])

                    <div class="pp-block">
                        <p class="pp-title"><span class="pp-title-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Who will note it?</p>
                        <p class="pp-sub">Pick who signs as "Noted by". Your team's officers are listed first. You can skip this.</p>
                        <div class="sg-grid" role="radiogroup" aria-label="Noted by">
                            @foreach ($signatoryCards as $i => $card)
                                @php $initials = collect(preg_split('/\s+/', trim($card['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''); @endphp
                                <div class="sg-card">
                                    <input type="radio" name="noted_signatory_id" id="sg-{{ $i }}" value="{{ $card['value'] }}" {{ $notedOld === $card['value'] ? 'checked' : '' }}>
                                    <label for="sg-{{ $i }}">
                                        <span class="sg-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                                        <span class="u-minw-0">
                                            <span class="sg-name">{{ $card['name'] }}</span>
                                            <span class="sg-role">{{ $card['role'] }}{{ $card['unit'] && ! \Illuminate\Support\Str::contains($card['name'], $card['unit']) ? ' · ' . $card['unit'] : '' }}</span>
                                        </span>
                                        <span class="sg-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                                        @if (empty($card['team']))
                                        <span class="sg-type">
                                            <input type="text" class="sg-type-input" maxlength="255" autocomplete="name" placeholder="Type their name" aria-label="Name of the {{ $card['name'] }} (optional)">
                                        </span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                            <div class="sg-card">
                                <input type="radio" name="noted_signatory_id" id="sg-custom" value="custom" {{ $notedOld === 'custom' ? 'checked' : '' }}>
                                <label for="sg-custom">
                                    <span class="sg-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></span>
                                    <span class="u-minw-0"><span class="sg-name">Someone else</span><span class="sg-role">Not listed</span></span>
                                    <span class="sg-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
                                    <span class="sg-type" id="custom-noted-input">
                                        <input type="text" name="noted_signatory_custom" id="noted_signatory_custom" class="sg-type-input" maxlength="255" autocomplete="name" placeholder="Type their name" aria-label="Name of the person who will note it" value="{{ old('noted_signatory_custom') }}">
                                    </span>
                                </label>
                            </div>
                            <div class="sg-card is-muted">
                                <input type="radio" name="noted_signatory_id" id="sg-none" value="" {{ $notedOld === '' ? 'checked' : '' }}>
                                <label for="sg-none">
                                    <span class="sg-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/></svg></span>
                                    <span class="u-minw-0"><span class="sg-name">Skip for now</span><span class="sg-role">No signatory</span></span>
                                    <span class="sg-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></span>
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
// Noted by: college team members, deans, program heads, presidents, advisers, or someone else
(function () {
    const notedRadios = document.querySelectorAll('input[name="noted_signatory_id"]');

    // Each card shows its own name box while selected (CSS); focus it when a card is picked
    function updateNotedUI() {
        const picked = document.querySelector('input[name="noted_signatory_id"]:checked');
        const box = picked && picked.closest('.sg-card').querySelector('.sg-type-input');
        if (box) box.focus({ preventScroll: true });
    }
    notedRadios.forEach(r => r.addEventListener('change', updateNotedUI));

    // A name typed on the Dean / Program Head card is sent through the existing "Someone else" field,
    // so the request saves that person's name; left blank, the card's title is saved as before.
    const requestForm = document.getElementById('request-form');
    if (requestForm) requestForm.addEventListener('submit', function () {
        const picked = document.querySelector('input[name="noted_signatory_id"]:checked');
        if (!picked || !picked.value.includes(':')) return;
        const nameBox = picked.closest('.sg-card').querySelector('.sg-type-input');
        if (!nameBox) return; // team members: their real name is saved
        const typed = nameBox.value.trim();
        if (!typed) return;
        document.getElementById('sg-custom').checked = true;
        document.getElementById('noted_signatory_custom').value = typed;
    });
})();
</script>
@include('partials.venue-equipment-suggest')
@include('partials.facility-time-availability')
@endsection
