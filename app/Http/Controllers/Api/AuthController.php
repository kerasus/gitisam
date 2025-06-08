<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Manager')->only(['resetPassword']);
    }

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users',
            'password' => 'required|string|min:8',
            'mobile' => 'required|string|unique:users',
            'email' => 'nullable|string|email|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Authenticate a user and issue a token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Issue a token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Send a verification code to the authenticated user's mobile number.
     */
    public function sendVerificationCode(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        // Generate a random 6-digit verification code
        $verificationCode = mt_rand(100000, 999999);

        // Update the user's record with the verification code
        $user->update([
            'mobile_verification_code' => $verificationCode,
        ]);

        // Simulate sending the verification code (e.g., via SMS gateway)
//        Log::info("Verification code for {$user->mobile}: {$verificationCode}");

        return response()->json([
            'message' => 'Verification code sent successfully.',
        ]);
    }

    /**
     * Verify the user's mobile number using the verification code.
     */
    public function verifyMobile(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'code' => 'required|string',
        ]);

        $user = User::where('mobile', $request->mobile)->first();

        if (!$user || $user->mobile_verification_code !== $request->code) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 400);
        }

        // Mark the mobile as verified
        $user->update([
            'mobile_verified_at' => now(),
            'mobile_verification_code' => null, // Clear the verification code
        ]);

        return response()->json([
            'message' => 'Mobile number verified successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Logout the user and revoke the token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Reset a user's password by ID.
     *
     * @param Request $request
     * @param int $user_id
     * @return JsonResponse
     */
    public function resetPassword(Request $request, int $user_id): JsonResponse
    {
        // Validate the request data
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Find the user by ID
        $user = User::findOrFail($user_id);

        // Update the user's password
        $user->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return response()->json([
            'message' => 'کلمه عبور با موفقیت تغییر یافت.',
        ], 200);
    }
}
