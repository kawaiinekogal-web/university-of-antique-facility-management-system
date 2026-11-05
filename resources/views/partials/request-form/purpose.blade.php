{{-- Request form block: "What's the event?" (event-type chips + text, 500 chars) and the number of persons.
     Params: $eventTypes (chip labels), $placeholder. Chips and the meter are driven by partials.request-form.script. --}}
<div class="pp-block">
    <p class="pp-title"><span class="pp-title-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg></span><label class="u-m-0" for="purpose">What's the event? <span class="pk-req u-text-danger">*</span></label></p>
    <p class="pp-sub">Tap a type to start, then add the details.</p>
    <div class="pp-chips" role="group" aria-label="Event type">
        @foreach ($eventTypes as $type)
            <button type="button" class="pp-chip" data-type="{{ $type }}" aria-pressed="false">{{ $type }}</button>
        @endforeach
    </div>
    <div class="pp-text">
        <textarea name="purpose" id="purpose" rows="3" required maxlength="500" class="pk-input" placeholder="{{ $placeholder }}" aria-describedby="pp-count" @error('purpose') aria-invalid="true" @enderror>{{ old('purpose') }}</textarea>
        <div class="pp-meter" id="pp-meter" aria-hidden="true">
            <span class="pp-meter-bar"><span id="pp-meter-fill"></span></span>
            <span class="pp-meter-num" id="pp-count">0 / 500</span>
        </div>
    </div>
    @error('purpose')<p class="pr-err">{{ $message }}</p>@enderror
</div>

@include('partials.number-of-persons')
