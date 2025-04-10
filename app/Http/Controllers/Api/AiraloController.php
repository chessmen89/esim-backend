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
        $validatedData = $request->validate([
            'package_id' => 'required|string',
            'user_id'    => 'required|integer',
            // Add additional validation rules as needed.
        ]);

        $order = $this->airaloService->createOrder($validatedData);

        if (is_null($order)) {
            return response()->json(['error' => 'Unable to create order via Airalo'], 500);
        }

        return response()->json($order);
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
}
