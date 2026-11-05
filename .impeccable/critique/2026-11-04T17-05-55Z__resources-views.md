---
target: facility request forms + admin record overview
total_score: 23
max_score: 40
na_heuristics: 
p0_count: 2
p1_count: 7
target_identity: "file:C:\\last clone\\university-of-antique-facility-management-system\\resources\\views"
timestamp: 2026-11-04T17-05-55Z
slug: resources-views
---
# Critique: facility request forms (public, college, org, admin direct) + admin Record overview
Method: dual-agent (A: design review · B: detector + browser) + code-error audit (C)

## Heuristics (23/40, Acceptable)
1 Status 3 · 2 Real world 3 · 3 Control 2 · 4 Consistency 1 · 5 Error prevention 3 · 6 Recognition 3 · 7 Efficiency 3 · 8 Minimalism 2 · 9 Error recovery 1 · 10 Help 2

## Numbered error list
1. [P0] Org request form: duplicate "Other equipment" block + stray closing divs (org/requests/facilities_create.blade.php:319-325) break the layout; summary drops to page bottom.
2. [P0/High] Walk-in guests can receive each other's ticket/details (PublicFacilityRequestController.php:78-88, max(id) race).
3. [P1/High] "Other equipment" rows dropped on save; equipment_other text missing from bookings/PDFs/Excel.
4. [P1] Duplicate id facility-selection-error x12 (college:209, org:196, guest:233).
5. [P1] Metro Manila / NCR guests cannot pick a province/city -> cannot submit (AddressLookupController).
6. [P1] Requests exactly 30 days ahead can never be approved (BookingService.php:51).
7. [P1] College/org accounts without unit name get 500 on submit (requesterUnit returns null).
8. [P1] No error-recovery pattern: native bubbles only, no summary/inline errors.
9. [P1] Helper text contrast 2.6:1 (#94a3b8), zero counts 1.5:1 (#cbd5e1).
10. [P2] 12-month trend wrong on days 29-31 (subMonths overflow, MonthlyRecordsController:68,80).
11. [P2] Venue picker overwrites user-typed equipment/persons on every toggle.
12. [P2] Four divergent request forms (purpose/signatory/equipment differ per portal).
13. [P2] Unlabeled noted_signatory_custom input on public form (:334).
14. [P2] Touch targets under 44px on mobile (calendar nav 28px, ±10 steppers 22px tall).
15. [P2] Weekly report: trend not anchored to period; ledger columns clipped; utilization vs 24h days.
16. [P3] Weekly label drops start year across New Year; year dropdown 2024-2030 vs resolver 2000-2100.
17. [P3] College "Noted by" accepts another college's signatory id.
18. [P3] Classroom per-head rate counted as flat PhP50 in records.
19. [P3] Stale PublicRequestAddressTest (old field names) + dead code (address-picker partial, guest create view).
20. [P3] Calendar days lack aria-label/pressed; no skip link; two h1 on dashboard.
