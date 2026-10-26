# Functional Design Document (FDD) — UA Facility Management System

## 1. Overview

The UA Facility Management System is a role-based web application that centralizes facility reservations, utilization requests, and maintenance coordination for the University of Antique. It supports:

- Public users (reservation calendar and no-sign-in request, booking, and maintenance status lookup).
- GSU admins (full control over facilities, reservations, and users).
- College staff (manage college facilities and submit reservation requests).
- Organization staff (submit and track facility utilization requests).

This document describes the functional behavior of the system from a user and business perspective, independent of implementation details.

---

## 2. Actors & Roles

### 2.1 Public User (Unauthenticated)

- View the public reservation calendar.
- See upcoming reservations per facility.
- Check a facility request (`FM`), booking (`BKG`), or maintenance ticket (`RM`) status without signing in, using the tracking code and the submitter's last name.
- View matching status and relevant schedule/request details without exposing the requester's identity. A code or surname mismatch does not disclose the record.
- Public tracking is read-only for status. Approved guest facility requests may additionally expose temporary signed PDF and payment-receipt actions when applicable.

### 2.2 Admin (GSU Staff)

- Manage all facilities (GSU + college + org owned).
- Review and approve/disapprove utilization requests.
- Convert approved requests into reservations.
- Create high‑priority "direct" reservations that can preempt existing ones.
- View and manage all reservations (reschedule, cancel).
- Generate official PDF forms (Facilities Utilization, Repair & Maintenance).
- Manage portal user accounts (admins, college staff, org staff).
- View system‑wide analytics (monthly reservations per facility, daily counts).

### 2.3 College Staff

- Manage facilities owned by their college (CRUD, availability).
- Submit facilities utilization requests to GSU.
- View and filter:
  - Their own facilities utilization requests.
  - Their reservations (active / rescheduled).
- View read‑only reservation calendars:
  - Public calendar (home page).
  - College calendar (college + own reservations).

### 2.4 Organization Staff

- Submit facilities utilization requests (for events/activities).
- View and filter their own utilization requests.
- View their reservations (active / rescheduled).
- View read‑only reservation calendar scoped to all active reservations.

---

## 3. Core Functional Areas

### 3.1 Facilities Management

**Owner:** Admin (GSU), College staff (for college‑owned facilities).

**Functional Requirements:**

1. Admin can:
   - Create facilities with:
     - Name, location.
     - Owner type: `gsu`, `college`, or `org`.
     - Owner college (optional).
     - Description, active flag, availability status (`available`, `unavailable`, `maintenance`).
   - Edit any facility.
   - Delete facilities.

2. College staff can:
   - Create facilities where:
     - Owner type is implicitly `college`.
     - Owner college is the staff member's college.
   - Edit facilities belonging to their college.
   - Delete their college facilities.
   - Cannot change core facility ownership or global GSU facilities.

3. Facility availability:
   - `available`: can be reserved.
   - `unavailable` or `maintenance`:
     - Cannot be reserved by the automated request→reservation flow.
     - Public calendar still shows existing reservations for historical transparency.

---

### 3.2 Facilities Utilization Requests

**Owner:** College staff, Org staff; reviewed by Admin.

**Functional Requirements:**

1. **Form fields (College & Org)**:
   - Date of activity (must be at least 30 days from today).
   - Optional end date for a continuous event range (maximum 14 days including the start date).
   - Start time and end time (end > start).
   - One or more facilities from the active facility list; all share the same dates and times.
   - Purpose of activity.
   - Equipment quantities:
     - Monobloc chairs, tables, fans, rostrum, flag & school color, sound, LED wall.
   - Noted‑by signatory (predefined or custom).
   - System captures requester identity (user, unit) and date of request.

2. **Reservation conflict handling:**
   - The form shows booked time ranges for selected facilities and dates and blocks submission when a requested time overlaps an active reservation.
   - Back-to-back reservations are allowed when one ends at the exact time the next begins.
   - Approval rechecks and reserves every selected facility on every requested day atomically. If availability changes after submission, no bookings are created and the request remains pending.

3. **Status lifecycle (FormSubmission):**
   - `pending`: newly submitted requests.
   - `approved`: admin has approved; request still needs signing and conversion to reservation.
   - `reserved`: request has been converted into a reservation.
   - `disapproved`: request denied by admin.
   - `cancelled`: requester has cancelled a pending request (college or org).

4. **Cancel request (College & Org):**
   - A requester may cancel **only while status is `pending`**.
   - After cancellation:
     - Status becomes `cancelled`.
     - Request is hidden from "pending approvals" on admin side but visible historically.

5. **College/Org views:**
   - List of own requests (status != 'reserved'), filterable by:
     - Control number.
     - Status.
     - Activity date.
   - Detail page shows:
     - Request metadata.
     - All selected facilities, date range, and time.
     - Purpose.
     - Equipment breakdown.
     - Signatory information.
     - Cancel button when status is `pending`.

---

### 3.3 Reservations (Bookings)

**Owner:** System (via approved requests), Admin (direct/high‑priority).

**Terminology Note:** The system uses `reserved` as the canonical active status. Older `booked` values are normalized to `reserved`.

**Functional Requirements:**

