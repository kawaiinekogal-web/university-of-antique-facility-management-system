<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\FormSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeAndCompleteBookings extends Command
{
    protected $signature = 'bookings:normalize-and-complete';

    protected $description = 'Normalize legacy bookings and complete expired reservations';

    public function handle(): int
    {
        [$normalized, $completed] = DB::transaction(function () {
            $normalized = Booking::where('status', 'booked')->update(['status' => 'reserved']);
            $expired = Booking::whereIn('status', ['reserved', 'rescheduled'])
                ->where('end_time', '<', now())
                ->lockForUpdate()
                ->get();

            $submissionIds = [];
            foreach ($expired as $booking) {
                $booking->status = 'completed';
                $booking->save();

                $submissionId = $booking->additional_details['form_submission_id'] ?? null;
                if ($submissionId) {
                    $submissionIds[] = (int) $submissionId;
                }
            }

            if ($submissionIds) {
                FormSubmission::whereIn('id', array_unique($submissionIds))
                    ->whereIn('status', ['approved', 'reserved'])
                    ->update(['status' => 'completed']);
            }

            return [$normalized, $expired->count()];
        });

        $this->info("Normalized {$normalized} legacy booking(s); completed {$completed} expired booking(s).");

        return self::SUCCESS;
    }
}