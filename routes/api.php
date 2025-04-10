<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // Use statement for AuthController
use App\Http\Controllers\API\AiraloController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- Default Laravel API Route (for authenticated users) ---
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- Custom Authentication Routes ---

// Public routes for authentication
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // You can add other authenticated routes here later, for example:
    // Route::get('/profile', [ProfileController::class, 'show']);
});

// --- Other API routes for your application will go here ---

Route::get('/airalo/packages', [AiraloController::class, 'listPackages']);
Route::post('/airalo/orders', [AiraloController::class, 'createOrder']);
Route::get('/airalo/countries-from-packages', [AiraloController::class, 'listCountriesFromPackages']);

// Example for showing a single package (implement later)
// Route::get('/packages/{package}', [PackageController::class, 'show'])->name('api.packages.show');

// Example for placing an order (would likely require authentication)
// Route::middleware('auth:sanctum')->post('/orders', [OrderController::class, 'store'])->name('api.orders.store');


// --- Add Admin routes here later ---

