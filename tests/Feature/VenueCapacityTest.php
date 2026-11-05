<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Rules\WithinVenueCapacity;
use App\Support\VenueRates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VenueCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_equipment_matches_the_gsu_rate_sheet(): void
    {
        $this->assertSame(['qty_monobloc' => 700, 'qty_table' => 10, 'qty_fan' => 4, 'qty_sound' => 1], VenueRates::suggestedEquipmentFor('PAGHIUSA HALL'));
        $this->assertSame(['qty_monobloc' => 90, 'qty_table' => 2, 'qty_fan' => 0, 'qty_sound' => 0], VenueRates::suggestedEquipmentFor('BUSALIAN HALL'));
        $this->assertSame(['qty_monobloc' => 80, 'qty_table' => 2, 'qty_fan' => 2, 'qty_sound' => 0], VenueRates::suggestedEquipmentFor('CEA AVR'));
        $this->assertSame(600, VenueRates::seatingCapacityFor('PAGHIUSA HALL'));
    }

    public function test_system_names_resolve_to_rate_sheet_aliases(): void
    {
        $this->assertSame(80, VenueRates::seatingCapacityFor('ICT AVR'));
        $this->assertSame(2000.0, VenueRates::rateFor('ICT AVR'));
        $this->assertSame(1000.0, VenueRates::rateFor('GRAND STAND'));
        $this->assertSame(300.0, VenueRates::rateFor('COVERED GYM')); // treated as Open Space
        $this->assertSame(0, VenueRates::seatingCapacityFor('COVERED GYM'));
        $this->assertSame(600, VenueRates::seatingCapacityFor('TIRIPUNAN HALL'));
    }

    public function test_max_persons_is_the_sum_of_venue_maximums_and_open_venues_lift_the_cap(): void
    {
        $this->assertSame(140, VenueRates::maxPersonsFor(['BALAY NI JUAN', 'BUSALIAN HALL']));
        $this->assertNull(VenueRates::maxPersonsFor(['BALAY NI JUAN', 'TRACK OVAL']));
        $this->assertNull(VenueRates::maxPersonsFor([]));
    }

    public function test_rule_rejects_more_persons_than_the_venues_hold(): void
    {
        $balay = Facility::create(['name' => 'BALAY NI JUAN', 'location' => 'Campus', 'owner_type' => 'gsu', 'is_active' => true]);
        $check = fn (int $persons) => Validator::make(['n' => $persons], ['n' => [new WithinVenueCapacity([$balay->id])]]);

        $this->assertFalse($check(60)->fails());
        $this->assertTrue($check(61)->fails());
        $this->assertSame('The selected venue(s) can hold at most 60 persons.', $check(61)->errors()->first('n'));
    }
}
