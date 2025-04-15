<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AiraloController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;

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

// نقاط النهاية الخاصة بـ Airalo (إذا كانت تريده أن تكون عامة أو يمكن حمايتها لاحقاً)
Route::get('/airalo/packages', [AiraloController::class, 'listPackages']);
Route::post('/airalo/orders', [AiraloController::class, 'createOrder']);
Route::get('/airalo/countries-from-packages', [AiraloController::class, 'listCountriesFromPackages']);
Route::get('/airalo/packages/{type}/{country}', [AiraloController::class, 'listPackagesByTypeAndCountry']);
Route::get('/airalo/packages/global', [AiraloController::class, 'listGlobalPackages']);


// نقطة النهاية الخاصة بـ Payment Webhook (يفضل تأمينها بمفتاح أو توقيع مشترك)
Route::post('/payment/webhook', [PaymentWebhookController::class, 'handlePaymentCallback']);

// نقاط النهاية المحمية (التي تتطلب مصادقة مستخدم) مع استخدام middleware "auth:sanctum"
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
});

