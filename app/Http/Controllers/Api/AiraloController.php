<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiraloService;

class AiraloController extends Controller
{
    protected AiraloService $airaloService;

    public function __construct(AiraloService $airaloService)
    {
        $this->airaloService = $airaloService;
    }

    /**
     * Retrieve available eSIM packages from Airalo.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPackages()
    {
        $packages = $this->airaloService->getPackages();

        if (is_null($packages)) {
            return response()->json(['error' => 'Unable to fetch packages from Airalo'], 500);
        }

        return response()->json($packages);
    }

    /**
     * Create a new eSIM order via Airalo.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
{
    // This will work whether the client sends JSON or form data.
    $validatedData = $request->validate([
        'quantity'            => 'required|integer|min:1|max:50',
        'package_id'          => 'required|string',
        'type'                => 'nullable|string',
        'description'         => 'nullable|string',
        'brand_settings_name' => 'nullable|string',
    ]);

    // If 'type' is not provided, default to "sim"
    if (empty($validatedData['type'])) {
        $validatedData['type'] = 'sim';
    }

    // Call Airalo API to create the order using our service
    $order = $this->airaloService->createOrder($validatedData);

    if (is_null($order)) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Unable to create order via Airalo'
        ], 500);
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Order created successfully.',
        'data'    => $order,
    ]);
}


    /**
     * Retrieve a unique list of countries extracted from the packages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listCountriesFromPackages()
    {
        $countries = $this->airaloService->getCountriesFromPackages();

        if (is_null($countries)) {
            return response()->json(['error' => 'Unable to fetch countries from Airalo packages'], 500);
        }

        return response()->json($countries);
    }
    /**
 * Retrieve packages filtered by type and country.
 *
 * @param string $type    The package type (e.g., "local", "global").
 * @param string $country The country code (e.g., "TR", "US").
 * @return \Illuminate\Http\JsonResponse
 */
public function listPackagesByTypeAndCountry(string $type, string $country)
{
    // Build query parameters using the provided type and country.
    $queryParams = [
        'filter[type]'    => $type,
        'filter[country]' => strtoupper($country)  // Ensure country code is uppercase.
    ];

    $packages = $this->airaloService->getPackages($queryParams);

    if (is_null($packages)) {
        return response()->json([
            'status'  => 'error',
            'message' => "Unable to fetch packages for type: {$type} and country: {$country}"
        ], 500);
    }

    return response()->json([
        'status'  => 'success',
        'message' => "Packages for type {$type} and country {$country} retrieved successfully.",
        'data'    => $packages
    ]);
}
}
