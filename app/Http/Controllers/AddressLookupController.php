<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressLookupController extends Controller
{
    private const API = 'https://psgc.gitlab.io/api';

    /** Requests are accepted from Region VI (Western Visayas) only. */
    private const REGION = '/regions/060000000';

    /** Region VI provinces as PSGC names them; also the server-side allow-list for the address field. */
    public const PROVINCES = ['Aklan', 'Antique', 'Capiz', 'Guimaras', 'Iloilo', 'Negros Occidental'];

    public function provinces(): JsonResponse
    {
        return $this->cached('region6:provinces', self::REGION . '/provinces/');
    }

    public function cities(string $provinceCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{9}$/', $provinceCode), 404);

        return response()->json(collect($this->regionCities())->where('provinceCode', $provinceCode)->values());
    }

    public function barangays(string $cityCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{9}$/', $cityCode), 404);
        abort_unless(collect($this->regionCities())->contains('code', $cityCode), 404); // Region VI towns only

        // One town's list only: the nationwide list is ~11 MB and outlasts the request timeout
        return $this->cached('barangays:' . $cityCode, '/cities-municipalities/' . $cityCode . '/barangays/');
    }

    private function regionCities(): array
    {
        return $this->all('region6:cities', self::REGION . '/cities-municipalities/');
    }

    private function cached(string $key, string $path): JsonResponse
    {
        return response()->json($this->all($key, $path));
    }

    private function all(string $key, ?string $path = null): array
    {
        return Cache::remember('psgc:' . $key, now()->addDays(7), function () use ($key, $path) {
            $path ??= '/' . $key . '/';
            $response = Http::acceptJson()->timeout(10)->get(self::API . $path);
            abort_if($response->notFound(), 404);
            $response->throw();

            return collect($response->json())
                ->map(fn (array $item) => collect($item)->only(['code', 'name', 'provinceCode', 'cityCode', 'municipalityCode'])->all())
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }
}
