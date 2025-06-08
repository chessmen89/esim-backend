<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\AiraloService;

class SimUsageController extends Controller
{
    protected AiraloService $airaloService;

    public function __construct(AiraloService $airaloService)
    {
        $this->airaloService = $airaloService;
    }

    public function show($iccid)
    {
        $cacheKey = "sim_usage_{$iccid}";

        // Check if result is cached
        if (Cache::has($cacheKey)) {
            return response()->json([
                'status' => 'cached',
                'data'   => Cache::get($cacheKey),
            ]);
        }

        $accessToken = $this->airaloService->getAccessToken();

        if (! $accessToken) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unable to authenticate with Airalo',
            ], 401);
        }

        $response = Http::withToken($accessToken)
            ->get(config('services.airalo.base_url') . "/sims/{$iccid}/usage");

        if ($response->status() === 429) {
            return response()->json([
                'status'       => 'error',
                'message'      => 'Rate limit exceeded. Try again later.',
                'retry_after'  => $response->header('Retry-After'),
            ], 429);
        }

        if (! $response->successful()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch usage data.',
                'details' => $response->json(),
            ], $response->status());
        }

        $data = $response->json();

        // Cache for 15 mins
        Cache::put($cacheKey, $data, now()->addMinutes(15));

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }
}