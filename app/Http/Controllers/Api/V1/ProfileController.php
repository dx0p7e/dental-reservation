<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'email_verified_at' => $user->email_verified_at,
            'phone_verified_at' => $user->phone_verified_at,
            'smart_id_verified_at' => $user->smart_id_verified_at,
            'notification_channel' => $user->notification_channel,
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        if (isset($data['phone']) && $data['phone'] !== $user->phone) {
            $data['phone_verified_at'] = null;
        }

        $user->update($data);

        return response()->json(['message' => 'Profile updated.']);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        auth()->user()->update([
            'password' => $request->validated('password'),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }
}
