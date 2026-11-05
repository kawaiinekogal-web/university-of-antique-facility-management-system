{{-- Error summary + inline field errors for a request form. Usage: @include('partials.form-errors', ['form' => 'request-form'])
     Server errors (after a failed submit) and browser checks (on submit) both end up here: one list at the top whose
     items jump to the field, a message under each field (aria-describedby), focus moved to the list, and each entry
     cleared as soon as its field is fixed. Without JS the server list still renders and native validation still runs. --}}
@php
    $serverErrors = collect($errors->getMessages())->map(fn ($messages) => $messages[0])->all();
@endphp
<div class="pk-errors form-errors" id="{{ $form }}-errors" role="alert" tabindex="-1" @if (! $serverErrors) hidden @endif>
    <strong class="form-errors-title">
        @if ($serverErrors)
            Please fix {{ count($serverErrors) === 1 ? 'this problem' : 'these ' . count($serverErrors) . ' problems' }} before sending:
        @endif
    </strong>
    <ul class="form-errors-list">
        @foreach ($serverErrors as $key => $message)
            <li><a href="#" data-error-key="{{ $key }}">{{ $message }}</a></li>
        @endforeach
    </ul>
</div>
<script>
(function () {
    const form = document.getElementById(@json($form));
    const box = document.getElementById(@json($form . '-errors'));
    if (!form || !box) return;
    const title = box.querySelector('.form-errors-title');
    const list = box.querySelector('.form-errors-list');
    const serverErrors = @json($serverErrors);
    form.noValidate = true; // our summary replaces the browser's one-field-at-a-time bubbles

    const fieldFor = key => {
        const [base, ...rest] = key.split('.');
        const bracketed = base + rest.map(part => `[${part}]`).join('');
        return form.querySelector(`[id="${CSS.escape(key)}"]`)
            || form.querySelector(`[name="${CSS.escape(bracketed)}"]`)
            || form.querySelector(`[name="${CSS.escape(base)}[]"]`)
            || form.querySelector(`[name^="${CSS.escape(base)}["]`)
            || form.querySelector(`[id="${CSS.escape(base)}"]`);
    };
    const keyOf = el => (el.name || el.id).replace(/\[\]$/, '').replace(/\[(\w+)\]/g, '.$1');
    const labelOf = el => {
        const label = el.id && form.querySelector(`label[for="${CSS.escape(el.id)}"]`);
        const group = el.closest('[role="radiogroup"]');
        const text = (label && label.textContent) || (group && group.getAttribute('aria-label')) || el.getAttribute('aria-label') || el.name || '';
        return text.replace(/\*|\(optional\)/gi, '').replace(/\s+/g, ' ').trim();
    };
    const messageFor = el => {
        const v = el.validity, label = labelOf(el);
        if (v.valueMissing) {
            if (el.type === 'radio' || el.tagName === 'SELECT') return `Choose ${label.toLowerCase()}.`;
            return label.endsWith('?') ? `Please answer “${label}”` : `${label} is required.`;
        }
        if (v.typeMismatch && el.type === 'email') return 'Enter an email address like name@example.com.';
        if (v.patternMismatch && el.title) return el.title;
        if (v.rangeOverflow) return `${label} can be at most ${Number(el.max).toLocaleString()}.`;
        if (v.rangeUnderflow) return `${label} must be at least ${Number(el.min).toLocaleString()}.`;
        return `${label}: ${el.validationMessage}`;
    };
    // Where a field's message goes; a message the server already printed under the field is reused, not doubled
    const holderFor = el => el.closest('[role="radiogroup"]')?.parentElement || el.closest('.vb-grid')?.parentElement || el.closest('.pk-field') || el.parentElement;

    const clearField = key => {
        form.querySelectorAll(`[data-client-error="${CSS.escape(key)}"]`).forEach(p => p.remove());
        const el = fieldFor(key);
        if (el) form.querySelectorAll(`[name="${CSS.escape(el.name)}"]`).forEach(input => input.removeAttribute('aria-invalid'));
        list.querySelector(`[data-error-key="${CSS.escape(key)}"]`)?.closest('li')?.remove();
        if (!list.children.length) box.hidden = true;
    };
    const showField = (key, message, el) => {
        if (!el) return;
        form.querySelectorAll(`[name="${CSS.escape(el.name)}"]`).forEach(input => input.setAttribute('aria-invalid', 'true'));
        const holder = holderFor(el);
        let note = holder && [...holder.querySelectorAll('.pr-err, .pk-error')].find(p => !p.hidden && p.closest('form') === form);
        if (!note && holder) {
            note = document.createElement('p');
            note.className = 'pr-err';
            note.dataset.clientError = key;
            note.id = `${form.id}-err-${key.replace(/\W+/g, '-')}`;
            note.textContent = message;
            holder.appendChild(note);
        }
        if (note && el.id) {
            note.id ||= `${form.id}-err-${key.replace(/\W+/g, '-')}`;
            const ids = new Set((el.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
            ids.add(note.id);
            el.setAttribute('aria-describedby', [...ids].join(' '));
        }
    };
    const render = entries => {
        list.innerHTML = '';
        entries.forEach(({ key, message }) => {
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = '#';
            link.dataset.errorKey = key;
            link.textContent = message;
            li.appendChild(link);
            list.appendChild(li);
        });
        title.textContent = `Please fix ${entries.length === 1 ? 'this problem' : `these ${entries.length} problems`} before sending:`;
        box.hidden = !entries.length;
    };
    const reveal = () => {
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        box.focus({ preventScroll: true });
    };

    // Each summary item jumps to its field
    list.addEventListener('click', event => {
        const link = event.target.closest('[data-error-key]');
        if (!link) return;
        event.preventDefault();
        const el = fieldFor(link.dataset.errorKey);
        if (!el) return;
        (el.closest('.vb-card, .eq-tile, .pk-field') || el).scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.focus({ preventScroll: true });
    });

    // Browser checks on submit (runs before the form's own submit handlers; stops them when something is wrong)
    form.addEventListener('submit', event => {
        const entries = [], seen = new Set();
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (el.disabled || el.type === 'hidden' || el.validity.valid) return;
            const key = keyOf(el);
            if (seen.has(key)) return;
            seen.add(key);
            entries.push({ key, message: messageFor(el), el });
        });
        const venues = [...form.querySelectorAll('input[name="facility_ids[]"]')];
        if (venues.length && !venues.some(box => box.checked)) {
            entries.push({ key: 'facility_ids', message: 'Select at least one venue.', el: venues.find(box => !box.disabled) });
            const note = document.getElementById('facility-selection-error');
            if (note) note.hidden = false;
        }
        if (!entries.length) return;
        // List problems in the order the fields appear on the page
        entries.sort((a, b) => a.el && b.el && (a.el.compareDocumentPosition(b.el) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
        event.preventDefault();
        event.stopImmediatePropagation();
        form.querySelectorAll('[data-client-error]').forEach(p => p.remove());
        entries.forEach(({ key, message, el }) => showField(key, message, el));
        render(entries);
        reveal();
    }, true);

    // Fixing a field clears its entry
    const onEdit = event => {
        const el = event.target;
        if (!el.name || !el.matches('input, select, textarea')) return;
        const key = keyOf(el);
        if (key === 'facility_ids') {
            if (form.querySelector('input[name="facility_ids[]"]:checked')) clearField(key);
        } else if (el.checkValidity()) {
            clearField(key);
        }
    };
    form.addEventListener('input', onEdit);
    form.addEventListener('change', onEdit);

    // After a failed server submit: mark each field and bring the summary into view
    // (deferred: this script sits at the top of the form, before the fields exist)
    const keys = Object.keys(serverErrors);
    const markServerErrors = () => {
        keys.forEach(key => showField(key, serverErrors[key], fieldFor(key)));
        reveal();
    };
    if (keys.length) document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', markServerErrors) : markServerErrors();
})();
</script>
