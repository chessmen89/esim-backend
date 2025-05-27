<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\HesabeService;
use App\Services\AiraloService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected AiraloService $airalo;
    protected HesabeService $hesabe;

    public function __construct(HesabeService $hesabe, AiraloService $airalo)
    {
        $this->hesabe = $hesabe;
        $this->airalo = $airalo;
    }


    /**
     * POST /api/checkout
     * Validates order, encrypts & redirects to Hesabe
     */
    public function checkout(Request $request)
    {
        // 1️⃣ Validate & load the order
        $data  = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);
        $order = Order::findOrFail($data['order_id']);

        // 2️⃣ Build payload exactly per Hesabe v2 indirect docs
        $paymentData = [
            'merchantCode'  => (string)config('hesabe.merchant_code'),
            'access_code'   => config('hesabe.access_code'),
            'amount'        => $order->amount,                        // use stored KWD amount
            'currency'      => $order->currency ?? 'KWD',
            'responseUrl' => route('payment.verify'),
            'failureUrl'  => route('payment.verify'),
            'paymentType'   => '0',                                  // indirect card flow
            'version'       => '2.0',
            'merchantRefNo' => $order->reference_number,
        ];
                Log::info('Checkout Payload:', $paymentData);


        // 3️⃣ Kick off the payment and return the URL
        $paymentUrl = $this->hesabe->initiatePayment($paymentData);

        return response()->json([
            'status'      => 'success',
            'payment_url' => $paymentUrl,
        ]);
    }
 /**
     * POST /api/payment/verify
     * Decrypts Hesabe callback, updates local order to “paid”,
     * then calls AiraloService->createOrder(...) and saves its data.
     */
   public function verify(Request $request)
{
    // decrypt & JSON-decode
    try {
        $payload = $this->hesabe->decryptCallback($request->input('data'));
    } catch (\Exception $e) {
        Log::error('Hesabe callback decrypt failed', ['err' => $e->getMessage()]);
        return response()->json(['status'=>'error','message'=>'Invalid callback data'], 400);
    }

    Log::debug('Hesabe callback payload', $payload);

//     // grab the reference, preferring merchantRefNo
//     $orderRef = $payload['response']['merchantRefNo']
//           ?? $payload['response']['orderReferenceNumber']
//           ?? null;

// if (! $orderRef) {
//     Log::warning('Hesabe callback missing orderRef', $payload);
//     return response()->json(['status'=>'error','message'=>'Missing order reference'], 400);
// }

// 🔁 استخدام قيمة ثابتة مؤقتًا لغرض الاختبار
$orderRef = 'ORD-21'; // <-- غيرها حسب الطلب اللي دفعته

$order = Order::where('reference_number', $orderRef)->first();

if (! $order) {
    return response()->json(['status'=>'error','message'=>'Order not found'], 404);
}

// ✅ بدلاً من استخراج ID منه، استخدمه كما هو:
$order = Order::where('reference_number', $orderRef)->first();

if (! $order) {
    return response()->json(['status'=>'error','message'=>'Order not found'], 404);
}

    // find the order
    $orderId = (int) str_replace('ORD-', '', $orderRef);
    $order   = Order::find($orderId);
    if (! $order) {
        return response()->json(['status'=>'error','message'=>'Order not found'], 404);
    }

    // only “CAPTURED” is a success
    $ok         = ($payload['status'] ?? false) === true;
    $resultCode = $payload['response']['resultCode'] ?? '';
    if (! $ok || $resultCode !== 'CAPTURED') {
        Log::warning('Hesabe transaction not captured', $payload);
        return response()->json(['status'=>'error','message'=>$payload['message']], 400);
    }

    // update your local order
    $order->update([
        'status'         => 'paid',
        'transaction_id' => $payload['response']['transactionId'] ?? null,
        'hesabe_id'      => $payload['response']['paymentToken']    ?? null,
        'payment_id'     => $payload['response']['paymentId']       ?? null,
        'terminal'       => $payload['response']['auth']            ?? null,
        'track_id'       => $payload['response']['trackID']         ?? null,
        'payment_type'   => $payload['response']['method'] == 1 ? 'KNET' : 'MPGS',
        'service_type'   => 'Payment Gateway',
    ]);

    // now call Airalo
    try {
        $airaloResp   = $this->airalo->createOrder([
            'package_id' => $order->package_id,
            'quantity'   => $order->quantity,
            'type'       => $order->type,
        ]);
        $order->update([
            'airalo_order_id' => data_get($airaloResp, 'data.data.id'),
            'order_data'      => $airaloResp,
        ]);
    } catch (\Exception $e) {
        Log::error("Airalo createOrder failed for order {$orderId}", ['err'=>$e->getMessage()]);
        // you can decide how to handle this failure
    }

    // return exactly what the client expects
    return response()->json([
        'message'  => $payload['message'],
        'response' => [
            'resultCode'           => $payload['response']['resultCode'],
            'amount'               => $payload['response']['amount'],
            'paymentToken'         => $payload['response']['paymentToken'],
            'paymentId'            => $payload['response']['paymentId'],
            'paidOn'               => $payload['response']['paidOn'],
            'orderReferenceNumber' => $orderRef,
            'auth'                 => $payload['response']['auth'],
            'trackID'              => $payload['response']['trackID'],
            'transactionId'        => $payload['response']['transactionId'],
            'Id'                   => $payload['response']['Id'],
            'bankReferenceId'      => $payload['response']['bankReferenceId'],
        ],
    ], 200);
}

    /**
     * GET /api/payment/failure
     */
    public function failure()
    {
        return response()->json(['status'=>'error','message'=>'payment failed'], 402);
    }

     public function callback(Request $request)
{
    // Retrieve the encrypted response data sent by Hesabe (usually under 'data' or similar key)
    $encryptedData = $request->input('data');
    if (!$encryptedData) {
        // No data provided – return an error response
        return response()->json([
            'message'  => 'No payment data received',
            'response' => null
        ], 400);
    }

    try {
        // Decrypt or parse the Hesabe response (using Hesabe SDK or custom decryption)
        $hesabe    = new HesabePayment();  // example: using a Hesabe payment utility class
        $responseData = $hesabe->decryptResponse($encryptedData); 
        // Ensure $responseData is an associative array of the payment result.

        // Check if the transaction was successful (e.g., resultCode indicates success)
        if (!empty($responseData['resultCode']) && $responseData['resultCode'] === 'CAPTURED') {
            // Prepare the success payload
            $output = [
                'message'  => 'Transaction Success',
                'response' => [
                    'resultCode'       => $responseData['resultCode'] ?? '',   // e.g. "CAPTURED"
                    'amount'           => $responseData['amount'] ?? '',
                    'paymentToken'     => $responseData['paymentToken'] ?? '',
                    'paymentId'        => $responseData['paymentId'] ?? '',
                    'paidOn'           => $responseData['paidOn'] ?? '',
                    'orderReferenceNo' => $responseData['orderReferenceNo'] ?? null,
                    'auth'             => $responseData['auth'] ?? '',
                    'trackID'          => $responseData['trackID'] ?? ($responseData['trackId'] ?? ''),
                    'transactionId'    => $responseData['transactionId'] ?? '',
                    'Id'               => $responseData['Id'] ?? ($responseData['id'] ?? ''),
                    'bankReferenceId'  => $responseData['bankReferenceId'] ?? ''
                ]
            ];
            // Log the success for debugging (optional)
            \Log::info("Hesabe Payment Success: " . json_encode($output));
            // Return the JSON response (HTTP 200 by default)
            return response()->json($output);
        } else {
            // Handle failed or pending transactions similarly
            $output = [
                'message'  => 'Transaction Failed',
                'response' => $responseData  // include the response details for debugging
            ];
            \Log::warning("Hesabe Payment Failure: " . json_encode($output));
            return response()->json($output, 200);
        }
    } catch (\Exception $e) {
        // In case of decryption errors or unexpected issues
        \Log::error("Error in Hesabe callback: " . $e->getMessage());
        return response()->json([
            'message' => 'Transaction Error',
            'response' => null
        ], 500);
    }
}
}