<?php

namespace Tests\Feature;

use App\Models\Facility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestVenueRatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_venue_choices_show_the_published_rate_and_unit(): void
    {
        Facility::create([
            'name' => 'PaghiUsA Hall',
            'location' => 'Main Campus',
            'owner_type' => 'gsu',
            'is_active' => true,
            'availability_status' => 'available',
        ]);

        $this->get(route('public.request.create'))
            ->assertOk()
            ->assertSee('PaghiUsA Hall')
            ->assertSee('₱ 15,000.00 / day')
            ->assertSee('Estimated seating capacity: 500-600 persons');

        $this->get(route('public.rates'))
            ->assertOk()
            ->assertSee('Estimated seating capacity: 500-600 persons');
    }
}
