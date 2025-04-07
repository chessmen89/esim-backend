<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // Make sure this line is present

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
Route::post('/register', [AuthController::class, 'register'])->name('api.register'); // Added name for potential future use
Route::post('/login', [AuthController::class, 'login'])->name('api.login');       // Added name

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout'); // Added name

    // You can add other authenticated routes here later, for example:
    // Route::get('/profile', [ProfileController::class, 'show']);
});

// --- Other API routes for your application will go here ---
// Example:
// Route::get('/packages', [PackageController::class, 'index']);
// Route::get('/packages/{package}', [PackageController::class, 'show']);
// Route::middleware('auth:sanctum')->post('/orders', [OrderController::class, 'store']);

