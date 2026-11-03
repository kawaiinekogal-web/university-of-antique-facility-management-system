<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressLookupController extends Controller
{
    private const API = 'https://psgc.gitlab.io/api';

    public function provinces(): JsonResponse
    {
        return $this->cached('provinces', '/provinces/');
    }

    public function cities(string $provinceCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{9}$/', $provinceCode), 404);

        $cities = $this->all('cities-municipalities');

        return response()->json(collect($cities)->where('provinceCode', $provinceCode)->values());
    }

    public function barangays(string $cityCode): JsonResponse
    {
        abort_unless(preg_match('/^\d{9}$/', $cityCode), 404);

        $barangays = $this->all('barangays');

        return response()->json(collect($barangays)->filter(fn (array $item) => ($item['cityCode'] ?? false) === $cityCode || ($item['municipalityCode'] ?? false) === $cityCode)->values());
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
            $response->throw();

            return collect($response->json())
                ->map(fn (array $item) => collect($item)->only(['code', 'name', 'provinceCode', 'cityCode', 'municipalityCode'])->all())
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }
}
