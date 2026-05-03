<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Password::default()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => 'patient',
        ]);

        event(new Registered($user));

        return response()->json(['user' => $user->only('id', 'name', 'email', 'role')], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 422);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        return response()->json(['user' => $user->only('id', 'name', 'email', 'role')]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->only('id', 'name', 'email', 'role', 'smart_id_verified_at')]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect(config('app.url').'/profile?error=invalid_link');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(config('app.url').'/profile?verified=1');
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return redirect(config('app.url').'/profile?verified=1');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }
}
