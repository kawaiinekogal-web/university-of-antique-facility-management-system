{{-- Request form step: optional supporting files. Needs $step. --}}
<div class="pk-step">
    <span class="pk-step-num">{{ $step }}</span>
    <div>
        <h2>Documents <span class="u-optional">(optional)</span></h2>
        <p class="pk-step-intro">Supporting files for your request.</p>
        <div class="nreq-uploads is-single">
            <div>
                <label class="pk-drop" title="Select up to 10 JPG, PNG, or PDF files, max 10MB each">
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf" data-empty="JPG, PNG, or PDF, max 10 files, 10MB each">
                    <span class="pk-drop-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.4 11.1l-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg></span>
                    <span><strong>Attachments</strong><span class="pk-drop-sub">JPG, PNG, or PDF; max 10 files, 10MB each</span></span>
                </label>
                <p class="nreq-note">Upload supporting documents.</p>
            </div>
        </div>
    </div>
</div>
