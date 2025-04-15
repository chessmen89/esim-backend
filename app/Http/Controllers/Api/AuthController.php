<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Http\JsonResponse; // Use JsonResponse for consistent API responses
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log; // Optional: for logging errors

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            // Validate incoming request data based on SRS requirements
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users', // Ensure email is unique
                'password' => ['required', 'confirmed', Password::defaults()], // Use default strong password rules, requires password_confirmation field
                'date_of_birth' => 'required|date|before_or_equal:today', // Ensure valid date
                'mobile_number' => 'nullable|string|max:20', // Optional fields
                'country' => 'nullable|string|max:100',
            ]);

            // Create the user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']), // Hash the password
                'date_of_birth' => $validatedData['date_of_birth'],
                'mobile_number' => $validatedData['mobile_number'] ?? null,
                'country' => $validatedData['country'] ?? null,
            ]);

            // Create a token for the new user (using Sanctum)
            $token = $user->createToken('auth_token')->plainTextToken;

            // Return successful response with user data and token
            return response()->json([
                'message' => 'User registered successfully!',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user, // Return user data (consider filtering sensitive info if needed)
            ], 201); // HTTP 201 Created

        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422); // HTTP 422 Unprocessable Entity
        } catch (\Exception $e) {
            // Handle other potential errors during registration
            Log::error('Registration failed: '.$e->getMessage()); // Log the actual error
            return response()->json([
                'message' => 'Registration failed. Please try again later.',
                // Optionally include error details in non-production environments
                // 'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500); // HTTP 500 Internal Server Error
        }
    }

    /**
     * Authenticate a user and return a token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
{
    try {
        $credentials = $request->only('email', 'password');

        $validator = Validator::make($credentials, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);

    } catch (\Exception $e) {
        Log::error('Login failed: ' . $e->getMessage());
        return response()->json([
            'message' => 'Login failed. Please try again later.',
        ], 500);
    }
}

     /**
     * Log the user out (Invalidate the token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user via the token and delete the current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Successfully logged out.'
            ], 200); // HTTP 200 OK

        } catch (\Exception $e) {
             Log::error('Logout failed: '.$e->getMessage()); // Log the actual error
             return response()->json([
                'message' => 'Logout failed. Please try again later.',
             ], 500); // HTTP 500 Internal Server Error
        }
    }

    // Add methods for social login handling here later if needed
    // public function redirectToProvider($provider) { ... }
    // public function handleProviderCallback($provider) { ... }
}
