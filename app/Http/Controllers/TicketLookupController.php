<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FormSubmission;
use App\Models\MaintenanceTicket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Public "Track a request" lookup used by the landing page.
 *
 * A facility ticket code is FM-<year submitted>-<request id, 5 digits> (e.g. FM-2026-00012).
 * The caller must also give the requester's last name. A wrong code and a wrong name get the
 * same "notfound" answer, and only status / venue / date / time are returned.
 */
class TicketLookupController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $type = strtoupper((string) $request->query('type', 'FM'));
        $code = strtoupper(preg_replace('/\s+/', '', (string) $request->query('code', '')));
        $lastName = mb_strtolower(trim((string) $request->query('last_name', '')));

        $notFound = response()->json(['status' => 'notfound'], 404);

        if ($type === 'RM') {
            return $this->repairTicket($code, $lastName) ?? $notFound;
        }
        if ($type !== 'FM') {
            return $notFound;
        }

        if ($lastName === '' || ! preg_match('/^FM-(\d{4})-(\d{1,10})$/', $code, $m)) {
            return $notFound;
        }

        $submission = FormSubmission::with('requester')
            ->where('type', 'facilities_utilization')
            ->find((int) $m[2]);

        if (! $submission || $submission->created_at?->format('Y') !== $m[1]) {
            return $notFound;
        }

        $payload = $submission->payload ?? [];

        // Accept the ending of any name on the request (requester name, guest client name, account name).
        // Handles multi-word last names ("Dela Cruz") and suffixes ("Dapar II", "Santos Jr.").
        $names = array_filter([
            $payload['requester_name'] ?? null,
            $payload['client_name'] ?? null,
            optional($submission->requester)->name,
        ]);

        if (! $this->lastNameMatches($lastName, $names)) {
            return $notFound;
        }

        // GSU reschedules the booking, not the request: report its new schedule with the same approved actions.
        $rescheduled = Booking::with('facilities')
            ->where('additional_details->form_submission_id', $submission->id)
            ->where('status', 'rescheduled')
            ->orderBy('start_time')
            ->first();

        $approved = in_array($submission->status, ['approved', 'reserved'], true);
        $canUploadPayment = $approved && $submission->requester_type === 'guest'
            && in_array($submission->payment_status, ['pending_payment', 'not_required'], true);

        return response()->json([
            'status' => $rescheduled ? 'rescheduled' : $submission->status,
            'code'   => $code,
            'venue'  => $rescheduled
                ? ($rescheduled->facilities->pluck('name')->join(', ') ?: null)
                : ($payload['facility_name'] ?? ($payload['facility_name_custom'] ?? null)),
            'date'   => $rescheduled
                ? $rescheduled->start_time?->format('D, M j, Y')
                : $this->dateLabel($payload['date_activity'] ?? null, $payload['date_end'] ?? null),
            'time'   => $rescheduled && $rescheduled->start_time && $rescheduled->end_time
                ? $rescheduled->start_time->format('g:i A') . ' – ' . $rescheduled->end_time->format('g:i A')
                : $this->timeLabel($payload['time_range'] ?? []),
            'payment_status' => $submission->payment_status ?? 'not_required',
            'can_download_pdf' => $approved,
            'pdf_url' => $approved
                ? URL::temporarySignedRoute('guest.forms.facilities.pdf', now()->addMinutes(30), ['submission' => $submission->id], false)
                : null,
            'can_upload_payment' => $canUploadPayment,
            'payment_upload_url' => $canUploadPayment
                ? URL::temporarySignedRoute('ticket.payment.upload', now()->addMinutes(15), ['submission' => $submission->id], false)
                : null,
        ]);
    }

    public function uploadPayment(Request $request, FormSubmission $submission): JsonResponse
    {
        abort_unless(
            $submission->type === 'facilities_utilization' && $submission->requester_type === 'guest',
            404
        );
        abort_unless(in_array($submission->status, ['approved', 'reserved'], true), 403, 'Payment can only be uploaded after approval.');
        abort_unless(
            in_array($submission->payment_status, ['pending_payment', 'not_required'], true),
            409,
            'A payment receipt has already been submitted.'
        );

        $validated = $request->validate([
            'payment_attachment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'mimetypes:image/jpeg,image/png,application/pdf',
                'max:10240',
            ],
        ]);

        $submission->payment_attachment = $validated['payment_attachment']->store('receipts', 'payment_receipts');
        $submission->payment_status = 'payment_uploaded';
        $submission->save();

        return response()->json([
            'payment_status' => $submission->payment_status,
            'message' => 'Receipt submitted. GSU will verify your payment.',
        ]);
    }

    /**
     * Repair & maintenance ticket by its tracking code (e.g. RM-20261019-PV739Q) + the sender's last name.
     * Returns status (notified / pending / ongoing / success / failed), facility, subject and when it was sent.
     */
    private function repairTicket(string $code, string $lastName): ?JsonResponse
    {
        if ($lastName === '' || ! preg_match('/^RM-[A-Z0-9-]{3,40}$/', $code)) {
            return null;
        }

        $ticket = MaintenanceTicket::with(['requester', 'facility'])->where('ticket_code', $code)->first();

        if (! $ticket || ! $this->lastNameMatches($lastName, array_filter([optional($ticket->requester)->name]))) {
            return null;
        }

        return response()->json([
            'status'  => $ticket->status,
            'code'    => $ticket->ticket_code,
            'venue'   => $ticket->facility?->name ?? $ticket->facility_other,
            'subject' => $ticket->subject,
            'sent'    => ($ticket->requested_at ?? $ticket->created_at)?->format('M j, Y · g:i A'),
        ]);
    }

    private function lastNameMatches(string $input, array $names): bool
    {
        $norm = fn ($s) => trim(preg_replace('/\s+/u', ' ', mb_strtolower(str_replace([',', '.'], ' ', (string) $s))));
        $dropSuffix = fn ($s) => preg_replace('/\s+(jr|sr|ii|iii|iv|v)$/u', '', $s);

        $typed = $norm($input);
        if ($typed === '') {
            return false;
        }
        $typedBare = $dropSuffix($typed);

        foreach ($names as $name) {
            $full = $norm($name);
            if (str_ends_with(' ' . $full, ' ' . $typed)
                || str_ends_with(' ' . $dropSuffix($full), ' ' . $typedBare)) {
                return true;
            }
        }

        return false;
    }

    private function dateLabel(?string $start, ?string $end): ?string
    {
        try {
            if (! $start) {
                return null;
            }
            $from = Carbon::parse($start);
            if ($end && Carbon::parse($end)->gt($from)) {
                return $from->format('M j') . ' – ' . Carbon::parse($end)->format('M j, Y');
            }

            return $from->format('D, M j, Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function timeLabel(array $range): ?string
    {
        $fmt = function ($t) {
            try {
                return $t ? Carbon::createFromFormat('H:i', substr($t, 0, 5))->format('g:i A') : null;
            } catch (\Throwable $e) {
                return null;
            }
        };
        $start = $fmt($range['start'] ?? null);
        $end = $fmt($range['end'] ?? null);

        return $start && $end ? $start . ' – ' . $end : ($start ?: null);
    }
}
