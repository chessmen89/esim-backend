<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use Illuminate\Support\Str;
use App\Services\HesabeService;

class OrderController extends Controller
{
    protected $hesabe;

    public function __construct(HesabeService $hesabe)
    {
        $this->hesabe = $hesabe;
    }

    /**
     * POST  /api/orders
     * Create a local "pending" order (no call to Airalo yet).
     */
    public function store(Request $request)
    {
        // 1) Auth
        $user = $request->user();
        if (! $user) {
            Log::error('Attempt to create order without auth');
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // 2) Validate
        $data = $request->validate([
            'package_id' => 'required|string',
            'quantity'   => 'required|integer|min:1|max:50',
            'type'       => 'nullable|string',
        ]);
        $data['type'] = $data['type'] ?? 'sim';

        // 3) Determine amount (replace with your real pricing logic)
        $airaloUsdPrice = 9.5; 
        $kwdAmount      = round($airaloUsdPrice * 0.30, 3);

        // 4) Create reference number
        $referenceNumber = 'ORD-' . strtoupper(Str::random(10));
        Log::debug("Step 1 - Generated Reference Number: {$referenceNumber}");

        // 5) Create local pending order with correct reference number
        $order = Order::create([
            'user_id'          => $user->id,
            'package_id'       => $data['package_id'],
            'quantity'         => $data['quantity'],
            'type'             => $data['type'],
            'status'           => 'pending',
            'amount'           => $kwdAmount,
            'currency'         => 'KWD',
            'airalo_order_id'  => null,
            'order_data'       => null,
            'reference_number' => $referenceNumber,
        ]);
        Log::debug("Step 2 - Saved Order ID: {$order->id}, Reference Number in DB: {$order->reference_number}");


        // 6) Build payment payload using orderReferenceNumber officially
        $paymentData = [
            'reference_number'       => $referenceNumber, // نستخدم هذا لتمريره داخل initiatePayment
            'merchantCode'           => config('hesabe.merchant_code'),
            'access_code'            => config('hesabe.access_code'),
            'amount'                 => $order->amount,
            'currency'               => $order->currency ?? 'KWD',
            'responseUrl'            => route('payment.verify'),
            'failureUrl'             => route('payment.verify'),
            'paymentType'            => '0',
            'version'                => '2.0',
            'orderReferenceNumber'   => $referenceNumber, // هذا الحقل المعتمد من Hesabe
            'variable1'              => $referenceNumber, // احتياطي لتتبع إضافي فقط
        ];
        Log::debug("Step 3 - Sending Reference Number to initiatePayment: {$paymentData['reference_number']}");

        try {
            $paymentUrl = $this->hesabe->initiatePayment($paymentData);
        } catch (\Exception $e) {
            Log::error('Payment initiation failed', ['err' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to generate payment link.',
            ], 500);
        }

        // 7) Return both order and payment URL
        return response()->json([
            'status'      => 'success',
            'message'     => 'Order created with payment link.',
            'data'        => $order,
            'payment_url' => $paymentUrl,
        ], 201);
    }

    /**
     * GET  /api/orders
     * List all orders for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Orders retrieved successfully.',
            'data'    => $orders,
        ], 200);
    }

    /**
     * GET  /api/orders/{id}
     * Retrieve a single order by ID (must belong to you).
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = Order::find($id);
        if (! $order || $order->user_id !== $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Order not found or access denied.',
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Order retrieved successfully.',
            'data'    => $order,
        ], 200);
    }
}