<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicRequestOwnershipTest extends TestCase
{
    use DatabaseTransactions;

    /** Another guest saving at the same moment (same shared walk-in account) must not swap tickets. */
    public function test_guest_gets_their_own_request_when_another_guest_submits_concurrently(): void
    {
        Mail::fake();
        $facility = Facility::create([
            'name' => 'Ownership Test Hall', 'location' => 'Test Campus', 'owner_type' => 'gsu',
            'is_active' => true, 'availability_status' => 'available',
        ]);

        // Right after this guest's row is created, a second guest's row lands (newer id, same walk-in account)
        $concurrent = null;
        FormSubmission::created(function (FormSubmission $mine) use (&$concurrent) {
            if ($concurrent === null) {
                $concurrent = false; // guard: the row below fires this listener too
                $concurrent = FormSubmission::create([
                    'type' => 'facilities_utilization', 'requester_id' => $mine->requester_id, 'requester_type' => 'guest',
                    'requester_unit' => 'Other Guest Org', 'status' => 'pending', 'payment_status' => 'not_required',
                    'payload' => ['client_name' => 'Other Guest', 'email' => 'other@example.test'],
                ]);
            }
        });

        $response = $this->post('/request-facility', [
            'client_name' => 'Maria Jo', 'last_name' => 'Dela Cruz', 'contact_number' => '0917-123-4567',
            'email' => 'guest@example.test', 'organization' => 'Community Partner',
            'noted_signatory_id' => 'custom', 'noted_signatory_custom' => 'Barangay Captain',
            'street_address' => 'Purok 3', 'barangay' => 'Bahuyan', 'city_municipality' => 'Barbaza',
            'province' => 'Antique', 'country' => 'Philippines',
            'date_activity' => now()->addDays(40)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'facility_ids' => [$facility->id], 'purpose' => 'Community assembly', 'number_of_persons' => 50,
        ]);

        $mine = FormSubmission::where('payload->email', 'guest@example.test')->firstOrFail();
        $response->assertRedirect(route('public.request.submitted', $mine));
        $this->assertNotSame($concurrent->id, $mine->id);
        $this->assertSame(['client_name' => 'Other Guest', 'email' => 'other@example.test'], $concurrent->fresh()->payload);
        $this->assertSame([$mine->id], session('public_ticket_ids'));
    }
}
