{{-- Request form step: calendar date picker (one day or several), start/end time, availability check.
     Needs $step and $minActivityDate; driven by partials.request-form.script. --}}
<div class="pk-step">
    <span class="pk-step-num">{{ $step }}</span>
    <div>
        <h2>Schedule</h2>
        <p class="pk-step-intro">When the activity takes place.</p>
        <div class="dp-mode" role="group" aria-label="Event length">
            <button type="button" data-mode="single" aria-pressed="true">One day</button>
            <button type="button" data-mode="multi" aria-pressed="false">Several days</button>
        </div>
        <div class="dp" data-calendar-url="{{ route('calendar.data') }}">
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
                    <li><i class="u-sw-unavailable"></i>Unavailable</li>
                </ul>
                <p class="dp-status" id="dp-status" aria-live="polite"></p>
            </div>

            <div class="dp-side">
                <div class="dp-fields">
                    <div class="dp-dates is-single" id="dp-dates">
                        <div class="pk-field">
                            <label for="date_activity" class="pk-label" id="dp-start-label">Date of Activity <span class="pk-req">*</span></label>
                            <input type="text" name="date_activity" id="date_activity" required inputmode="numeric" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder="YYYY-MM-DD" class="pk-input" min="{{ $minActivityDate }}" value="{{ old('date_activity') }}" @error('date_activity') aria-invalid="true" @enderror>
                        </div>
                        <div class="pk-field dp-end">
                            <label for="date_end" class="pk-label">End date <span class="pk-req">*</span></label>
                            {{-- Only sent for multi-day events (disabled in One day mode) --}}
                            <input type="text" name="date_end" id="date_end" inputmode="numeric" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" placeholder="YYYY-MM-DD" class="pk-input" min="{{ $minActivityDate }}" value="{{ old('date_end') }}" disabled @error('date_end') aria-invalid="true" @enderror>
                        </div>
                    </div>
                    <div class="dp-times">
                        <div class="pk-field">
                            <label for="start_time" class="pk-label">Start Time <span class="pk-req">*</span></label>
                            <input type="time" name="start_time" id="start_time" required min="06:00" max="19:00" class="pk-input" value="{{ old('start_time') }}" @error('start_time') aria-invalid="true" @enderror>
                        </div>
                        <div class="pk-field">
                            <label for="end_time" class="pk-label">End Time <span class="pk-req">*</span></label>
                            <input type="time" name="end_time" id="end_time" required min="06:00" max="19:00" class="pk-input" value="{{ old('end_time') }}" @error('end_time') aria-invalid="true" @enderror>
                        </div>
                    </div>
                </div>
                <div id="time-availability" class="time-availability is-neutral" role="status" aria-live="polite" data-conflict="false">
                    <strong>Availability check</strong>
                    <p>Choose a date, facility and time to check for conflicts.</p>
                </div>
                @foreach (['date_activity', 'date_end', 'start_time', 'end_time'] as $f)
                    @error($f)<p class="pr-err">{{ $message }}</p>@enderror
                @endforeach
                <p class="dp-span" id="dp-span" hidden></p>
                <p class="pk-help" id="dp-help">Pick a day on the calendar or type it. Requests need at least 30 days' notice and must be between 6:00 AM and 7:00 PM.</p>
                <div class="dp-dayinfo" id="dp-dayinfo" aria-live="polite">
                    <h3>On this day</h3>
                    <p>Pick a date to see what's already booked.</p>
                </div>
            </div>
        </div>
    </div>
</div>
