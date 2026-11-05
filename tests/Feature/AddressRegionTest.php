<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AddressRegionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'psgc.gitlab.io/api/regions/060000000/provinces/' => Http::response([
                ['code' => '060600000', 'name' => 'Antique'], ['code' => '063000000', 'name' => 'Iloilo'],
            ]),
            'psgc.gitlab.io/api/regions/060000000/cities-municipalities/' => Http::response([
                ['code' => '060604000', 'name' => 'Barbaza', 'provinceCode' => '060600000'],
            ]),
            'psgc.gitlab.io/api/cities-municipalities/060604000/barangays/' => Http::response([
                ['code' => '060604001', 'name' => 'Bahuyan'],
            ]),
        ]);
    }

    public function test_address_lookup_only_serves_western_visayas(): void
    {
        $this->getJson('/api/addresses/provinces')->assertOk()->assertJsonCount(2)->assertJsonPath('0.name', 'Antique');
        $this->getJson('/api/addresses/cities/060600000')->assertOk()->assertJsonPath('0.name', 'Barbaza');
        $this->getJson('/api/addresses/barangays/060604000')->assertOk()->assertJsonPath('0.name', 'Bahuyan');
        // Quezon City (NCR) is outside Region VI
        $this->getJson('/api/addresses/barangays/137404000')->assertNotFound();
    }

    public function test_requests_from_outside_region_vi_are_rejected(): void
    {
        $this->submit('Cebu')->assertSessionHasErrors(['province' => 'Requests are accepted from Western Visayas (Region VI) only: Aklan, Antique, Capiz, Guimaras, Iloilo or Negros Occidental.']);
        $this->assertFalse(FormSubmission::where('payload->email', 'region@example.test')->exists());

        $this->submit('Antique')->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(FormSubmission::where('payload->email', 'region@example.test')->exists());
    }

    private function submit(string $province)
    {
        Mail::fake();
        $facility = Facility::firstOrCreate(['name' => 'Region Test Hall', 'owner_type' => 'gsu', 'owner_college' => null],
            ['location' => 'Test Campus', 'is_active' => true, 'availability_status' => 'available']);

        return $this->post('/request-facility', [
            'client_name' => 'Maria Jo', 'last_name' => 'Dela Cruz', 'contact_number' => '0917-123-4567',
            'email' => 'region@example.test', 'organization' => 'Community Partner',
            'noted_signatory_id' => 'custom', 'noted_signatory_custom' => 'Barangay Captain',
            'street_address' => 'Purok 3', 'barangay' => 'Bahuyan', 'city_municipality' => 'Barbaza',
            'province' => $province, 'country' => 'Philippines',
            'date_activity' => now()->addDays(40)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'facility_ids' => [$facility->id], 'purpose' => 'Community assembly', 'number_of_persons' => 50,
        ]);
    }
}
