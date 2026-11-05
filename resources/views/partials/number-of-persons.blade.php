<div class="pk-field ga-persons">
    <label class="pk-label" for="number_of_persons"><span>Number of persons <span class="pk-req">*</span></span></label>
    <div class="ga-stepper">
        <button type="button" class="ga-step-btn" data-step="-1" aria-label="Fewer persons">&minus;</button>
        <input type="number" name="number_of_persons" id="number_of_persons" required min="1" max="100000" step="1" inputmode="numeric" class="pk-input" value="{{ old('number_of_persons') }}" placeholder="0" aria-describedby="persons-help">
        <button type="button" class="ga-step-btn" data-step="1" aria-label="More persons">+</button>
    </div>
    <p class="pk-help" id="persons-help">Filled in from the maximum capacity of the venues you pick. Lower it to your expected attendance. <strong id="persons-limit"></strong></p>
    @error('number_of_persons')<p class="pr-err">{{ $message }}</p>@enderror
</div>
