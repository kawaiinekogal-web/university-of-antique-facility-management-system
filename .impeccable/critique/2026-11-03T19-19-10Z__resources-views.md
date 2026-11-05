---
target: whole UI (resources/views)
total_score: 22
max_score: 40
na_heuristics: 
p0_count: 0
p1_count: 4
target_identity: "file:C:\\last clone\\university-of-antique-facility-management-system\\resources\\views"
timestamp: 2026-11-03T19-19-10Z
slug: resources-views
closed: true
---
# Critique: UA FMS user interface (resources/views), monochrome by client brief

Method: dual-agent (A: design review · B: detector + browser overlay)

## Design Health Score — 22/40 (Acceptable)
| # | Heuristic | Score | Key Issue |
|---|-----------|-------|-----------|
| 1 | Visibility of System Status | 2 | Request #21 is "Reserved" for admin but "Approved / Payment" for the guest |
| 2 | Match System / Real World | 3 | Paper-form masthead is great; admin lists BKG-… codes, guest holds FM-… codes |
| 3 | User Control and Freedom | 2 | 4,000–7,400px single-page form, no draft, Cancel next to Submit |
| 4 | Consistency and Standards | 1 | Guest vs college/org forms diverged; nav labels/order differ; 8 font families, 4 inks |
| 5 | Error Prevention | 3 | Good availability/30-day guards; payment step never shows amount due |
| 6 | Recognition Rather Than Recall | 3 | Venue cards carry rate/capacity/availability; live summary rail |
| 7 | Flexibility and Efficiency | 2 | Presets/±10 only in college/org; duplicate status tabs + select |
| 8 | Aesthetic and Minimalist Design | 2 | Dense microcopy, repeated rules, full-height empty charts, coloured charts |
| 9 | Error Recovery | 2 | Empty submit = native first-field bubble only, no summary |
| 10 | Help and Documentation | 2 | No "what happens next" after submit or receipt upload |

## Design specificity
Public side is authored (Kruhay Bambuhay, real campus photos, GSU-FM-004 masthead, ticket metaphor). Portals are a generic SaaS admin template (dark sidebar, greeting hero, 4 KPI cards, quick-access grid). Two visual systems: homepage #111 + Bricolage vs portals #0f172a + Merriweather/Inter; forms load EB Garamond/Source Sans; CSS also declares DM Sans/DM Serif/Google Sans Flex.
Detector: CLI 3 findings (broken-image FP, overused-font Inter = taste flag, Arial in JS ticket export FP). Browser overlay: / 4, /request-facility 9, /admin/dashboard 13, /college/requests/facilities 45, /org/users 9. Real: low-contrast (#94a3b8 on white 2.6:1, #64748b on #f1f5f9 4.3:1, #7b7f8a on #0f172a 4.46:1), 10.5–11px sidebar labels/step buttons, skipped heading on admin dashboard, 179-char line on /org/users. Intentional/FP: icon-tile-stack, kicker, nested-cards (grouped form sections), overused-font.

## Priority issues
- [P1] Status + ID vocabulary disagree across portals (Reserved vs Approved/Payment; BKG vs FM). Fix: one status model and FM code as primary ID everywhere. /impeccable clarify
- [P1] Payment step hides the amount due; confetti peak fires before payment. Fix: amount due + pay-to row, prefill amount, after-upload expectation; save celebration for Confirmed. /impeccable clarify, /impeccable delight
- [P1] Portal inconsistency (breaks project nav rule): forked request forms, different nav labels/order, page title missing in top bar on list pages, no header on /request-facility, all tabs titled "Laravel". /impeccable polish
- [P1] Contrast + tiny text: #94a3b8 text/placeholders at 2.6:1, 8.5–11px labels, near-invisible input focus ring, unnamed burger/bell buttons, no skip link. Fix: darken muted token to #64748b/#475569, 12px floor, visible focus ring. /impeccable audit
- [P2] "Multi-step" form is one long page; equipment/signatory shown before venue/date; mobile tables overflow at 390px. Fix: one step at a time with sticky step header, progressive disclosure, stacked mobile rows. /impeccable layout, /impeccable adapt
- [P2] Monochrome discipline: admin charts use blue/orange/green/pink/amber; amber=Reserved in donut but Reserved is blue elsewhere; 8 type families. Fix: ink/grey charts, semantic colours only on badges mapped 1:1, 2 families + one ink token. /impeccable typeset

## Persona red flags
Jordan: 3 hero CTAs + 3 login cards + Start request; December calendar with no 30-day explanation; "Noted by" defaults to Others with no guidance; two ID formats.
Sam: tabs titled "Laravel"; no skip link; unnamed burger/bell; focus ring rgba(15,23,42,.12); 8.5–11px #94a3b8 text; unlabeled noted_signatory_custom; dialog labelled only by status line.
Casey: 7,381px guest form on phone; tables cut off at 390px (/admin/forms/facilities, /college/users, /org/users); no page title in phone top bar; 53–59 targets under 32px on forms.

## Minor observations
Donut 0% labels overlap; three "add user" entry points; quick-access grids repeat sidebar; "Dates are open" card misleads when next booking is next month; 6AM–7PM rule repeated; h1 18px on dashboard vs 30px on lists; lone orange system-account row.

## Questions
1. Why does the GSU-FM-004 paper-form identity stop at the guest form instead of shaping the admin review screen?
2. Is the guest's real moment "approved" or "the hall is mine"? Design the ending around the confirmed ticket/gate pass.
3. Does a small GSU office need hero + greeting + 4 KPIs, or one "Needs your action today" queue?
