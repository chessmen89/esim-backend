<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiraloService
{
    /**
     * The base URL for the Airalo API.
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
     * @return string|null The access token, or null on failure.
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'airalo_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

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
                if (isset($data['data']['access_token'])) {
                    $accessToken = $data['data']['access_token'];
                    $expiresIn   = $data['data']['expires_in'] ?? 31536000; // Default 1 year
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
     * You can pass an optional array of query parameters for filtering.
     *
     * @param array $queryParams Optional query parameters.
     * @return array|null
     */
    public function getPackages(array $queryParams = []): ?array
    {
        $endpoint = $this->baseUrl . '/packages';

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
     * Create a new eSIM order via the Airalo API.
     *
     * @param array $orderData
     * @return array|null
     */
    public function createOrder(array $orderData): ?array
    {
        $endpoint = $this->baseUrl . '/orders';
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
     * Retrieve global eSIM packages from the Airalo API.
     *
     * This method calls the global endpoint exactly (without appending a region)
     * and returns the raw response.
     *
     * @return array|null The JSON response from Airalo.
     */
    public function getGlobalPackages(): ?array
    {
        $endpoint = $this->baseUrl . '/packages/global';
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
                return $response->json(); // Expected to contain keys: status, message, data.
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
}
