# Test user accounts

Role-based login accounts for the current web app are now seeded by `database/seeders/SampleUsersSeeder.php`. The legacy desktop launcher flow used helper routes for quick test account creation, but those helper routes are development-only and should not be used in production.

## Summary

| Source | What it creates | Used for portal login? |
|--------|-----------------|------------------------|
| `DatabaseSeeder` | Sample admin, college, org users A/B/C + generic test user + facilities | Yes (admins/college/org staff) |
| Helper routes in `routes/web.php` | Legacy, development-only account creation | Yes (legacy only) |

For standard web deployment, use seeded users instead of the legacy `/make-*` helper routes.

Legacy data note: if your database predates the 2026-06 status normalization, any `booked` rows in `bookings` / `form_submissions` are automatically converted to `reserved` by a one‑time migration and a safety check in `AppServiceProvider`. Active reservations are now tracked under the `reserved` status; past reservations with `end_time` in the past are auto‑marked `completed`.

---

## 1. Seeder-created sample users

`database/seeders/DatabaseSeeder.php` runs when you execute:

```bash
php artisan db:seed
```

It now creates the following accounts:

### Admins
- `adminA@example.com` / `password123`
- `adminB@example.com` / `password123`
- `adminC@example.com` / `password123`

### College staff
- `college@ccis.com` / `password123` → `college_name = "CCIS"`
- `college@cea.com` / `password123` → `college_name = "CEA"`
- `college@cmg.com` / `password123` → `college_name = "CMG"`

### Org staff
- `orgA@example.com` / `password123` → `organization_name = "Org A"`
- `orgB@example.com` / `password123` → `organization_name = "Org B"`
- `orgC@example.com` / `password123` → `organization_name = "Org C"`

It also still creates one generic test user via `User::factory()` and the official facilities list via `FacilitySeeder`.

> **Note (2026-10-19):** most of these sample accounts were deleted from the local database through Admin → Users before account deactivation was implemented. Admin A is still there but its email was changed to `adminA@gsuadmin.com` (see 1b). Those earlier deletions also removed their linked bookings, requests and repair tickets; that historical data cannot be restored by seeding. Account removal now deactivates accounts and preserves records. Run `php artisan db:seed` to bring back missing sample accounts.

---

## 1b. Accounts created in the app (local database)

Created or edited through the portals, not by the seeder. Each portal only accepts its own accounts: use the matching card on the home page (Admin / College Staff / Org Unit). Last checked against the local database on 2026-10-19.

### Admins (Admin team)
- `adminA@gsuadmin.com` / `password123` → AdminA, position `GSU Head`, contact `0990-990-9090` *(seeded as `adminA@example.com`; email changed in the app)*
- `garcia@gsustaff.com` / `password123` → Garcia, position `Staff`, contact `0909-999-9099`

### College staff
- `jcamar@ccisdean.com` / `password123` → John C. Amar, `college_name = "CCIS Dean"`, contact `0909-090-9090`

### Org staff
- `geoyanga@ccisprovincial.com` / `ccis2026` → Geo Yanga, `organization_name = "CCIS Provincial"`, contact `0909-991-3450` *(password was set by whoever created it; it is not `password123`)*

### System account (do not use or delete)
- `walk-in-guest@ua-fms.local` → Walk-in Guest. Holds every walk-in guest request; it does not sign in.

---

## 2. Legacy helper routes

The `/make-*` helper routes in `routes/web.php` are development-only and supported only for the legacy desktop launcher flow. They should not be relied on in production.

If you are using the current web-only portal, use seeded users or your normal user management process instead.

---

## Typical local setup order

```bash
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

For standard deployment, you do not need to visit `/make-admin`, `/make-college-staff`, or `/make-org-staff`.

---

## Security note

Remove or protect the `/make-*` routes before production deployment. They are intended for development and testing only.

## Related documentation

- `QUICKSTART.md` — full local setup flow
- `LAUNCHER_README.md` — launcher keys and build steps
