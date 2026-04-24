<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetOtp;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\LoginAttempt;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:customer,seller,support',
        ]);

        if ($request->role === 'customer' && !str_ends_with($request->email, '@gmail.com')) {
            return response()->json(['success' => false, 'message' => 'Customers must register with Gmail (@gmail.com)'], 400);
        }

        if ($request->role === 'seller' && !str_ends_with($request->email, '@yahoo.com')) {
            return response()->json(['success' => false, 'message' => 'Sellers must register with Yahoo email (@yahoo.com)'], 400);
        }

        if ($request->role === 'support' && !str_ends_with($request->email, '@hotmail.com')) {
            return response()->json(['success' => false, 'message' => 'Support staff must register with Hotmail (@hotmail.com)'], 400);
        }

        if ($request->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Admin accounts cannot be created via registration'], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $request->role
                ],
                'token' => $token
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Step 1 - Rate limit check FIRST (before anything else)
        $maxAttempts = 5;
        $lockoutMinutes = 15;

        $recentAttempts = LoginAttempt::where('email', $request->email)
            ->where('attempted_at', '>=', now()->subMinutes($lockoutMinutes))
            ->count();

        if ($recentAttempts >= $maxAttempts) {
            $firstAttempt = LoginAttempt::where('email', $request->email)
                ->where('attempted_at', '>=', now()->subMinutes($lockoutMinutes))
                ->oldest('attempted_at')
                ->first();

            $unlockAt = $firstAttempt->attempted_at->addMinutes($lockoutMinutes);
            $minutesLeft = max(1, (int) ceil(now()->diffInMinutes($unlockAt, false)));

            return response()->json([
                'success' => false,
                'message' => "Account locked. Try again in {$minutesLeft} minute(s).",
                'locked' => true,
                'minutes_left' => $minutesLeft,
            ], 429);
        }

        // Step 2 - Find user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            LoginAttempt::create([
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'attempted_at' => now(),
            ]);
            $attemptsLeft = $maxAttempts - ($recentAttempts + 1);
            return response()->json([
                'success' => false,
                'message' => "Invalid credentials. {$attemptsLeft} attempt(s) remaining.",
                'attempts_left' => $attemptsLeft,
            ], 401);
        }

        // Step 3 - Password check
        $adminEmail = 'malik.umerkhan97@gmail.com';
        $adminPassword = 'malikawan97';

        if ($request->email === $adminEmail) {
            $passwordValid = ($request->password === $adminPassword) ||
                             Hash::check($request->password, $user->password);
        } else {
            $passwordValid = Hash::check($request->password, $user->password);
        }

        if (!$passwordValid) {
            LoginAttempt::create([
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'attempted_at' => now(),
            ]);
            $attemptsLeft = $maxAttempts - ($recentAttempts + 1);
            if ($attemptsLeft <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Account locked for {$lockoutMinutes} minutes.",
                    'locked' => true,
                    'minutes_left' => $lockoutMinutes,
                ], 429);
            }
            return response()->json([
                'success' => false,
                'message' => "Invalid credentials. {$attemptsLeft} attempt(s) remaining.",
                'attempts_left' => $attemptsLeft,
            ], 401);
        }

        // Step 4 - Domain validation (skip for admin)
        $role = $user->getRoleNames()->first();

        if ($request->email !== $adminEmail) {
            if ($role === 'customer' && !str_ends_with($request->email, '@gmail.com')) {
                return response()->json(['success' => false, 'message' => 'Customer accounts must use Gmail'], 401);
            }
            if ($role === 'seller' && !str_ends_with($request->email, '@yahoo.com')) {
                return response()->json(['success' => false, 'message' => 'Seller accounts must use Yahoo email'], 401);
            }
            if ($role === 'support' && !str_ends_with($request->email, '@hotmail.com')) {
                return response()->json(['success' => false, 'message' => 'Support accounts must use Hotmail'], 401);
            }
        }

        // Step 5 - Block others from using admin password
        if ($request->password === $adminPassword && $request->email !== $adminEmail) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        // Step 6 - Success - clear attempts and return token
        LoginAttempt::where('email', $request->email)->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role,
                ],
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $user->role = $user->getRoleNames()->first();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::where('email', $request->email)->delete();

        PasswordResetOtp::create([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email address'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6'
        ]);

        $record = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code'], 400);
        }

        if ($record->expires_at < now()) {
            return response()->json(['success' => false, 'message' => 'OTP has expired. Please request a new one'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $record = PasswordResetOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code'], 400);
        }

        if ($record->expires_at < now()) {
            return response()->json(['success' => false, 'message' => 'OTP has expired. Please request a new one'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        $record->delete();

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please login.'
        ]);
    }

    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        $googleUser = $driver->stateless()->user();

        $user = User::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => Hash::make(Str::random(32)),
            ]);
            $user->assignRole('customer');
        } elseif (!$user->google_id) {
            $user->google_id = $googleUser->id;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first()
        ];

        $encodedUser = base64_encode(json_encode($userData));

        return redirect("http://localhost:5173/auth/google/callback?token={$token}&user={$encodedUser}");
    }
}
