<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiraloService
{
    /**
     * The base URL for the Airalo API (used for packages/orders).
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * The client ID for Airalo.
     *
     * @var string|null
     */
    protected ?string $clientId;

    /**
     * The client secret for Airalo.
     *
     * @var string|null
     */
    protected ?string $clientSecret;

    /**
     * AiraloService constructor.
     *
     * Retrieves configuration values from config/services.php.
     */
    public function __construct()
    {
        $this->baseUrl = config('services.airalo.base_url');
        $this->clientId = config('services.airalo.client_id');
        $this->clientSecret = config('services.airalo.client_secret');
    }

    /**
     * Obtain an access token from Airalo using client credentials.
     *
     * This endpoint is rate-limited (max 5 requests per minute).
     * We use Laravel's cache to store the token for its lifetime.
     *
     * @return string|null The access token, or null on failure.
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'airalo_access_token';

        // Return the token if it's already cached.
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Token endpoint (correct URL per documentation).
        $tokenUrl = "https://sandbox-partners-api.airalo.com/v2/token";

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->asForm()->post($tokenUrl, [
                'client_id'     => trim($this->clientId),
                'client_secret' => trim($this->clientSecret),
                'grant_type'    => 'client_credentials',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // The response structure is nested:
                // {
                //     "data": {
                //         "access_token": "...",
                //         "expires_in": 86400
                //     },
                //     "meta": { ... }
                // }
                if (isset($data['data']) && isset($data['data']['access_token'])) {
                    $accessToken = $data['data']['access_token'];
                    $expiresIn = $data['data']['expires_in'] ?? 31536000; // Default to 1 year.
                    Cache::put($cacheKey, $accessToken, now()->addSeconds($expiresIn));
                    return $accessToken;
                }

                Log::error("Airalo token response missing 'access_token'", ['data' => $data]);
                return null;
            }

            Log::error("Airalo getAccessToken error", [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Exception in getAccessToken: " . $e->getMessage());
            return null;
        }
    }

    /**
 * Retrieve available eSIM packages from the Airalo API.
 *
 * Optionally pass an array of query parameters (e.g., for filtering or pagination).
 *
 * @param array $queryParams Optional query parameters.
 * @return array|null
 */
public function getPackages(array $queryParams = []): ?array
{
    $endpoint = $this->baseUrl . '/packages';

    // Retrieve the access token.
    $accessToken = $this->getAccessToken();
    if (!$accessToken) {
        Log::error("Unable to obtain access token in getPackages.");
        return null;
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept'        => 'application/json',
        ])->get($endpoint, $queryParams);

        if ($response->successful()) {
            $json = $response->json();
            // If a top-level "data" key exists, use that; otherwise, return the full JSON.
            return $json['data'] ?? $json;
        }

        Log::error('Airalo API error in getPackages', [
            'status'   => $response->status(),
            'response' => $response->body(),
        ]);
        return null;
    } catch (\Exception $e) {
        Log::error('Exception in getPackages: ' . $e->getMessage());
        return null;
    }
}

 /**
     * Retrieve available global eSIM packages from the Airalo API.
     *
     * تُستدعى هذه الدالة للحصول على رد الباقات العالمية من نقطة النهاية
     * الخاصة بها ("/packages/global")، ثم سيتم استخدام هذا الرد لاحقاً في
     * الكنترولر لاستخراج بيانات القارات.
     *
     * @return array|null
     */
    public function getGlobalPackages(): ?array
    {
        $endpoint = $this->baseUrl . '/packages?filter[type]=global';

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error("Unable to obtain access token in getGlobalPackages.");
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ])->get($endpoint);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Airalo API error in getGlobalPackages", [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Exception in getGlobalPackages: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Create a new eSIM order via the Airalo API.
     *
     * @param array $orderData
     * @return array|null
     */
    public function createOrder(array $orderData): ?array
    {
        $endpoint = $this->baseUrl . '/orders';

        // Retrieve the access token.
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error("Unable to obtain access token in createOrder.");
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept'        => 'application/json',
            ])->post($endpoint, $orderData);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Airalo API error in createOrder', [
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception in createOrder: ' . $e->getMessage());
            return null;
        }
    }

    /**
 * Extract a unique list of countries (with details) from the available eSIM packages.
 *
 * Each country entry will include:
 *  - slug
 *  - country_code
 *  - title
 *  - image (an associative array with width, height, and url)
 *
 * @return array|null Returns an array of unique country data or null on failure.
 */
public function getCountriesFromPackages(): ?array
{
    $packages = $this->getPackages();

    if (is_null($packages)) {
        Log::error('Unable to fetch packages to extract countries.');
        return null;
    }

    $countries = [];
    foreach ($packages as $package) {
        // Check if all the required keys are available in the package.
        if (
            isset($package['slug']) &&
            isset($package['country_code']) &&
            isset($package['title']) &&
            isset($package['image']) &&
            !empty($package['slug']) &&
            !empty($package['country_code']) &&
            !empty($package['title'])
        ) {
            $slug = $package['slug'];
            // Use the slug as the unique key to prevent duplicates.
            if (!isset($countries[$slug])) {
                $countries[$slug] = [
                    'slug'         => $package['slug'],
                    'country_code' => $package['country_code'],
                    'title'        => $package['title'],
                    'image'        => $package['image']
                ];
            }
        }
    }
    // Return a zero-indexed array of country data.
    return array_values($countries);
}

/**
 * Retrieve global regions from the available eSIM packages.
 *
 * This method does not require a country_code to be non-empty.
 *
 * @return array|null Returns an array of unique region data or null on failure.
 */
public function getGlobalRegions(): ?array
{
    $packages = $this->getPackages();
    if (is_null($packages)) {
        Log::error('Unable to fetch packages to extract global regions.');
        return null;
    }

    $regions = [];
    foreach ($packages as $package) {
        // Check for required keys: slug, title, and image.
        if (isset($package['slug'], $package['title'], $package['image'])) {
            $slug = $package['slug'];
            // Use the slug as the unique key.
            if (!isset($regions[$slug])) {
                $regions[$slug] = [
                    'slug'         => $package['slug'],
                    // Even if country_code is empty, we still include the region.
                    'country_code' => $package['country_code'] ?? '',
                    'title'        => $package['title'],
                    'image'        => $package['image']
                ];
            }
        }
    }

    return array_values($regions);
}

/**
 * Retrieve packages for a given global region (identified by its slug).
 *
 * @param string $regionSlug The slug of the region (e.g., "asia").
 * @return array|null Returns an array of packages for that region or null on failure.
 */
public function getPackagesByRegion(string $regionSlug): ?array
{
    $packages = $this->getPackages();
    if (is_null($packages)) {
        Log::error('Unable to fetch packages for region filtering.');
        return null;
    }

    // Filter packages where the region slug matches (case-insensitive)
    $filtered = array_filter($packages, function ($package) use ($regionSlug) {
        return isset($package['slug']) && strtolower($package['slug']) === strtolower($regionSlug);
    });

    return array_values($filtered);
}


}
