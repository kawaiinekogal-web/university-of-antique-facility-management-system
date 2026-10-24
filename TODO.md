# TODO

## Status normalization (legacy "booked" → "reserved")

- [x] Update `BookingService`, queries, validation and views to use `reserved` as the active status instead of `booked`.
- [x] Add one-time migration to convert existing `booked` records to `reserved` in `bookings` and `form_submissions`.
- [x] Add safety check in `AppServiceProvider` to normalize any leftover `booked` rows to `reserved` on boot.
- [x] Ensure auto-completion: `reserved` bookings whose `end_time` has passed are marked `completed`.
- [ ] Re-run smoke tests on:
  - Admin bookings list / calendar / overview.
  - College and org "My Reservations" and calendars.
  - Public calendar.
  - Facilities utilization approvals (set reservation flow).

## Goal: Center welcome page content horizontally

- [x] Inspect current `resources/views/welcome.blade.php` content and styling.

- [ ] Create a fixed-width centered container and apply it consistently to portal + facilities.
- [ ] Replace conflicting/duplicated hero/portal/facilities layout rules.
- [ ] Remove reliance on Tailwind utility classes that conflict with the page CSS.
- [ ] Update hero overlay to center horizontally.
- [ ] Ensure the facilities grid is fully centered using the same container.
- [ ] Write final `welcome.blade.php` with coherent HTML structure + CSS.
- [ ] Run a quick sanity check by searching for obvious layout classes and ensuring no leftover broken markup.

