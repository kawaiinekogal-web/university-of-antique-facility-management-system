<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyRecordsController extends Controller
{
    public function index(Request $request)
    {
        // Get month and year from request or default to current month
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Create date range for the selected month
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all bookings (reserved status) for the month
        $bookings = Booking::with(['facilities', 'requester'])
            ->whereBetween('start_time', [$startDate, $endDate])
            ->where('status', 'reserved')
            ->orderBy('start_time', 'asc')
            ->get();

        // Each booking records the exact source submission, avoiding accidental matches
        // when the same requester has multiple reservations.
        $submissionIds = $bookings
            ->pluck('additional_details.form_submission_id')
            ->filter()
            ->unique();
        $formSubmissions = FormSubmission::whereIn('id', $submissionIds)->get()->keyBy('id');

        // Format bookings for display
        $records = $bookings->map(function ($booking) use ($formSubmissions) {
            $facilities = $booking->facilities;
            $details = $booking->additional_details ?? [];
            $formSubmission = $formSubmissions->get($details['form_submission_id'] ?? null);
            $payload = $formSubmission?->payload ?? [];
            $equipment = $details['equipment'] ?? $payload['equipment'] ?? [];

            return [
                'date_requested' => $booking->requested_at ? $booking->requested_at->format('F d, Y') : ($booking->created_at ? $booking->created_at->format('F d, Y') : '-'),
                'date_of_activity' => $booking->start_time->format('F d, Y'),
                'time' => $booking->start_time->format('g:ia') . '-' . $booking->end_time->format('g:ia'),
                'purpose' => $booking->purpose ?? '-',
                'requester' => $payload['client_name'] ?? $booking->requester->name ?? '-',
                'contact_no' => $booking->requester->contact_number ?? '-',
                'venue' => $facilities->isNotEmpty() ? $facilities->pluck('name')->join(', ') : '-',
                'equipment' => [
                    'chairs' => $equipment['monobloc_chair'] ?? $payload['qty_monobloc'] ?? 0,
                    'tables' => $equipment['table'] ?? $payload['qty_table'] ?? 0,
                    'fans' => $equipment['electric_fan'] ?? $payload['qty_fan'] ?? 0,
                    'rostrum' => $equipment['rostrum'] ?? $payload['qty_rostrum'] ?? 0,
                    'flags' => $equipment['flag'] ?? $payload['qty_flag'] ?? 0,
                    'sound' => $equipment['sound'] ?? $payload['qty_sound'] ?? 0,
                    'led' => $equipment['led'] ?? $payload['qty_led'] ?? 0,
                ],
                'rental_fee' => $facilities->sum(fn ($facility) => $facility->rental_fee ?? 0),
            ];
        });

        // Calculate venue usage summary
        $venueSummary = $bookings
            ->flatMap(fn ($booking) => $booking->facilities->pluck('name'))
            ->countBy()
            ->sortKeys();

        $allVenues = Facility::orderBy('name')
            ->pluck('name')
            ->mapWithKeys(fn ($name) => [$name => (int) $venueSummary->get($name, 0)]);
        foreach ($venueSummary as $venue => $count) {
            $allVenues[$venue] = $count;
        }
        $allVenues = $allVenues->sortByDesc(fn ($count) => $count);

        // Calculate total rental fees
        $totalRentalFees = $records->sum('rental_fee');

        // Get monthly trend data (last 12 months)
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $trendMonth = now()->subMonths($i);
            $count = Booking::whereBetween('start_time', [
                $trendMonth->copy()->startOfMonth(),
                $trendMonth->copy()->endOfMonth()
            ])
            ->where('status', 'reserved')
            ->count();

            $monthlyTrend[] = [
                'month' => $trendMonth->format('M Y'),
                'count' => $count
            ];
        }

        return view('admin.monthly-records.index', compact(
            'records',
            'venueSummary',
            'allVenues',
            'totalRentalFees',
            'month',
            'year',
            'monthlyTrend'
        ));
    }
}
