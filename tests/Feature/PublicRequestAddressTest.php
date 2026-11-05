<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicRequestAddressTest extends TestCase
{
    use DatabaseTransactions;

    public function test_address_fields_are_saved_separately_and_as_one_address_line(): void
    {
        $this->submit([
            'street_address' => 'Purok 3', 'barangay' => 'Bahuyan', 'city_municipality' => 'Barbaza',
            'province' => 'Antique', 'country' => 'Philippines',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $payload = FormSubmission::where('payload->email', 'guest@example.test')->latest('id')->firstOrFail()->payload;
        $this->assertSame('Purok 3, Bahuyan, Barbaza, Antique, Philippines', $payload['address']);
        $this->assertSame(['Purok 3', 'Bahuyan', 'Barbaza', 'Antique', 'Philippines'],
            [$payload['street_address'], $payload['barangay'], $payload['city_municipality'], $payload['province'], $payload['country']]);
    }

    public function test_every_address_field_is_required(): void
    {
        $this->submit([])->assertSessionHasErrors(['street_address', 'barangay', 'city_municipality', 'province', 'country']);
    }

    public function test_a_single_legacy_address_line_is_no_longer_accepted(): void
    {
        // Used to be stored with "Not provided" placeholders, bypassing the structured address
        $this->submit(['address' => 'Sibalom, Antique'])
            ->assertSessionHasErrors(['street_address', 'barangay', 'city_municipality', 'province', 'country']);
    }

    private function submit(array $address)
    {
        Mail::fake();
        $facility = Facility::create([
            'name' => 'Address Test Hall',
            'location' => 'Test Campus',
            'owner_type' => 'gsu',
            'is_active' => true,
            'availability_status' => 'available',
        ]);

        return $this->post('/request-facility', array_merge([
            'client_name' => 'Maria Jo',
            'last_name' => 'Dela Cruz',
            'contact_number' => '0917-123-4567',
            'email' => 'guest@example.test',
            'organization' => 'Community Partner',
            'noted_signatory_id' => 'custom',
            'noted_signatory_custom' => 'Barangay Captain',
            'date_activity' => now()->addDays(40)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'facility_ids' => [$facility->id],
            'purpose' => 'Community assembly',
            'number_of_persons' => 50,
        ], $address));
    }
}
