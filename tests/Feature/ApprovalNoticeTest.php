<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\FormSubmission;
use App\Models\User;
use App\Services\BookingService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** The 30-day notice counts from the day the request was submitted, not from the day GSU reviews it. */
class ApprovalNoticeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_request_with_exactly_30_days_notice_can_be_approved_days_later(): void
    {
        $submittedOn = now()->subDays(3)->startOfDay();
        $submission = $this->submission($submittedOn, $submittedOn->copy()->addDays(30)->toDateString());

        $result = $this->service()->createFromSubmission($submission);

        $this->assertInstanceOf(Booking::class, $result, is_string($result) ? $result : '');
        $this->assertSame('reserved', $submission->fresh()->status);
    }

    public function test_request_with_less_than_30_days_notice_is_still_refused(): void
    {
        $submittedOn = now()->subDays(3)->startOfDay();
        $submission = $this->submission($submittedOn, $submittedOn->copy()->addDays(29)->toDateString());

        $this->assertSame('Date of activity was less than 30 days after the request was submitted.', $this->service()->createFromSubmission($submission));
        $this->assertSame('pending', $submission->fresh()->status);
    }

    public function test_request_whose_date_has_passed_is_refused(): void
    {
        $submission = $this->submission(now()->subDays(40)->startOfDay(), now()->subDay()->toDateString());

        $this->assertSame('Date of activity has already passed.', $this->service()->createFromSubmission($submission));
    }

    private function service(): BookingService
    {
        return new BookingService(\Mockery::mock(NotificationService::class)->shouldIgnoreMissing());
    }

    private function submission(Carbon $submittedOn, string $date): FormSubmission
    {
        $user = User::create([
            'name' => 'Notice Test User', 'last_name' => 'User', 'email' => 'notice-' . uniqid() . '@example.test',
            'password' => 'test-password', 'role' => 'college_staff',
        ]);
        $facility = Facility::create(['name' => 'Notice Venue ' . uniqid(), 'owner_type' => 'gsu', 'is_active' => true, 'availability_status' => 'available']);
        $submission = FormSubmission::create([
            'type' => 'facilities_utilization', 'requester_id' => $user->id, 'requester_type' => 'college',
            'requester_unit' => 'Test College', 'status' => 'pending',
            'payload' => [
                'facility_id' => $facility->id, 'facility_ids' => [$facility->id], 'date_activity' => $date,
                'date_request' => $submittedOn->toDateString(), 'time_range' => ['start' => '09:00', 'end' => '11:00'],
                'purpose' => 'Notice rule test',
            ],
        ]);
        $submission->created_at = $submittedOn;
        $submission->save();

        return $submission;
    }
}
