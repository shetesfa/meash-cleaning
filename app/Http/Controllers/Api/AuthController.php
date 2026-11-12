<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        if (!$request->has('login') && $request->has('username')) {
            $request->merge(['login' => $request->input('username')]);
        }

        $request->validate([
            'login' => 'required|string', // phone, email, or username
            'password' => 'required|string',
        ]);

        $login = trim($request->input('login'));
        $throttleKey = Str::transliterate(Str::lower($login) . '|' . $request->ip());

        // Throttle check: Max 5 attempts per minute per account
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        // Enforce strict login by exact email, phone number, username, or full name with password
        $user = User::where('phone', $login)
            ->orWhere('email', $login)
            ->orWhere('username', $login)
            ->orWhere('name', $login)
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Clear rate limiter upon successful authentication
        RateLimiter::clear($throttleKey);

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        // Secure expiring token (8 hours / 480 minutes)
        $tokenResult = $user->createToken('meash-pwa-token', ['*'], now()->addMinutes(480));
        $plainToken = $tokenResult->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_active) {
            $user->tokens()->delete();
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()?->delete();
        }
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                \Illuminate\Validation\Rules\Password::min(12)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['የአሁኑ የይለፍ ቃል ትክክል አይደለም።'],
            ]);
        }

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        // Revoke all existing tokens on password change
        $user->tokens()->delete();

        $newToken = $user->createToken('meash-pwa-token', ['*'], now()->addMinutes(480))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'የይለፍ ቃልዎ በተሳካ ሁኔታ ተቀይሯል! የቆዩ ሴሽኖች በሙሉ ተዘግተዋል።',
            'token' => $newToken,
        ]);
    }
}
