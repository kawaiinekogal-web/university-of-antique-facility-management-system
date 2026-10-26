<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\FormSubmission;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicketLookupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_signed_in_requester_can_look_up_a_booking_by_reference(): void
    {
        $user = $this->user('college_staff', 'CCIS');
        $booking = $this->booking($user, 'college', 'BKG-20261025173245-11-ABC123');
        $orgUser = $this->user('org_staff');
        $orgBooking = $this->booking($orgUser, 'org', 'BKG-20261025173445-11-ORG123');

        $this->actingAs($user)
            ->getJson('/track/ticket?type=BK&code=' . $booking->booking_code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('status', 'reserved')
            ->assertJsonPath('code', $booking->booking_code)
            ->assertJsonPath('venue', 'Lookup Test Hall');

        $this->actingAs($orgUser)
            ->getJson('/track/ticket?type=BK&code=' . $orgBooking->booking_code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('code', $orgBooking->booking_code);

        auth()->logout();
        $this->getJson('/track/ticket?type=BK&code=' . $booking->booking_code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('code', $booking->booking_code)
            ->assertJsonMissingPath('requester_name');
    }

    public function test_maintenance_ticket_status_is_available_without_sign_in_when_last_name_matches(): void
    {
        $requester = $this->user('college_staff', 'CCIS');
        $ticket = $this->maintenanceTicket($requester);

        $this->getJson('/track/ticket?type=RM&code=' . $ticket->ticket_code)
            ->assertOk()
            ->assertJsonPath('status', 'needs_last_name');

        $this->getJson('/track/ticket?type=RM&code=' . $ticket->ticket_code . '&last_name=Wrong')
            ->assertNotFound();

        $this->getJson('/track/ticket?type=RM&code=' . $ticket->ticket_code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('status', 'notified')
            ->assertJsonPath('code', $ticket->ticket_code)
            ->assertJsonMissingPath('requester');
    }

    public function test_explicit_last_name_is_matched_exactly_without_parsing_the_full_name(): void
    {
        $requester = $this->user('college_staff', 'CCIS');
        $requester->name = 'María Jo Dela Cruz';
        $requester->last_name = 'Dela Cruz';
        $requester->save();
        $booking = $this->booking($requester, 'college', 'BKG-20261025173545-11-EXPLICIT');

        $this->getJson('/track/ticket?type=BK&code=' . $booking->booking_code . '&last_name=Dela%20Cruz')
            ->assertOk()
            ->assertJsonPath('code', $booking->booking_code);

        $this->getJson('/track/ticket?type=BK&code=' . $booking->booking_code . '&last_name=Cruz')
            ->assertNotFound();
    }

    public function test_facility_request_lookup_uses_its_separately_saved_last_name(): void
    {
        $requester = $this->user('college_staff', 'CCIS');
        $requester->name = 'Dela Cruz Maria Jo';
        $requester->last_name = null;
        $requester->save();
        $submission = FormSubmission::create([
            'type' => 'facilities_utilization',
            'requester_id' => $requester->id,
            'requester_type' => 'college',
            'requester_unit' => 'CCIS',
            'status' => 'pending',
            'payload' => [
                'requester_name' => $requester->name,
                'requester_last_name' => 'Dela Cruz',
                'date_activity' => now()->addDays(40)->toDateString(),
                'time_range' => ['start' => '09:00', 'end' => '10:00'],
            ],
        ]);
        $code = 'FM-' . $submission->created_at->format('Y') . '-' . $submission->id;

        $this->getJson('/track/ticket?type=FM&code=' . $code . '&last_name=Dela%20Cruz')
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->getJson('/track/ticket?type=FM&code=' . $code . '&last_name=Cruz')
            ->assertNotFound();
    }

    public function test_guest_booking_uses_the_linked_request_tracking_last_name(): void
    {
        $walkIn = $this->user('guest');
        $walkIn->name = 'Walk-in Guest';
        $walkIn->last_name = null;
        $walkIn->save();
        $submission = FormSubmission::create([
            'type' => 'facilities_utilization',
            'requester_id' => $walkIn->id,
            'requester_type' => 'guest',
            'requester_unit' => 'Community Partner',
            'status' => 'reserved',
            'payload' => ['requester_name' => 'Dela Cruz Maria Jo', 'requester_last_name' => 'Dela Cruz'],
        ]);
        $booking = $this->booking($walkIn, 'guest', 'BKG-20261025173645-11-GUEST2');
        $booking->additional_details = ['form_submission_id' => $submission->id];
        $booking->save();

        $this->getJson('/track/ticket?type=BK&code=' . $booking->booking_code . '&last_name=Dela%20Cruz')
            ->assertOk()
            ->assertJsonPath('code', $booking->booking_code);
    }

    public function test_guest_booking_lookup_requires_the_requesters_last_name(): void
    {
        $guest = $this->user('guest');
        $booking = $this->booking($guest, 'guest', 'BKG-20261025173345-11-GUEST1');
        $url = '/track/ticket?type=BK&code=' . $booking->booking_code;

        $this->getJson($url)->assertOk()->assertJsonPath('status', 'needs_last_name');
        $this->getJson($url . '&last_name=Wrong')->assertNotFound();
        $this->getJson($url . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('code', $booking->booking_code);
    }

    public function test_public_guest_request_requires_a_separately_entered_tracking_last_name(): void
    {
        $this->post('/request-facility', [])
            ->assertSessionHasErrors('last_name');
    }

    public function test_college_facility_request_can_be_viewed_without_sign_in_with_requester_last_name(): void
    {
        $collegeUser = $this->user('college_staff', 'CCIS');
        $submission = FormSubmission::create([
            'type' => 'facilities_utilization',
            'requester_id' => $collegeUser->id,
            'requester_type' => 'college',
            'requester_unit' => 'CCIS',
            'status' => 'pending',
            'payload' => [
                'requester_name' => $collegeUser->name,
                'date_activity' => now()->addDays(40)->toDateString(),
                'time_range' => ['start' => '09:00', 'end' => '10:00'],
                'facility_name' => 'Lookup Test Hall',
            ],
            'payment_status' => 'not_required',
        ]);
        $code = 'FM-' . $submission->created_at->format('Y') . '-' . $submission->id;

        $this->getJson('/track/ticket?type=FM&code=' . $code)
            ->assertOk()
            ->assertJsonPath('status', 'needs_last_name');

        $this->getJson('/track/ticket?type=FM&code=' . $code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('can_download_pdf', false);
    }

    public function test_signed_in_guest_can_look_up_own_facility_request_with_requester_last_name(): void
    {
        $guest = $this->user('guest');
        $submission = FormSubmission::create([
            'type' => 'facilities_utilization',
            'requester_id' => $guest->id,
            'requester_type' => 'guest',
            'requester_unit' => $guest->organization_name,
            'status' => 'pending',
            'payload' => [
                'requester_name' => $guest->name,
                'date_activity' => now()->addDays(40)->toDateString(),
                'time_range' => ['start' => '09:00', 'end' => '10:00'],
                'facility_name' => 'Lookup Test Hall',
            ],
            'payment_status' => 'not_required',
        ]);
        $code = 'FM-' . $submission->created_at->format('Y') . '-' . $submission->id;

        $this->actingAs($guest)
            ->getJson('/track/ticket?type=FM&code=' . $code . '&last_name=Requester')
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('code', $code);
    }

    private function user(string $role, ?string $college = null): User
    {
        return User::create([
            'name' => 'Taylor Requester',
            'last_name' => null,
            'email' => uniqid('tracker-', true) . '@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'college_name' => $college,
            'organization_name' => null,
        ]);
    }

    private function booking(User $requester, string $requesterType, string $code): Booking
    {
        $facility = Facility::create([
            'name' => 'Lookup Test Hall',
            'location' => 'Test Campus',
            'owner_type' => 'gsu',
            'is_active' => true,
            'availability_status' => 'available',
        ]);
        $booking = Booking::create([
            'requester_id' => $requester->id,
            'start_time' => now()->addDays(40),
            'end_time' => now()->addDays(40)->addHour(),
            'requester_type' => $requesterType,
            'requester_unit' => $requester->college_name,
            'status' => 'reserved',
            'purpose' => 'Test reservation',
            'booking_code' => $code,
        ]);
        $booking->facilities()->sync([$facility->id]);

        return $booking;
    }

    private function maintenanceTicket(User $requester): MaintenanceTicket
    {
        $facility = Facility::create([
            'name' => 'Lookup Test Workshop',
            'location' => 'Test Campus',
            'owner_type' => 'gsu',
            'is_active' => true,
            'availability_status' => 'available',
        ]);

        return MaintenanceTicket::create([
            'facility_id' => $facility->id,
            'requester_id' => $requester->id,
            'request_method' => 'system',
            'status' => 'notified',
            'issue_description' => 'Test maintenance issue',
            'ticket_code' => 'RM-20261025-TEST01',
            'subject' => 'Test maintenance request',
            'requested_at' => now(),
        ]);
    }
}
