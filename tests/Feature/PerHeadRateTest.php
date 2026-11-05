<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FormSubmission;
use App\Support\VenueRates;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** Classrooms are rated "PhP 50.00/head in a day": the fee is rate × persons × days, not a flat ₱50. */
class PerHeadRateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_daily_fee_multiplies_per_head_rates_by_persons(): void
    {
        $this->assertTrue(VenueRates::isPerHead('CLASS ROOMS'));
        $this->assertSame(1500.0, VenueRates::dailyFeeFor('CLASS ROOMS', 30));
        $this->assertNull(VenueRates::dailyFeeFor('CLASS ROOMS', null)); // unknown attendance: no amount yet
        $this->assertFalse(VenueRates::isPerHead('PAGHIUSA HALL'));
        $this->assertSame(15000.0, VenueRates::dailyFeeFor('PAGHIUSA HALL', 300)); // flat rate ignores persons
    }

    public function test_rental_quote_charges_classrooms_per_person_per_day(): void
    {
        $classroom = $this->facility('CLASS ROOMS');
        $hall = $this->facility('BALAY NI JUAN');

        $quote = $this->quote([$classroom->id, $hall->id], 30, '2026-12-14', '2026-12-15');

        $this->assertSame('30', $quote['rows'][0]['quantity']);
        $this->assertSame(3000.0, $quote['rows'][0]['total']); // 50 × 30 persons × 2 days
        $this->assertSame(16000.0, $quote['rows'][1]['total']); // 8,000 × 2 days
        $this->assertSame(19000.0, $quote['total']);
        $this->assertTrue($quote['complete']);
    }

    public function test_classroom_quote_without_a_persons_count_is_incomplete_not_fifty_pesos(): void
    {
        $quote = $this->quote([$this->facility('CLASS ROOMS')->id], null, '2026-12-14');

        $this->assertNull($quote['rows'][0]['total']);
        $this->assertSame(0.0, (float) $quote['total']);
        $this->assertFalse($quote['complete']);
    }

    private function facility(string $name): Facility
    {
        return Facility::create(['name' => $name, 'location' => 'Main Campus', 'owner_type' => 'gsu', 'is_active' => true]);
    }

    private function quote(array $facilityIds, ?int $persons, string $date, ?string $end = null): array
    {
        return (new FormSubmission(['payload' => array_filter([
            'facility_ids' => $facilityIds, 'number_of_persons' => $persons,
            'date_activity' => $date, 'date_end' => $end, 'time_range' => ['start' => '08:00', 'end' => '12:00'],
        ], fn ($v) => $v !== null)]))->rentalQuote();
    }
}
