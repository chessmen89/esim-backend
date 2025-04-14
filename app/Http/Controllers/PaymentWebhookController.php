<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle the payment callback and update the order status.
     *
     * This endpoint should be secured with a shared secret or signature verification.
     * It receives a payload with:
     * - order_id (local order ID)
     * - payment_status (expected values: "paid" or "failed")
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePaymentCallback(Request $request)
    {
        // Validate the payload (modify according to your payment gateway)
        $validated = $request->validate([
            'order_id'       => 'required|integer',
            'payment_status' => 'required|string|in:paid,failed',
            // Add any additional fields or signature verification as needed.
        ]);

        // Find the local order by id.
        $order = Order::find($validated['order_id']);
        if (!$order) {
            Log::error("Payment callback: order not found", ['order_id' => $validated['order_id']]);
            return response()->json(['status' => 'error', 'message' => 'Order not found.'], 404);
        }

        // Update the order status based on the payment status.
        // If payment_status is "paid", update the order status to "paid".
        // If it's "failed", update to "canceled".
        $newStatus = $validated['payment_status'] === 'paid' ? 'paid' : 'canceled';
        $order->status = $newStatus;
        $order->save();

        // Optionally, update the corresponding Airalo order if required.
        // For example: $this->airaloService->updateOrderStatus($order->airalo_order_id, $newStatus);

        Log::info("Payment callback: order updated", ['order_id' => $order->id, 'status' => $newStatus]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order updated successfully.',
            'data'    => $order,
        ]);
    }
}
