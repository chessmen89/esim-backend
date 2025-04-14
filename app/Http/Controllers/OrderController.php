<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\AiraloService;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    protected AiraloService $airaloService;

    public function __construct(AiraloService $airaloService)
    {
        $this->airaloService = $airaloService;
    }

    /**
     * تخزين طلب جديد.
     *
     * العملية:
     * 1. التحقق من صحة البيانات المدخلة.
     * 2. التأكد من وجود مستخدم مصادق عليه (من التوكن).
     * 3. إنشاء سجل طلب محلي مبدئي في قاعدة البيانات بحالة "pending".
     * 4. إرسال الطلب إلى Airalo API.
     * 5. حال نجاح Airalo API: تحديث السجل المحلي مع بيانات Airalo وتغيير الحالة إلى "paid".
     *    إن فشل Airalo API: تحديث السجل المحلي لحالة "failed".
     * 6. إرجاع بيانات الطلب النهائي.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // الخطوة الأولى: التحقق من وجود مستخدم مصادق عليه
        $user = $request->user();
        if (!$user) {
            Log::error("محاولة إنشاء طلب بدون توكن");
            return response()->json([
                'status'  => 'error',
                'message' => 'غير مصرح: يرجى تسجيل الدخول.'
            ], 401);
        }
        Log::info("المستخدم المصادق عليه:", ['user_id' => $user->id]);

        // الخطوة الثانية: التحقق من صحة البيانات المُرسلة
        $validatedData = $request->validate([
            'quantity'            => 'required|integer|min:1|max:50',
            'package_id'          => 'required|string',
            'type'                => 'nullable|string',
            'description'         => 'nullable|string',
            'brand_settings_name' => 'nullable|string',
        ]);
        if (empty($validatedData['type'])) {
            $validatedData['type'] = 'sim';
        }
        
        Log::info("بيانات الطلب المُحققة:", $validatedData);

        // الخطوة الثالثة: إنشاء سجل طلب محلي مبدئي بحالة pending
        try {
            $localOrder = Order::create([
                'user_id'         => $user->id,
                'airalo_order_id' => null, // سيتم تحديثها لاحقاً
                'order_data'      => null, // سيتم تحديثها بعد استجابة Airalo
                'status'          => 'pending',
            ]);
            Log::info("تم إنشاء سجل الطلب المحلي بحالة pending", ['order_id' => $localOrder->id]);
        } catch (\Exception $e) {
            Log::error("خطأ أثناء إنشاء سجل الطلب المحلي: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'فشل إنشاء سجل الطلب المحلي.'
            ], 500);
        }

        // الخطوة الرابعة: إرسال الطلب إلى Airalo API
        $airaloResponse = $this->airaloService->createOrder($validatedData);
        if (!$airaloResponse) {
            $localOrder->status = 'failed';
            $localOrder->save();
            Log::error("فشل إنشاء الطلب عبر Airalo. تم تحديث حالة الطلب المحلي إلى failed.");
            return response()->json([
                'status'  => 'error',
                'message' => 'تعذر إنشاء الطلب عبر Airalo.'
            ], 500);
        }
        Log::info("تم استلام استجابة Airalo:", $airaloResponse);
        Log::info("نوع استجابة Airalo:", ['type' => gettype($airaloResponse)]);

        // إذا لم يكن airaloResponse مصفوفة، حاول تحويلها
        if (!is_array($airaloResponse)) {
            $converted = json_decode($airaloResponse, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $airaloResponse = $converted;
                Log::info("تم تحويل استجابة Airalo إلى مصفوفة.");
            } else {
                Log::error("فشل تحويل استجابة Airalo إلى مصفوفة.");
            }
        }

        // الخطوة الخامسة: تحديث السجل المحلي بناءً على استجابة Airalo وتغيير الحالة إلى paid
        try {
            $airaloOrderId = data_get($airaloResponse, 'data.data.id', null);
\Log::info("Extracted Airalo Order ID:", ['airalo_order_id' => $airaloOrderId]);
$localOrder->airalo_order_id = $airaloOrderId;

            $localOrder->order_data = $airaloResponse;
            $localOrder->status = 'paid'; // بما أننا نفترض الدفع افتراضيًا
            $localOrder->save();
            Log::info("تم تحديث سجل الطلب المحلي مع بيانات Airalo بنجاح", ['order' => $localOrder]);
        } catch (\Exception $e) {
            Log::error("خطأ أثناء تحديث سجل الطلب المحلي: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'فشل تحديث سجل الطلب المحلي.'
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Order created',
            'data'    => $localOrder,
        ]);
    }

    /**
     * استرجاع جميع الطلبات للمستخدم المصادق عليه.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير مصرح: يرجى تسجيل الدخول.'
            ], 401);
        }

        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الطلبات بنجاح.',
            'data'    => $orders,
        ]);
    }

    /**
     * استرجاع طلب محدد للمستخدم المصادق عليه.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'غير مصرح: يرجى تسجيل الدخول.'
            ], 401);
        }

        $order = Order::find($id);
        if (!$order || $order->user_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الطلب غير موجود أو الوصول غير مصرح به.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'تم استرجاع الطلب بنجاح.',
            'data'    => $order,
        ]);
    }
}
