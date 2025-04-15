<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;

class TripController extends Controller
{
    /**
     * إرجاع جميع الرحلات.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $trips = Trip::all();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع جميع الرحلات بنجاح.',
            'data'    => $trips
        ], 200);
    }

    /**
     * إرجاع الرحلات حسب كود الدولة.
     *
     * @param  string  $country_code
     * @return \Illuminate\Http\JsonResponse
     */
    public function tripsByCountry($country_code)
    {
        $trips = Trip::where('country_code', strtoupper($country_code))->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الرحلات للدولة ' . strtoupper($country_code) . ' بنجاح.',
            'data'    => $trips
        ], 200);
    }

    /**
     * إرجاع رحلة برقم الـ ID.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $trip = Trip::find($id);

        if (!$trip) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الرحلة غير موجودة.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الرحلة بنجاح.',
            'data'    => $trip
        ], 200);
    }

    /**
     * إنشاء باقة جديدة.
     *
     * جميع الحقول مطلوبة:
     * - name: اسم الرحلة.
     * - pdf_path: مسار ملف PDF للرحلة.
     * - image_path: مسار صورة الرحلة.
     * - country_code: كود الدولة.
     * - price: سعر الرحلة.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validatedData = $request->validate([
            'name'         => 'required|string',
            'pdf_path'     => 'required|string',
            'image_path'   => 'required|string',
            'country_code' => 'required|string|max:10',
            'price'        => 'required|numeric',
        ]);

        // إنشاء سجل الباقة في قاعدة البيانات
        $trip = Trip::create($validatedData);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم إنشاء الباقة بنجاح.',
            'data'    => $trip
        ], 201);
    }
}
