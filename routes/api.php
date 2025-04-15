<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AiraloController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\TripController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| هنا يمكنك تسجيل مسارات API الخاصة بتطبيقك.
| يتم تحميل هذه المسارات بواسطة RouteServiceProvider وتعيينها ضمن مجموعة "api".
|
*/

// نقاط النهاية العامة أو غير المحمية
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('api.logout');
Route::middleware('auth:api')->get('/me', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'data'   => $request->user()
    ]);
});

// نقاط النهاية الخاصة بـ Airalo (إذا كانت تريده أن تكون عامة أو يمكن حمايتها لاحقاً)
Route::get('/airalo/packages', [AiraloController::class, 'listPackages']);
Route::post('/airalo/orders', [AiraloController::class, 'createOrder']);
Route::get('/airalo/countries-from-packages', [AiraloController::class, 'listCountriesFromPackages']);
Route::get('/airalo/packages/{type}/{country}', [AiraloController::class, 'listPackagesByTypeAndCountry']);
Route::get('/airalo/packages/global', [AiraloController::class, 'listGlobalPackages']);
Route::get('/airalo/regions', [AiraloController::class, 'listRegions']);
// New endpoint: Retrieve packages based on region slug
Route::post('/airalo/packages/global', [AiraloController::class, 'getPackagesByRegion']);



// نقطة النهاية الخاصة بـ Payment Webhook (يفضل تأمينها بمفتاح أو توقيع مشترك)
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handlePaymentCallback']);

// نقاط النهاية المحمية (التي تتطلب مصادقة مستخدم) مع استخدام middleware "auth:sanctum"
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
});

// نقاط النهاية الخاصة بالرحلات (trips)
// يمكنك حماية نقاط النهاية حسب الحاجة، مثلاً عبر auth:sanctum إذا أردت أن يكون الإنشاء محدوداً للمشرفين
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // نقطة النهاية لإنشاء باقة جديدة
    Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
   
});
 // نقطة النهاية لاسترجاع جميع الرحلات
 Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
 // نقطة النهاية لاسترجاع الرحلات حسب كود الدولة
 Route::get('/trips/country/{country_code}', [TripController::class, 'tripsByCountry'])->name('trips.byCountry');
 // نقطة النهاية لاسترجاع رحلة برقم الـ ID
 Route::get('/trips/{id}', [TripController::class, 'show'])->name('trips.show');

