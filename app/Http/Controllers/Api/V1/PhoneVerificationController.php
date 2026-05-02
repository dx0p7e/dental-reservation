<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerification;
use App\Notifications\PhoneOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhoneVerificationController extends Controller
{
    public function sendOtp(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->phone) {
            return response()->json(['message' => 'Telefono numeris nenurodytas.'], 422);
        }

        // Delete any previous OTPs for this user
        PhoneVerification::where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerification::create([
            'user_id'    => $user->id,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        if (app()->environment(['local', 'testing'])) {
            logger()->info("OTP for user {$user->id}: {$code}");
        } else {
            $user->notify(new PhoneOtpNotification($code));
        }

        return response()->json(['message' => 'OTP išsiųstas.']);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        $record = PhoneVerification::where('user_id', $user->id)
            ->valid()
            ->latest('created_at')
            ->first();

        if (! $record || $record->code !== $request->input('code')) {
            return response()->json(['message' => 'Neteisingas arba pasibaigęs kodas.'], 422);
        }

        $user->update(['phone_verified_at' => now()]);

        $record->delete();

        return response()->json(['message' => 'Telefonas patvirtintas.']);
    }
}
