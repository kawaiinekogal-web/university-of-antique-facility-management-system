<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressLookupTest extends TestCase
{
    public function test_barangays_are_fetched_for_one_town_only_and_cached(): void
    {
        Cache::flush();
        Http::fake([
            'psgc.gitlab.io/api/cities-municipalities/060601000/barangays/' => Http::response([
                ['code' => '060601002', 'name' => 'Bayo Grande', 'municipalityCode' => '060601000', 'extra' => 'x'],
                ['code' => '060601001', 'name' => 'Banago', 'municipalityCode' => '060601000'],
            ]),
            'psgc.gitlab.io/api/cities-municipalities/999999999/barangays/' => Http::response([], 404),
        ]);

        $this->getJson('/api/addresses/barangays/060601000')->assertOk()
            ->assertExactJson([
                ['code' => '060601001', 'name' => 'Banago', 'municipalityCode' => '060601000'],
                ['code' => '060601002', 'name' => 'Bayo Grande', 'municipalityCode' => '060601000'],
            ]);
        $this->getJson('/api/addresses/barangays/060601000')->assertOk(); // served from cache
        $this->getJson('/api/addresses/barangays/999999999')->assertNotFound();

        Http::assertSentCount(2); // never the nationwide /barangays/ list
    }
}