1. **Creation from approved requests:**
   - Admin selects an approved utilization request.
   - System validates:
     - Request status is `approved`.
     - Every selected facility is `available`.
     - No requested day/time overlaps an active reservation on any selected facility.
   - On success:
     - Creates one `Booking` with status `reserved` for each event day.
     - Links all selected facilities to each booking (many‑to‑many).
     - Updates the request's status to `reserved`.
     - Sends notifications to requester and admins.

2. **Direct high‑priority reservations (admin):**
   - Admin can bypass the request flow to create direct reservations.
   - Direct reservations:
     - Can span multiple facilities.
     - Convert any overlapping `reserved` reservations for those facilities to:
       - `pending` (conflict is pushed back for renegotiation).
     - Notify affected requesters that their reservation has been preempted.

3. **Statuses (Booking):**
   - `reserved`: active reservation.
   - `rescheduled`: reserved but modified by admin (time/facilities changed).
   - `completed`: end time is in the past (auto‑set).
   - `cancelled`: cancelled by admin.
   - Old `booked` values are mapped to `reserved` on migration/boot.

4. **Auto-completion:**
   - On each app boot, the system:
     - Converts any leftover `booked` bookings to `reserved`.
     - Marks `reserved` bookings with `end_time < now()` as `completed`.

5. **Modification (Admin):**
   - Admin can:
     - Change facilities.
     - Change date/time.
     - Update purpose.
     - Adjust equipment block.
     - Provide a reschedule reason.
   - System sets status to `rescheduled`, stores reschedule note, notifies requester and co‑admins.

6. **Cancellation (Admin):**
   - Admin can cancel an existing reservation.
   - Requires a cancellation reason.
   - Status becomes `cancelled`.
   - Reason is stored and sent via notification.

7. **College/Org reservation views:**
   - "My Reservations" pages show only `reserved` / `rescheduled` reservations for the requesting user.
   - Filters: search by facility/purpose, status, facility, month.

---

### 3.4 Calendars

**Owner:** Public, Admin, College, Org.

**Common behavior:**

- Calendar shows days in a month with dots or styling for days containing reservations.
- Hover or title text indicates number of reservations.
- A right‑hand "Upcoming Reservations" list highlights the next few reservations.

**Views:**

1. **Public home calendar (`/`):**
   - Route: `home` → `PublicCalendarController@index`.
   - Shows all `reserved` + `rescheduled` reservations within the month.
   - Navigation stays on `/` with `?month=YYYY-MM#calendar`.
   - The homepage also provides a public request tracker for `FM`, `BK` (booking reference `BKG-…`), and `RM` records. Each lookup requires the tracking code and the submitter's last name; login is not required.
   - Lookup results return status and relevant record details only. FM requests may include temporary signed PDF and receipt-upload links for approved guest requests; these actions do not grant access to internal record editing.

2. **Admin reservation calendar (`/admin/calendar`):**
   - Same visual calendar partial, but route name is `admin.calendar`.
   - Shows all system reservations (reserved/rescheduled).
   - Admin context (within admin layout).

3. **College calendar (`/college/calendar`):**
   - Same shared partial; route name `college.calendar`.
   - Shows:
     - Reservations created by the college staff member, plus
     - Reservations for facilities owned by that college.

4. **Org calendar (`/org/bookings` and `/org/calendar`):**
   - Shared partial; `calendarRoute = 'org.bookings.index'` or `org.bookings.calendar` depending on view.
   - Shows active reservations, read‑only.

---

### 3.5 Notifications

**Owner:** All authenticated roles.

**Functional Requirements:**

- Each substantive event produces one or more in‑app notifications:
  - `form_pending`: request submitted.
  - `form_approved`: request approved.
  - `form_disapproved`: request disapproved.
  - `booking_created`: reservation created from request.
  - `booking_rescheduled`: reservation changed by GSU.
  - `booking_preempted`: reservation preempted by a direct booking.
  - `booking_cancelled`: reservation cancelled by GSU.
  - `form_pending_admin`: new request submitted (to all admins).
  - `booking_rescheduled_admin` / `booking_cancelled_admin`: admin‑side alerts.

- Users can view all notifications, see unread vs read, and mark individual ones as read.

---

## 4. Security & Access Rules (Functional)

- Only authenticated users may access dashboards or modify data.
- Role middleware enforces:
  - `/admin/*`: admins only.
  - `/college/*`: college_staff only.
  - `/org/*`: org_staff only.
- College and org users can only:
  - View and modify their own requests and reservations.
  - College facility edits are constrained to the user's college.
- Public users have no access to staff editing or management capabilities. The homepage tracker is read-only for status; an approved guest facility request may expose only its applicable signed PDF and payment-receipt actions.

---

## 5. Data Normalization Rules

- On migration/boot:
  - `bookings.status = 'booked'` → `reserved`.
  - `form_submissions.status = 'booked'` → `reserved`.
- Auto‑completion:
  - `reserved` bookings with `end_time` earlier than now become `completed`.
- Overlapping checks treat `reserved`, `rescheduled`, and legacy `booked` reservations as active. Requests are rejected when `existing_start < requested_end && existing_end > requested_start`; exact back-to-back boundaries do not overlap.

---

## 6. Non‑Functional Points (High‑level)

- Monochrome admin UI with consistent black/white styling across dashboards.
- Shared calendar partial for public and all roles to minimize UI drift.
- Document generation uses official DOCX templates and produces PDF output for GSU forms.

This FDD should be the primary reference for how the system is expected to behave from the user perspective, especially around reservations, statuses (`reserved` vs `completed`), and role‑based actions.
