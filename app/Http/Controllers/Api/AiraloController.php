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
     * GET endpoint (for non-filtered access)
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

        // Call Airalo API to create the order using our service.
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
     * Retrieve global packages.
     *
     * This method calls getPackages() using a filter for "global" packages.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listGlobalPackages()
    {
        $queryParams = [
            'filter[type]' => 'global'
        ];

        $packages = $this->airaloService->getPackages($queryParams);

        if (is_null($packages)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر جلب الباقات العالمية من Airalo'
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الباقات العالمية بنجاح',
            'data'    => $packages
        ]);
    }

    /**
     * POST endpoint to retrieve packages for a given global region.
     * 
     * The client should send a JSON body with a "slug" parameter 
     * (e.g., {"slug": "asia"}). This method retrieves global packages
     * from Airalo and then filters the response by the provided slug.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPackagesByRegion(Request $request)
    {
        // Validate that 'slug' is provided in the body.
        $request->validate([
            'slug' => 'required|string'
        ]);

        $regionSlug = trim($request->input('slug'));

        // Get global packages from Airalo using the "global" type filter.
        $globalPackages = $this->airaloService->getPackages([
            'filter[type]' => 'global'
        ]);

        if (is_null($globalPackages)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر جلب الباقات العالمية من Airalo'
            ], 500);
        }

        $foundRegion = null;
        // Search for the region by slug (case-insensitive).
        foreach ($globalPackages as $region) {
            if (isset($region['slug']) && strtolower(trim($region['slug'])) === strtolower($regionSlug)) {
                $foundRegion = $region;
                break;
            }
        }

        if (!$foundRegion) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لم يتم العثور على قارة بهذا الاسم: ' . $regionSlug
            ], 404);
        }

        // Merge all packages from the operators of the found region.
        $packages = [];
        if (isset($foundRegion['operators']) && is_array($foundRegion['operators'])) {
            foreach ($foundRegion['operators'] as $operator) {
                if (isset($operator['packages']) && is_array($operator['packages'])) {
                    $packages = array_merge($packages, $operator['packages']);
                }
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الباقات لقارة ' . $regionSlug . ' بنجاح',
            'data'    => $packages
        ]);
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
        $queryParams = [
            'filter[type]'    => $type,
            'filter[country]' => strtoupper($country)
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

    /**
 * استخراج القارات فقط من رد الباقات العالمية وإرجاعها.
 *
 * سيتم استخراج بيانات كل عنصر من الـ data بحيث نقوم بإرجاع:
 * - slug
 * - country_code
 * - title
 * - image
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function listRegions()
{
    // استدعاء الدالة الخاصة بالباقات العالمية من خدمات Airalo
    $globalResponse = $this->airaloService->getGlobalPackages();

    // التأكد من صحة الرد وبنية البيانات
    if (is_null($globalResponse) || !isset($globalResponse['data']) || !is_array($globalResponse['data'])) {
        return response()->json([
            'status'  => 'error',
            'message' => 'تعذر جلب الباقات العالمية'
        ], 500);
    }

    // استخراج القارات فقط باستخدام البيانات الأساسية لكل قارة
    $regions = array_map(function ($region) {
        return [
            'slug'         => $region['slug'] ?? null,
            'country_code' => $region['country_code'] ?? '',
            'title'        => $region['title'] ?? null,
            'image'        => $region['image'] ?? null,
        ];
    }, $globalResponse['data']);

    return response()->json([
        'status'  => 'success',
        'message' => 'تم استرجاع القارات بنجاح',
        'data'    => $regions
    ], 200);
}

}
