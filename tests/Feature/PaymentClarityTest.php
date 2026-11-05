<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentClarityTest extends TestCase
{
    use RefreshDatabase;

    private function guestRequest(array $facilityNames, string $paymentStatus = 'pending_payment'): FormSubmission
    {
        config(['venue-rates' => [
            ['name' => 'Balay ni jUAn', 'rate' => 'PhP 8,000.00'],
            ['name' => 'Class Room/s', 'rate' => 'PhP 50.00/head in a day'],
        ]]);
        $ids = collect($facilityNames)->map(fn ($name) => Facility::create([
            'name' => $name, 'location' => 'Campus', 'owner_type' => 'gsu', 'is_active' => true, 'availability_status' => 'available',
        ])->id)->all();

        return FormSubmission::create([
            'type' => 'facilities_utilization',
            'requester_id' => User::factory()->create(['role' => 'guest'])->id,
            'requester_type' => 'guest',
            'status' => 'reserved',
            'payment_status' => $paymentStatus,
            'payload' => [
                'requester_last_name' => 'Pangilinan',
                'facility_ids' => $ids,
                'date_activity' => now()->addDays(40)->toDateString(),
                'date_end' => now()->addDays(41)->toDateString(), // 2 days
                'time_range' => ['start' => '08:00', 'end' => '17:00'],
                'control_no' => 'BKG-TEST-1',
            ],
        ]);
    }

    public function test_tracker_shows_the_same_venue_total_as_the_pdf(): void
    {
        $submission = $this->guestRequest(['BALAY NI JUAN']);

        $this->assertSame(16000.0, $submission->rentalQuote()['total']);
        $this->getJson('/track/ticket?type=FM&code=' . $submission->ticket_code . '&last_name=Pangilinan')
            ->assertOk()
            ->assertJsonPath('amount_due', 16000);
    }

    public function test_amount_is_withheld_when_the_rate_depends_on_attendance(): void
    {
        $submission = $this->guestRequest(['Class Room/s']);

        $this->assertFalse($submission->rentalQuote()['complete']);
        $this->getJson('/track/ticket?type=FM&code=' . $submission->ticket_code . '&last_name=Pangilinan')
            ->assertOk()
            ->assertJsonPath('amount_due', null);
    }

    public function test_admin_list_finds_requests_by_ticket_code_and_shows_payment_step(): void
    {
        $submission = $this->guestRequest(['BALAY NI JUAN'], 'payment_uploaded');
        $other = $this->guestRequest(['BALAY NI JUAN']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.forms.facilities.index', ['search' => strtolower($submission->ticket_code)]))
            ->assertOk()
            ->assertSee($submission->ticket_code)
            ->assertDontSee($other->ticket_code)
            ->assertSee('Receipt to verify');
    }
}
