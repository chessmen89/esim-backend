<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OrderController extends Controller
{
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
        //    For example, if Airalo price is in USD and you convert at 0.30:
        $airaloUsdPrice = 9.5; 
        $kwdAmount      = round($airaloUsdPrice * 0.30, 3);

      // 4) Create local pending order
$order = Order::create([
    'user_id'         => $user->id,
    'package_id'      => $data['package_id'],
    'quantity'        => $data['quantity'],
    'type'            => $data['type'],
    'status'          => 'pending',
    'amount'          => $kwdAmount,
    'currency'        => 'KWD',
    'airalo_order_id' => null,
    'order_data'      => null,
]);

// ✅ Add reference_number and save again
$order->reference_number = 'ORD-' . $order->id;
$order->save();

        Log::info("Created local pending order", ['order_id' => $order->id]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order created, awaiting payment.',
            'data'    => $order,
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