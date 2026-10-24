# Quick Test Guide - UA-FMS New Features

## Start the Server
Open a new terminal and run:
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Then visit: http://127.0.0.1:8000

## Test Accounts
| Role | Email | Password |
|------|-------|----------|
| Admin | adminA@example.com | Use locally configured test credentials |
| College Staff | college@ccis.com | Use locally configured test credentials |
| Org Staff | orgA@example.com | Use locally configured test credentials |

---

## ✅ Feature 1: Dashboard Charts

### Test Admin Dashboard
1. Login as: `adminA@example.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/admin/dashboard
3. **Expected**: See two charts:
   - Pie chart for "Facilities Requests Overview"
   - Bar chart for "Repair & Maintenance Overview"

### Test College Dashboard
1. Login as: `college@ccis.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/college/dashboard
3. **Expected**: See two charts showing YOUR requests only

### Test Org Dashboard
1. Login as: `orgA@example.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/org/dashboard
3. **Expected**: See two charts showing YOUR requests only

---

## ✅ Feature 2: Org Staff Maintenance Tickets

### Create a Repair Request (Org)
1. Login as: `orgA@example.com` using locally configured test credentials.
2. Click "Maintenance" → "New repair request" in sidebar
3. OR visit: http://127.0.0.1:8000/org/maintenance-requests/create
4. Fill in:
   - Facility: Choose any
   - Subject: "Broken chair"
   - Message: "The chair in the office is broken"
5. Click "Send request"
6. **Expected**: Success message with tracking code (e.g., RM-20261008-ABC123)

### View Your Repair Requests (Org)
1. Click "My repair requests" in sidebar
2. OR visit: http://127.0.0.1:8000/org/maintenance-requests
3. **Expected**: See list of your tickets with tracking codes

### Admin Views Org Repair Request
1. Logout, then log in as `adminA@example.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/admin/maintenance-tickets
3. **Expected**: See the ticket from orgA in the list

---

## ✅ Feature 3: Payment Upload & Verification

### Upload Payment Receipt (Public Guest)
1. Open: http://127.0.0.1:8000/request-facility
2. Fill in the applicant, event date/time, venue, and purpose fields.
3. In Documents, upload a JPG/JPEG, PNG, or PDF receipt no larger than 10MB.
4. Submit the request.
5. **Expected**: Request succeeds and shows the ticket page; submitting without a receipt, with a different file type, or with any file over 10 MiB is rejected. Optional supporting documents accept up to 10 JPG/JPEG, PNG, or PDF files.

### Verify Payment (Admin)
1. Logout, then log in as `adminA@example.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/admin/forms/facilities
3. Find the request you just created (Status: "Pending Payment")
4. Click "View" or visit the submission detail page
5. **Expected**: See "Payment Information" section with:
   - Payment Status: "Awaiting Verification"
   - Link to download the receipt
6. Click the receipt link. It should download through the authorized route and not expose a `/storage/` public URL.
7. Click "Verify Payment" button
8. **Expected**: Success message, status changes to "Verified"

### Approve After Payment Verification
1. Still on the same page as admin
2. **Expected**: Now you can see "Approve" and "Disapprove" buttons
3. Click "Approve"
4. **Expected**: Request approved successfully

### Test Payment Required Validation
1. Try to approve a request with "Pending Payment" status WITHOUT verifying payment first
2. **Expected**: Error message: "Please verify the payment attachment before approving this request."

---

## ✅ Feature 4: Repair Form PDF Generation (Fixed)

### Generate Repair Form PDF
1. Login as: `adminA@example.com` using locally configured test credentials.
2. Go to: http://127.0.0.1:8000/forms/repair
3. Fill in the form with test data
4. Click "Generate PDF Form"
5. **Expected**: PDF downloads successfully (no template error)

---

## Common Issues & Solutions

### Issue: Charts not showing
**Solution**: Make sure Chart.js loaded. Check browser console for errors.

### Issue: Payment upload not working
**Solution**: 
1. Check that `PAYMENT_RECEIPTS_PATH` exists, or create the default `storage/app/private/payment-receipts` directory.
2. Ensure the PHP-FPM user owns the directory and can write to it.
3. Check PHP `upload_max_filesize`/`post_max_size` and Nginx `client_max_body_size`; see the Local payment receipt storage section in `DEPLOYMENT_GUIDE.md`.

### Issue: Template error on repair form
**Solution**: Already fixed! Templates are in `storage/app/templates/`

### Issue: Routes not found
**Solution**: 
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

## Database Check Commands

### View payment-enabled requests:
```bash
mysql -u root facility_management -e "SELECT id, status, payment_status, payment_attachment FROM form_submissions WHERE payment_attachment IS NOT NULL;"
```

### View all maintenance tickets:
```bash
mysql -u root facility_management -e "SELECT id, ticket_code, status, requester_id FROM maintenance_tickets ORDER BY created_at DESC LIMIT 10;"
```

### Check dashboard stats:
```bash
mysql -u root facility_management -e "SELECT status, COUNT(*) as count FROM form_submissions WHERE type='facilities_utilization' GROUP BY status;"
```

---

## Summary of URLs

| Feature | URL |
|---------|-----|
| Admin Dashboard | http://127.0.0.1:8000/admin/dashboard |
| College Dashboard | http://127.0.0.1:8000/college/dashboard |
| Org Dashboard | http://127.0.0.1:8000/org/dashboard |
| Org Maintenance (Create) | http://127.0.0.1:8000/org/maintenance-requests/create |
| Org Maintenance (List) | http://127.0.0.1:8000/org/maintenance-requests |
| Admin Approve Requests | http://127.0.0.1:8000/admin/forms/facilities |
| Admin Maintenance Tickets | http://127.0.0.1:8000/admin/maintenance-tickets |
| College New Request | http://127.0.0.1:8000/college/requests/facilities/create |
| Org New Request | http://127.0.0.1:8000/org/requests/facilities/create |
| Repair Form (Admin) | http://127.0.0.1:8000/forms/repair |

---

**All features are now implemented and ready to test!**
