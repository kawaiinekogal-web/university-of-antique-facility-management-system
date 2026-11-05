{{-- Shared behaviour for the Facilities Utilization Request forms (public guest, college, org):
     +/- steppers, double-submit guard, purpose chips + meter, equipment tiles, file names, calendar date picker,
     venue availability board and the live summary. Param: $formId. Portal-specific bits (signatory, guest fees)
     stay in each page. Include after the form, then partials.venue-equipment-suggest / facility-time-availability. --}}
<script>
function adjustQty(fieldId, delta) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    let value = parseInt(input.value || '0', 10);
    value += delta;
    if (value < 0) value = 0;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

// Prevent double submission
(function() {
    // Target the request form by id (the sidebar's logout form comes first in the page)
    const form = document.getElementById(@json($formId));
    const submitBtn = document.getElementById('submitBtn');

    if (form && submitBtn) {
        const label = submitBtn.querySelector('span');
        const idleLabel = label.textContent;
        form.addEventListener('submit', function(e) {
            // Disable button and show loading state
            submitBtn.disabled = true;
            label.textContent = 'Submitting...';
            submitBtn.style.opacity = '.6';
            submitBtn.style.cursor = 'not-allowed';

            // Re-enable after 5 seconds as fallback (in case of validation errors)
            setTimeout(function() {
                submitBtn.disabled = false;
                label.textContent = idleLabel;
                submitBtn.style.opacity = '';
                submitBtn.style.cursor = '';
            }, 5000);
        });
    }

    const notedRadios = document.querySelectorAll('input[name="noted_signatory_id"]');

    // Purpose: event-type chips + character meter (server limit is 500)
    (function () {
        const area = document.getElementById('purpose');
        if (!area) return;
        const chips = Array.from(document.querySelectorAll('.pp-chip'));
        const types = chips.map(c => c.dataset.type);
        const sep = ' — ';
        function currentType() { const t = types.find(t => area.value.startsWith(t + sep) || area.value === t); return t || ''; }
        function sync() {
            const len = area.value.length;
            document.getElementById('pp-count').textContent = len + ' / 500';
            document.getElementById('pp-meter-fill').style.width = Math.min(100, len / 5) + '%';
            document.getElementById('pp-meter').classList.toggle('is-near', len >= 450);
            const t = currentType();
            chips.forEach(c => c.setAttribute('aria-pressed', c.dataset.type === t ? 'true' : 'false'));
        }
        chips.forEach(chip => chip.addEventListener('click', () => {
            const old = currentType();
            const rest = old ? area.value.slice(Math.min(area.value.length, old.length + sep.length)) : area.value;
            area.value = (chip.dataset.type === old ? '' : chip.dataset.type + sep) + rest.replace(/^\s+/, '');
            area.dispatchEvent(new Event('input', { bubbles: true }));
            area.focus();
            area.setSelectionRange(area.value.length, area.value.length);
        }));
        area.addEventListener('input', sync);
        sync();
    })();

    // Equipment: highlight tiles in use, running total, quick-start sets
    (function () {
        const tiles = Array.from(document.querySelectorAll('.eq-tile'));
        if (!tiles.length) return;
        function sync() {
            let pieces = 0, kinds = 0;
            tiles.forEach(tile => {
                const input = tile.querySelector('.eq-count');
                let v = parseInt(input.value || '0', 10);
                if (isNaN(v) || v < 0) { v = 0; }
                tile.classList.toggle('is-on', v > 0);
                pieces += v; if (v > 0) kinds++;
            });
            document.getElementById('eq-total').textContent = pieces;
            document.getElementById('eq-total-text').textContent = pieces
                ? (pieces === 1 ? 'item' : 'items') + ' · ' + kinds + ' ' + (kinds === 1 ? 'type' : 'types')
                : 'items selected';
        }
        tiles.forEach(t => t.querySelector('.eq-count').addEventListener('input', sync));
        document.querySelectorAll('.eq-quick [data-preset]').forEach(btn => btn.addEventListener('click', () => {
            const preset = JSON.parse(btn.dataset.preset || '{}');
            tiles.forEach(tile => {
                const input = tile.querySelector('.eq-count');
                input.value = preset[tile.dataset.field] || 0;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }));
        sync();
    })();

    // Show chosen file names inside the drop zones
    document.querySelectorAll('.pk-drop input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const sub = input.closest('.pk-drop').querySelector('.pk-drop-sub');
            const names = Array.from(input.files).map(f => f.name);
            sub.textContent = names.length ? names.join(', ') : input.dataset.empty;
        });
    });

    // ---- Date picker: one day, or a start → end range for multi-day events ----
    (function () {
        const input = document.getElementById('date_activity');
        const endInput = document.getElementById('date_end');
        const grid = document.getElementById('dp-grid');
        if (!input || !grid) return;
        const minDate = input.min;                      // today + 30 days (server rule)
        const MAX_DAYS = 14;                            // server rule for date_end
        const todayKey = new Date().toLocaleDateString('en-CA');
        const calendarUrl = @json(route('calendar.data'));
        const cache = {};                               // 'YYYY-MM' -> { counts: {date: n}, items: {date: [{time, facility}]} }
        const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const pad = (n) => String(n).padStart(2, '0');
        const keyOf = (y, m, d) => y + '-' + pad(m + 1) + '-' + pad(d);
        const addDays = (key, n) => { const d = new Date(key + 'T00:00'); d.setDate(d.getDate() + n); return d.toLocaleDateString('en-CA'); };
        const daysBetween = (a, b) => Math.round((new Date(b + 'T00:00') - new Date(a + 'T00:00')) / 864e5);
        const fmt = (key, opts) => new Date(key + 'T00:00').toLocaleDateString(undefined, opts || { weekday: 'short', month: 'short', day: 'numeric' });
        const esc = (t) => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // Whole day = 6+ hours; anything shorter is a half day (morning if it ends by 1 PM, afternoon if it starts at noon or later)
        const mins = (hm) => { if (!hm) return null; const [h, m] = hm.split(':').map(Number); return h * 60 + m; };
        const clock12 = (hm) => { const [h, m] = hm.split(':').map(Number); return ((h % 12) || 12) + ':' + pad(m) + (h < 12 ? ' AM' : ' PM'); };
        function describe(it) {
            const a = mins(it.start), b = mins(it.end);
            if (a !== null && b !== null && b > a) {
                const len = b - a, hrs = Math.round(len / 30) / 2;
                it.hours = hrs + (hrs === 1 ? ' hr' : ' hrs');
                it.until = clock12(it.end);
                // Morning (AM) = starts before 12 PM; afternoon (PM) = runs past 1 PM (12–1 PM is slack). Both halves = whole day.
                it.am = a < 720;
                it.pm = b > 780 || !it.am;
                it.whole = it.am && it.pm;
                it.span = it.whole ? 'Whole day' : (it.am ? 'Half day · Morning' : 'Half day · Afternoon');
            }
            return it;
        }
        const clockIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
        // One booking: time + Booked, facility (panel only), event, who, whole/half day + until when
        const personIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg>';
        window.dpBookingHtml = function (it, opts) {
            opts = opts || {};
            let h = '<span class="bk">'
                + '<span class="bk-row"><span class="bk-time">' + clockIcon + (opts.date ? '<b>' + esc(opts.date) + '</b>&nbsp;·&nbsp;' : '') + esc(it.time || 'Time not set') + '</span>'
                + '<span class="bk-tag is-booked">Booked</span></span>';
            if (opts.facility) h += '<span class="bk-facility">' + esc(it.facility) + '</span>';
            if (it.event) h += '<span class="bk-event">' + esc(it.event) + '</span>';
            if (it.who) h += '<span class="bk-who">' + personIcon + esc(it.who) + '</span>';
            if (it.span || it.until) {
                h += '<span class="bk-row bk-foot">'
                    + (it.span ? '<span class="bk-tag ' + (it.whole ? 'is-whole' : 'is-half') + '">' + esc(it.span) + '</span>' : '<span></span>')
                    + (it.until ? '<span class="bk-until">Until <strong>' + esc(it.until) + '</strong> · ' + esc(it.hours) + '</span>' : '')
                    + '</span>';
            }
            return h + '</span>';
        };

        let mode = endInput.value ? 'multi' : 'single';
        let view = (input.value || minDate).slice(0, 7).split('-').map(Number);
        view = { y: view[0], m: view[1] - 1 };
        let hoverKey = '';

        const startKey = () => input.value;
        const endKey = () => (mode === 'multi' && endInput.value) ? endInput.value : '';
        const rangeDays = () => {
            const s = startKey(); if (!s) return [];
            const e = endKey() || s; const out = [];
            for (let k = s; k <= e && out.length < MAX_DAYS; k = addDays(k, 1)) out.push(k);
            return out;
        };

        function setValues(start, end) {
            input.value = start || '';
            endInput.value = end || '';
            [input, endInput].forEach(el => { el.dispatchEvent(new Event('input', { bubbles: true })); });
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Booked days come from the shared calendar data endpoint (same data the Reservation calendar shows)
        function loadMonth(y, m) {
            const ym = y + '-' + pad(m + 1);
            if (cache[ym]) return Promise.resolve(cache[ym]);
            return window.loadReservationCalendarMonth(calendarUrl, ym)
                .then(data => {
                    Object.keys(data.items).forEach(date => {
                        data.items[date] = data.items[date].map(describe);
                    });
                    return (cache[ym] = data);
                });
        }
        const loadKey = (key) => { const [yy, mm] = key.split('-').map(Number); return loadMonth(yy, mm - 1).catch(() => null); };

        function setMode(next) {
            mode = next;
            document.querySelectorAll('.dp-mode button').forEach(b => b.setAttribute('aria-pressed', b.dataset.mode === mode ? 'true' : 'false'));
            document.getElementById('dp-dates').classList.toggle('is-single', mode === 'single');
            endInput.disabled = mode === 'single';
            endInput.required = mode === 'multi';
            document.getElementById('dp-start-label').innerHTML = (mode === 'multi' ? 'Start date' : 'Date of Activity') + ' <span class="pk-req">*</span>';
            document.getElementById('dp-help').textContent = mode === 'multi'
                ? 'Click the first and last day (up to ' + MAX_DAYS + ' days). The times apply to every day.'
                : 'Pick a day on the calendar or type it. Requests need at least 30 days\' notice.';
            grid.setAttribute('aria-label', mode === 'multi' ? 'Choose the first and last day of the event' : 'Choose the date of activity');
            if (mode === 'single') setValues(startKey(), '');
            else { paintGrid(); announce(); }
        }

        function render() {
            const { y, m } = view;
            document.getElementById('dp-month').textContent = monthNames[m] + ' ' + y;
            document.getElementById('dp-prev').disabled = (y + '-' + pad(m + 1)) <= minDate.slice(0, 7);
            grid.classList.add('is-loading');
            document.getElementById('dp-status').textContent = 'Loading booked dates…';
            loadMonth(y, m).then(data => draw(data), () => {
                draw({ counts: {}, items: {} });
                document.getElementById('dp-status').textContent = "Couldn't load booked dates. You can still pick a date.";
            });
        }

        function draw(data) {
            const { y, m } = view;
            const first = new Date(y, m, 1).getDay();
            const days = new Date(y, m + 1, 0).getDate();
            grid.innerHTML = '';
            for (let i = 0; i < first; i++) {
                const blank = document.createElement('span'); blank.className = 'dp-day is-out'; grid.append(blank);
            }
            for (let d = 1; d <= days; d++) {
                const key = keyOf(y, m, d);
                const count = data.counts[key] || 0;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'dp-day' + (count ? ' has-events' : '') + (key === todayKey ? ' is-today' : '');
                btn.dataset.date = key;
                btn.dataset.count = count;
                btn.disabled = key < minDate;
                btn.innerHTML = d + (count ? '<span class="dp-count">' + count + '</span>' : '');
                if (count) btn.title = (data.items[key] || []).map(it => it.facility + ' · ' + it.time + (it.span ? ' (' + it.span + ')' : '') + (it.event ? ' — ' + it.event : '')).join('\n') || count + ' reservation(s)';
                btn.addEventListener('click', () => pick(key));
                btn.addEventListener('mouseenter', () => { if (mode === 'multi' && startKey() && !endKey()) { hoverKey = key; paintGrid(); } });
                grid.append(btn);
            }
            grid.onmouseleave = () => { if (hoverKey) { hoverKey = ''; paintGrid(); } };
            grid.classList.remove('is-loading');
            const booked = Object.keys(data.counts).length;
            document.getElementById('dp-status').textContent = booked ? booked + ' day' + (booked > 1 ? 's' : '') + ' this month already have reservations.' : 'No reservations yet this month.';
            paintGrid();
            announce();
        }

        // Selected / in-range / hover-preview styling + accessible labels
        function paintGrid() {
            const s = startKey(), e = endKey();
            const previewEnd = (mode === 'multi' && s && !e && hoverKey > s && daysBetween(s, hoverKey) < MAX_DAYS) ? hoverKey : '';
            grid.querySelectorAll('.dp-day[data-date]').forEach(btn => {
                const k = btn.dataset.date, count = +btn.dataset.count;
                const isEdge = k === s || (e && k === e);
                const inRange = e && k > s && k < e;
                const preview = previewEnd && k > s && k <= previewEnd;
                btn.classList.toggle('is-selected', !!isEdge);
                btn.classList.toggle('is-inrange', !!inRange);
                btn.classList.toggle('is-preview', !!preview && !isEdge);
                btn.setAttribute('aria-pressed', (isEdge || inRange) ? 'true' : 'false');
                btn.setAttribute('aria-label', fmt(k, { weekday: 'long', month: 'long', day: 'numeric' })
                    + (count ? ', ' + count + ' reservation' + (count > 1 ? 's' : '') : ', no reservations')
                    + (btn.disabled ? ', not available' : '') + (k === s && e ? ', first day' : '') + (k === e ? ', last day' : ''));
            });
        }

        function pick(key) {
            const s = startKey(), e = endKey();
            const status = document.getElementById('dp-status');
            if (mode === 'single') return setValues(key, '');
            if (!s || e || key < s) return setValues(key, '');          // start a new range
            if (key === s) return setValues(key, '');                    // same day twice = one day
            if (daysBetween(s, key) >= MAX_DAYS) {                       // too long
                status.textContent = 'A multi-day event can last at most ' + MAX_DAYS + ' days — pick an earlier last day.';
                return;
            }
            hoverKey = '';
            setValues(s, key);
        }

        // Tell the "On these days" panel, venue board and summary which days are chosen
        function announce() {
            const days = rangeDays();
            const span = document.getElementById('dp-span');
            if (mode === 'multi' && days.length > 1) {
                span.hidden = false;
                span.textContent = fmt(days[0]) + ' → ' + fmt(days[days.length - 1]) + ' · ' + days.length + ' days';
            } else if (mode === 'multi' && startKey()) {
                span.hidden = false;
                span.textContent = 'Start: ' + fmt(startKey()) + ' — now click the last day';
            } else {
                span.hidden = true;
            }
            Promise.all([...new Set(days.map(k => k.slice(0, 7)))].map(ym => loadKey(ym + '-01'))).then(() => {
                const perDay = days.map(k => ({ date: k, items: (cache[k.slice(0, 7)] || { items: {} }).items[k] || [], count: (cache[k.slice(0, 7)] || { counts: {} }).counts[k] || 0 }));
                showDays(perDay);
                document.dispatchEvent(new CustomEvent('dp:range', { detail: { start: days[0] || '', end: days[days.length - 1] || '', days: perDay } }));
            });
        }

        // "On this day" / "On these days": what's already booked, flagging the venue you picked
        function showDays(perDay) {
            const box = document.getElementById('dp-dayinfo');
            if (!perDay.length) { box.innerHTML = '<h3>On this day</h3><p>Pick a date to see what\'s already booked.</p>'; return; }
            const venues = Array.from(document.querySelectorAll('input[name="facility_ids[]"]:checked')).map(input => input.dataset.name);
            const multi = perDay.length > 1;
            let html = '<h3>' + (multi ? 'On these days' : fmt(perDay[0].date, { weekday: 'short', month: 'long', day: 'numeric' })) + '</h3>';
            const busy = perDay.filter(d => d.count || d.items.length);
            if (!busy.length) {
                html += '<p class="dp-free"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>No reservations yet — ' + (multi ? 'all ' + perDay.length + ' days are open.' : 'this date is open.') + '</p>';
            } else {
                let clashDays = 0;
                busy.forEach(d => {
                    if (multi) html += '<span class="dp-date-head">' + esc(fmt(d.date)) + '</span>';
                    const list = d.items.length ? d.items : [{ time: '', facility: d.count + ' reservation(s)' }];
                    html += '<ul>' + list.map(it => {
                        const bookedFacilities = it.facility.split(',').map(name => name.trim().toUpperCase());
                        const clash = venues.some(venue => bookedFacilities.includes(venue.toUpperCase()));
                        if (clash) clashDays++;
                        return '<li class="bk-item' + (clash ? ' is-clash' : '') + '">' + window.dpBookingHtml(it, { facility: true }) + '</li>';
                    }).join('') + '</ul>';
                });
                if (multi) html += '<p class="u-mt-8">' + (perDay.length - busy.length) + ' of ' + perDay.length + ' days have no reservations.</p>';
                if (clashDays) html += '<p class="dp-warn">A selected facility has an existing reservation in this schedule. GSU will review availability before approval.</p>';
            }
            box.innerHTML = html;
        }

        document.querySelectorAll('.dp-mode button').forEach(b => b.addEventListener('click', () => { if (b.dataset.mode !== mode) setMode(b.dataset.mode); }));
        document.getElementById('dp-prev').addEventListener('click', () => { view.m--; if (view.m < 0) { view.m = 11; view.y--; } render(); });
        document.getElementById('dp-next').addEventListener('click', () => { view.m++; if (view.m > 11) { view.m = 0; view.y++; } render(); });
        // Typing a date moves the calendar to it
        function onTyped() {
            if (endInput.value && input.value && endInput.value < input.value) endInput.value = '';
            const key = input.value;
            if (key) {
                const [yy, mm] = key.split('-').map(Number);
                if (yy !== view.y || mm - 1 !== view.m) { view = { y: yy, m: mm - 1 }; render(); return; }
            }
            paintGrid(); announce();
        }
        input.addEventListener('change', onTyped);
        endInput.addEventListener('change', onTyped);
        document.querySelectorAll('input[name="facility_ids[]"]').forEach(input => input.addEventListener('change', announce));

        // Restore "Several days" after a validation error that kept old('date_end')
        if (mode === 'multi') setMode('multi');
        render();
    })();

    // ---- Venue availability board (display only) ----
    (function () {
        const cards = Array.from(document.querySelectorAll('.vb-card'));
        if (!cards.length) return;
        const esc = (t) => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const clock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
        const shortDate = (k) => new Date(k + 'T00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        let current = { start: '', end: '', days: [] };

        // "8:00 AM – 12:00 PM" -> minutes since midnight
        const toMin = (t) => { const m = /(\d{1,2}):(\d{2})\s*(AM|PM)/i.exec(t || ''); if (!m) return null; let h = +m[1] % 12; if (/pm/i.test(m[3])) h += 12; return h * 60 + +m[2]; };
        const rangeOf = (txt) => { const parts = String(txt).split(/[–-]/); return [toMin(parts[0]), toMin(parts[1])]; };
        const hhmm = (v) => { if (!v) return null; const [h, m] = v.split(':').map(Number); return h * 60 + m; };

        function paint() {
            const start = hhmm(document.getElementById('start_time').value);
            const end = hhmm(document.getElementById('end_time').value);
            const text = document.getElementById('vb-day-text');
            const pill = document.getElementById('vb-day');
            const multi = current.days.length > 1;
            if (current.start) {
                text.textContent = multi
                    ? 'Availability ' + shortDate(current.start) + ' → ' + shortDate(current.end) + ', ' + current.end.slice(0, 4) + ' (' + current.days.length + ' days)'
                    : 'Availability on ' + new Date(current.start + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
                pill.classList.add('is-set');
            } else {
                text.textContent = text.dataset.idle;
                pill.classList.remove('is-set');
            }
            cards.forEach(card => {
                const name = card.dataset.name.toUpperCase();
                const input = card.querySelector('input');
                const slotsBox = card.querySelector('.vb-slots');
                const status = card.querySelector('.vb-status');
                // A booking row can list several facilities ("A, B"), so match on each name
                const mine = [];
                current.days.forEach(d => d.items.forEach(it => {
                    if (it.facility.split(',').map(x => x.trim().toUpperCase()).includes(name)) mine.push(Object.assign({ date: d.date }, it));
                }));
                const bookedDays = new Set(mine.map(x => x.date)).size;
                card.classList.toggle('is-booked', mine.length > 0);
                card.querySelector('.vb-count-num').textContent = current.start ? mine.length : '–';
                if (input.disabled) { card.classList.remove('is-clash'); return; }
                if (!current.start) {
                    slotsBox.innerHTML = '<span class="vb-idle">Pick a date to see times</span>';
                } else if (!mine.length) {
                    slotsBox.innerHTML = '<span class="vb-free">' + (multi ? 'Free all ' + current.days.length + ' days' : 'Free all day') + '</span>';
                } else {
                    slotsBox.innerHTML = mine.map(it => window.dpBookingHtml(it, { date: multi ? shortDate(it.date) : '' })).join('');
                }
                // Per day: AM and PM both taken = fully booked; only one half taken = partly booked
                const halves = (list) => ({ am: list.some(it => it.am), pm: list.some(it => it.pm) });
                const fullDays = current.days.filter(d => { const h = halves(mine.filter(it => it.date === d.date)); return h.am && h.pm; }).length;
                const day = halves(mine);
                let label, tone;
                if (!current.start || !mine.length) { label = 'Available'; tone = 'is-good'; }
                else if (multi) {
                    label = (fullDays ? 'Fully booked ' + fullDays : 'Partly booked ' + bookedDays) + ' of ' + current.days.length + ' days';
                    tone = fullDays ? 'is-bad' : 'is-warn';
                }
                else if (day.am && day.pm) { label = 'Fully booked · whole day'; tone = 'is-bad'; }
                else { label = day.am ? 'Partly booked · PM free' : 'Partly booked · AM free'; tone = 'is-warn'; }
                status.textContent = label;
                status.className = 'pk-pill vb-status ' + tone;
                // Flag venues whose bookings overlap the start/end time on any chosen day
                const clash = start !== null && end !== null && mine.some(it => { const [a, b] = rangeOf(it.time); return a !== null && b !== null && a < end && b > start; });
                card.classList.toggle('is-clash', clash);
            });
        }
        document.addEventListener('dp:range', (e) => { current = { start: e.detail.start || '', end: e.detail.end || '', days: e.detail.days || [] }; paint(); });
        ['start_time', 'end_time'].forEach(id => document.getElementById(id).addEventListener('input', paint));
        paint();
    })();

    // Live summary (display only)
    if (!form) return;
    const $ = (id) => document.getElementById(id);
    const fmtTime = (v) => {
        if (!v) return '';
        const [h, m] = v.split(':').map(Number);
        return ((h % 12) || 12) + ':' + String(m).padStart(2, '0') + (h < 12 ? ' AM' : ' PM');
    };
    function setText(el, text, empty) { if (!el) return; el.textContent = text || empty; el.classList.toggle('is-empty', !text); }
    function render() {
        const d = $('date_activity').value;
        const de = $('date_end').disabled ? '' : $('date_end').value;
        const long = (k) => new Date(k + 'T00:00').toLocaleDateString(undefined, { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
        const short = (k) => new Date(k + 'T00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        const n = d && de ? Math.round((new Date(de + 'T00:00') - new Date(d + 'T00:00')) / 864e5) + 1 : 1;
        $('sum-date').textContent = !d ? 'Pick a date' : (de && n > 1 ? short(d) + ' → ' + short(de) + ' (' + n + ' days)' : long(d));
        const s = fmtTime($('start_time').value), e = fmtTime($('end_time').value);
        $('sum-time').textContent = s || e ? (s || '…') + ' – ' + (e || '…') : 'Start – end time';
        const venues = Array.from(form.querySelectorAll('input[name="facility_ids[]"]:checked')).map(input => input.dataset.name);
        setText($('sum-venue'), venues.join(', '), 'None selected');
        const purpose = $('purpose').value.trim();
        setText($('sum-purpose'), purpose.length > 90 ? purpose.slice(0, 90) + '…' : purpose, '—');
        setText($('sum-persons'), $('number_of_persons')?.value, '—');
        const equip = Array.from(form.querySelectorAll('.eq-count'))
            .filter(i => parseInt(i.value || '0', 10) > 0 && i.dataset.label)
            .map(i => i.value + ' × ' + i.dataset.label);
        setText($('sum-equip'), equip.join(', '), 'None');
    }
    form.addEventListener('input', render);
    form.addEventListener('change', render);
    const venueBoxes = Array.from(form.querySelectorAll('input[name="facility_ids[]"]'));
    const selectionError = document.getElementById('facility-selection-error');
    venueBoxes.forEach(input => input.addEventListener('change', () => {
        if (selectionError && venueBoxes.some(box => box.checked)) selectionError.hidden = true;
    }));
    // An empty venue choice on submit is reported by partials.form-errors
    render();
})();
</script>
