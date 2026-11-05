<?php

namespace App\Support;

class VenueRates
{
    public static function all(): array
    {
        return config('venue-rates', []);
    }

    /** The rate-sheet row for a facility, matched by name or one of its aliases (case/punctuation-insensitive). */
    public static function find(string $facilityName): ?array
    {
        $normalize = static fn (string $name): string => preg_replace('/[^a-z0-9]+/', '', strtolower(trim($name)));
        $target = $normalize($facilityName);

        foreach (self::all() as $venue) {
            foreach ([$venue['name'], ...($venue['aliases'] ?? [])] as $name) {
                if ($normalize($name) === $target) {
                    return $venue;
                }
            }
        }

        return null;
    }

    public static function estimatedSeatingCapacityFor(string $facilityName): ?string
    {
        $capacity = trim((string) (self::find($facilityName)['capacity'] ?? ''));

        return $capacity === '' ? null : ($capacity === '-' ? 'No fixed seating capacity' : $capacity);
    }

    /** Upper bound of the listed seating ("500-600 persons" -> 600); 0 when the venue has no fixed seating. */
    public static function seatingCapacityFor(string $facilityName): int
    {
        preg_match_all('/\d+/', (string) self::estimatedSeatingCapacityFor($facilityName), $numbers);

        return $numbers[0] ? max(array_map('intval', $numbers[0])) : 0;
    }

    /**
     * Most persons allowed for the chosen venues: the sum of their seating maximums. Null (no limit) when any
     * chosen venue has no fixed seating on the rate sheet (open spaces, track oval, unlisted venues).
     */
    public static function maxPersonsFor(iterable $facilityNames): ?int
    {
        $total = 0;
        foreach ($facilityNames as $name) {
            $capacity = self::seatingCapacityFor((string) $name);
            if ($capacity === 0) {
                return null;
            }
            $total += $capacity;
        }

        return $total ?: null;
    }

    /** Package items for the venue as printed on the rate sheet, e.g. ['700 monoblock chairs', ...]. */
    public static function packageFor(string $facilityName): array
    {
        return self::find($facilityName)['package'] ?? [];
    }

    /**
     * Equipment the venue's package provides, keyed by the request form's qty_* inputs
     * ("700 monoblock chairs" -> qty_monobloc, "10 wooden long tables" -> qty_table, "4 Industrial Fan" -> qty_fan,
     * "w/sounds" -> qty_sound). Pre-fills the form; the requester can still change the quantities.
     */
    public static function suggestedEquipmentFor(string $facilityName): array
    {
        $suggested = ['qty_monobloc' => 0, 'qty_table' => 0, 'qty_fan' => 0, 'qty_sound' => 0];

        foreach (self::packageFor($facilityName) as $item) {
            if (preg_match('/^(\d+)\s.*chairs?/i', $item, $m)) {
                $suggested['qty_monobloc'] += (int) $m[1];
            } elseif (preg_match('/^(\d+)\s.*tables?/i', $item, $m)) {
                $suggested['qty_table'] += (int) $m[1];
            } elseif (preg_match('/^(\d+)\s.*fan/i', $item, $m)) {
                $suggested['qty_fan'] += (int) $m[1];
            } elseif (stripos($item, 'sound') !== false) {
                $suggested['qty_sound'] = 1;
            }
        }

        return $suggested;
    }

    /** Peso amount from the rate ("PhP 15,000.00" -> 15000.0); null when the venue is not on the rate sheet. */
    public static function rateFor(string $facilityName): ?float
    {
        $rate = self::find($facilityName)['rate'] ?? null;

        return $rate !== null && preg_match('/PhP\s*([\d,.]+)/i', $rate, $m) ? (float) str_replace(',', '', $m[1]) : null;
    }

    /** True when the venue is charged per person ("PhP 50.00/head in a day", classrooms), not per venue. */
    public static function isPerHead(string $facilityName): bool
    {
        return stripos(self::find($facilityName)['rate'] ?? '', '/head') !== false;
    }

    /**
     * Fee for one day of the venue: the rate, or rate × persons for per-head venues.
     * Null when the venue has no published rate, or is per-head and the number of persons is unknown.
     */
    public static function dailyFeeFor(string $facilityName, ?int $persons): ?float
    {
        $rate = self::rateFor($facilityName);
        if ($rate === null || ! self::isPerHead($facilityName)) {
            return $rate;
        }

        return $persons ? $rate * $persons : null;
    }

    /** Display label for guests ("₱ 15,000.00 / day"); null when the venue is not on the rate sheet. */
    public static function rateLabelFor(string $facilityName): ?string
    {
        $rate = self::find($facilityName)['rate'] ?? null;
        if ($rate === null) {
            return null;
        }
        $rate = str_replace('PhP', '₱', $rate);

        return preg_match('/(?:\/\s*day|in a day)/i', $rate) ? $rate : $rate . ' / day';
    }
}
