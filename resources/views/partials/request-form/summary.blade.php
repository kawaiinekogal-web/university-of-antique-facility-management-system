{{-- Live summary "ticket" beside a request form (filled in by the form's script).
     Params: $label ("Request summary"), $rows (optional [[label, id, empty text], ...]); put extra panels after the include. --}}
@php
    $rows ??= [
        ['Venue', 'sum-venue', 'None selected'],
        ['Purpose', 'sum-purpose', '—'],
        ['Persons', 'sum-persons', '—'],
        ['Equipment', 'sum-equip', 'None'],
    ];
@endphp
<p class="pk-aside-label">{{ $label }}</p>
<div class="pk-ticket">
    <div class="pk-ticket-top">
        <p class="pk-ticket-date" id="sum-date">Pick a date</p>
        <p class="pk-ticket-time" id="sum-time">Start – end time</p>
    </div>
    <div class="pk-ticket-cut" aria-hidden="true"></div>
    <div class="pk-ticket-body">
        @foreach ($rows as [$rowLabel, $rowId, $rowEmpty])
            <div class="pk-ticket-row"><span>{{ $rowLabel }}</span><strong id="{{ $rowId }}" class="is-empty">{{ $rowEmpty }}</strong></div>
        @endforeach
    </div>
</div>
