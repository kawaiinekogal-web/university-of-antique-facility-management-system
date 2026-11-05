{{-- Venue capacity + GSU package (rate sheet) for venue cards: "Up to 600 persons" and short package tags
     ("700 chairs", "10 long tables", "Air-conditioned"); the rate sheet's exact wording is kept in each title. --}}
@php
    $range = \App\Support\VenueRates::estimatedSeatingCapacityFor($facility->name);
    $maxPersons = \App\Support\VenueRates::seatingCapacityFor($facility->name);
    $short = function (string $item): array {
        $tags = [];
        if (preg_match('/^(\d+)\s+monoblock chairs?/i', $item, $m)) $tags[] = "{$m[1]} chairs";
        elseif (preg_match('/^(\d+)\s+wooden long tables?/i', $item, $m)) $tags[] = "{$m[1]} long tables";
        elseif (preg_match('/^(\d+)\s+square plastic tables?/i', $item, $m)) $tags[] = "{$m[1]} square tables";
        elseif (preg_match('/^(\d+)\s+industrial fans?/i', $item, $m)) $tags[] = "{$m[1]} industrial fans";
        elseif (stripos($item, 'airconditioned') !== false) {
            $tags[] = 'Air-conditioned';
            if (stripos($item, 'sound') !== false) $tags[] = 'Sound system';
        } else $tags[] = $item;
        return $tags;
    };
    $package = collect(\App\Support\VenueRates::packageFor($facility->name))
        ->reject(fn ($item) => str_starts_with(strtolower($item), 'maximum of'))
        ->flatMap(fn ($item) => collect($short($item))->map(fn ($tag) => ['tag' => $tag, 'full' => $item]));
@endphp
<span class="vb-cap" @if ($range) title="Seating: {{ $range }}" @endif>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <span>{{ $maxPersons > 0 ? 'Up to ' . number_format($maxPersons) . ' persons' : ($range ?? 'Capacity not listed') }}</span>
</span>
@if ($package->isNotEmpty())
    <span class="vb-tags"><span class="sr-only">Includes: </span>@foreach ($package as $p)<span class="vb-tag" title="{{ $p['full'] }}">{{ $p['tag'] }}</span>@endforeach</span>
@endif
