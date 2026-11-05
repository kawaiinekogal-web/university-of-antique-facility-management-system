<?php

namespace App\Rules;

use App\Models\Facility;
use App\Support\VenueRates;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Number of persons may not exceed the combined seating maximum of the chosen venues (GSU rate sheet). */
class WithinVenueCapacity implements ValidationRule
{
    public function __construct(private mixed $facilityIds)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($this->facilityIds) || ! is_numeric($value)) {
            return; // facility_ids / integer rules report those errors
        }

        $names = Facility::whereIn('id', $this->facilityIds)->pluck('name');
        $max = VenueRates::maxPersonsFor($names);

        if ($max !== null && (int) $value > $max) {
            $fail("The selected venue(s) can hold at most {$max} persons.");
        }
    }
}
