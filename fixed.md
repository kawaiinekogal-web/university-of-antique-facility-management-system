# Fixes and Updates

Summary of the changes made to the UA Facility Management System in this round of work, grouped by area. None of these changes are committed yet.

Test suite: **92 passed, 0 failed**.

---

## 1. Record overview (admin)

Formerly "Monthly records".

| Change | Details |
|---|---|
| Renamed in the sidebar | "Monthly records" is now **Record overview**, and the dashboard link was renamed to match. |
| Monthly, weekly or daily | A **Report** dropdown switches between Monthly (default), Weekly (Monday–Sunday) and Daily. The arrows move by month, week or day. |
| Paper-style Excel download | **Download Excel** produces an `.xlsx` laid out like the GSU paper report: gold period banner, Form No. … Venue, *Facilities to be used (qty.)*, Remarks, a venue summary chart, "Rental of Venues", and Prepared by / Noted by. |
| Paper-style print | **Print** prints the same layout on landscape legal paper. |
| Same format for all periods | Weekly and daily reports use the same Excel and print layout. Their banner reads "WEEKLY REPORT: …" or "DAILY REPORT: …". |
| Remarks column | Lists "Other equipment" items, e.g. "2 Projector screen". |
| Rental fees | Counted for **guest** bookings only. College, org and admin bookings have no fee. |
| Trend chart | Shows the 12 months ending with the period being viewed, and names them. It was always the last 12 months, and repeated or skipped months on the 29th–31st. |
| Occupancy | Measured against GSU operating hours (13 h/day, 6 AM–7 PM) instead of 24-hour days. |
| Labels and years | A week crossing New Year shows both years ("Dec 29, 2025 – Jan 04, 2026"). The Year dropdown always includes the year being viewed. |
| Ledger on screen | Fits at desktop width without cutting off columns. The always-empty Form No. column is hidden on screen but kept in print and Excel. |

New dependency: `phpoffice/phpspreadsheet` (for the Excel file). Run `composer install` when deploying.

---

## 2. Venues: capacity, equipment and rates (GSU rate sheet)

| Change | Details |
|---|---|
| Equipment matches the GSU package | Picking a venue fills in exactly its package from the rate sheet (chairs, tables, fans, sound only where "w/sounds"). Users can still change the quantities. |
| Maximum persons enforced | "Number of persons" is capped at the venue's maximum, or the combined maximum of several venues, both in the form and on the server. Venues with no fixed seating have no limit. |
| Number of persons is saved | The college, org and guest forms showed this field but never saved it; it is now saved. |
| Prices for guests only | Guests see rates and an estimated total; college, org and admin forms show no prices. |
| Name aliases | ICT AVR uses the sheet's **CCS AVR** row, Grand Stand uses **Track Oval**, and Covered Gym uses **Open Space** (₱300/day). |
| TiripUnAn Hall added | Added to `FacilitySeeder` and the local database (capacity 600, ₱8,000). Production still needs it added; see "Before deploying". |
| Classrooms charged per person | "₱50/head in a day" is now rate × persons × days, in the guest estimate, the PDF quote, the tracker and Record overview. |
| Clearer venue cards | Each card shows "Up to 600 persons" and short package tags (*700 chairs · 10 long tables · Air-conditioned*). The exact rate-sheet wording is kept as a tooltip. |

---

## 3. Request forms: all portals

The admin, college, org and guest forms now share one set of parts, so they look and behave the same.

- **Shared parts** (`resources/views/partials/request-form/`): schedule, venue board, purpose and persons, equipment, documents, summary, and one script. The four forms went from about 2,850 lines to about 800.
- **Still different per portal:** only the "Noted by" signatory choice, the guest's applicant details and fee estimate, and the admin requester field.
- **Error summary:** every problem is listed at the top with a link to its field, with a message under each field, in plain wording ("Select at least one venue."). Fixed fields clear on their own.
- **Guest form improvements:** event-type chips, the equipment tiles, the AM/PM half-day display, and Persons and Equipment in the summary.
- **Western Visayas only:** the guest address picker lists only Region VI provinces (Aklan, Antique, Capiz, Guimaras, Iloilo, Negros Occidental). The server enforces this too.
- **Guest pages:** the site's top bar was removed from Guest Access and Request a Facility; both have their own Back button.
- **Phones:**
  - each step number sits beside its heading;
  - the page banner is compact, so the first field appears on the first screen;
  - calendar, stepper and mode buttons have bigger tap targets.
- **Readability:** faint helper text, placeholders and labels now meet WCAG AA contrast (4.5:1).
- **"Long Table":** the table equipment is named "Long Table" everywhere, matching the GSU rate sheet.

---

## 4. Bugs fixed

These were found in the code review (critique items #1–#20).

| # | Problem | Fix |
|---|---|---|
| 1 | Org request form layout broken (duplicate block, stray `</div>`s) | Leftovers removed. |
| 2 | Two guests submitting at once could get each other's ticket and details | The save step now uses the exact request it created. |
| 3 | "Other equipment" entries were thrown away | Saved on every form and shown on bookings, PDFs and reports. |
| 4 | Same element id repeated 12 times on the venue board | One error message, below the grid. |
| 5 | Metro Manila guests couldn't pick an address | Changed as requested: the picker is limited to Western Visayas. |
| 6 | Requests for exactly 30 days ahead could never be approved | The 30 days now count from the day the request was submitted. |
| 7 | College or org accounts with no unit name crashed on submit | The admin forms require the unit name; a missing one shows a clear message instead of an error. |
| 8 | No proper error messages (browser bubbles only) | Shared error summary plus messages under each field. |
| 9 | Helper text too faint to read | Darkened to meet AA contrast. |
| 10 | Trend chart wrong on the 29th–31st | Counts back from the 1st of the month. |
| 11 | Changing venues wiped values the user had typed | The user's own values are kept (persons still capped). |
| 12 | Four request forms had drifted apart | Merged into shared parts. |
| 13 | Unlabelled "Full name" box under "Noted by" | Accessible label added. |
| 14 | Tap targets too small on phones | Enlarged on the request forms. |
| 15 | Weekly report misleading | Trend follows the period, occupancy uses operating hours, ledger fits. |
| 16 | Week label lost its year; limited Year dropdown | Both fixed. |
| 17 | College "Noted by" accepted another college's signatory | Only the user's own college's active signatories are accepted. |
| 18 | Classrooms counted as a flat ₱50 | Charged per person. |
| 19 | Outdated test and unused code | Test rewritten. The old address picker and its data, the unreachable guest portal, and other dead code were removed. |
| 20 | Screen-reader and keyboard gaps | Skip link on every page; one main heading on dashboards. Calendar days were already labelled. |

---

## 5. Tests added

`AddressRegionTest`, `PublicRequestOwnershipTest`, `VenueCapacityTest`, `PerHeadRateTest`, `RecordTrendTest`, `ApprovalNoticeTest`, `AccountUnitTest`, `CollegeSignatoryScopeTest`, plus additions to `OtherEquipmentTest`. `PublicRequestAddressTest` was rewritten.

---

## Before deploying

- Run `composer install` (new package: `phpoffice/phpspreadsheet`). The lock file was generated with `--ignore-platform-req=php`, so the server should run PHP 8.3.
- Add **TiripUnAn Hall** in production, from the admin Facilities page or with `php artisan db:seed --class=FacilitySeeder`. The seeder also marks every facility as active.
- Run `npm run build` so the updated CSS is published.
- If a classroom facility is added later, name it e.g. "Class Rooms" so the ₱50-per-person rate applies.
