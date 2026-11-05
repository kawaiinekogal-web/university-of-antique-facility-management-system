{{-- Needs partials.number-of-persons, qty_* equipment inputs, and venue checkboxes (facility_ids[]) carrying
     data-capacity / data-equipment from App\Support\VenueRates. --}}
<script>
// Picking venues fills in the equipment their GSU package provides (chairs, tables, fans, sound) and their
// combined maximum capacity. Persons are capped at that maximum (also enforced server-side by WithinVenueCapacity);
// a venue with no fixed seating (capacity 0) lifts the cap. Any value the user sets (typing, +/- steppers, presets)
// is theirs: later venue changes keep it, only capping persons at the new maximum. Clearing a field (empty/0)
// hands it back to the suggestions.
(function () {
    const persons = document.getElementById('number_of_persons');
    if (!persons) return;
    const limitNote = document.getElementById('persons-limit');
    const venueBoxes = Array.from(document.querySelectorAll('input[name="facility_ids[]"]'));
    const fields = ['qty_monobloc', 'qty_table', 'qty_fan', 'qty_sound', 'number_of_persons']
        .map(id => document.getElementById(id)).filter(Boolean);
    const userValue = {}; // field id -> number the user chose
    let applying = false; // true while this script writes, so its own writes are not taken as user edits
    const write = (input, value) => {
        applying = true;
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        applying = false;
    };
    const remember = input => {
        let n = parseInt(input.value, 10) || 0;
        if (input === persons && n > Number(persons.max)) n = Number(persons.max); // more than fits: remember the most
        if (n > 0) userValue[input.id] = n; else delete userValue[input.id];
    };
    fields.forEach(input => input.addEventListener('input', () => { if (!applying) remember(input); }));
    const maxPersons = () => {
        const venues = venueBoxes.filter(venue => venue.checked);
        const capacities = venues.map(venue => parseInt(venue.dataset.capacity, 10) || 0);
        return venues.length && capacities.every(c => c > 0) ? capacities.reduce((a, b) => a + b, 0) : null;
    };
    const applyLimit = () => {
        const max = maxPersons();
        persons.max = max ?? 100000;
        const wanted = userValue[persons.id] ?? (parseInt(persons.value, 10) || 0);
        const capped = max ? Math.min(wanted, max) : wanted;
        if (wanted && String(capped) !== persons.value) write(persons, capped);
        if (limitNote) limitNote.textContent = max ? `Maximum of ${max.toLocaleString()} persons for the selected venue(s).` : '';
        return max;
    };
    venueBoxes.forEach(box => box.addEventListener('change', function () {
        const equipment = { qty_monobloc: 0, qty_table: 0, qty_fan: 0, qty_sound: 0 };
        venueBoxes.filter(venue => venue.checked).forEach(venue => {
            const items = JSON.parse(venue.dataset.equipment || '{}');
            Object.keys(equipment).forEach(key => { equipment[key] += items[key] || 0; });
        });
        Object.entries(equipment).forEach(([id, qty]) => {
            const input = document.getElementById(id);
            if (input && !(id in userValue)) write(input, qty);
        });
        if (!(persons.id in userValue)) write(persons, maxPersons() ?? '');
        applyLimit();
    }));
    document.querySelectorAll('.ga-step-btn').forEach(btn => btn.addEventListener('click', function () {
        persons.value = Math.min(Number(persons.max) || 100000, Math.max(1, (parseInt(persons.value, 10) || 0) + Number(btn.dataset.step)));
        persons.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    persons.addEventListener('change', applyLimit);
    applyLimit(); // venues re-checked from old input after a validation error
    fields.forEach(remember); // values re-filled after a validation error are the user's own
})();
</script>
