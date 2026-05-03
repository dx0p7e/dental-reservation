<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Sk\SmartId\Exception\SessionNotFoundException;
use Sk\SmartId\Exception\SessionTimeoutException;
use Sk\SmartId\Exception\SmartIdException;
use Sk\SmartId\Exception\UserRefusedException;
use Sk\SmartId\Model\SemanticsIdentifier;
use Sk\SmartId\Notification\NotificationInteraction;
use Sk\SmartId\SmartIdClient;

class SmartIdVerificationController extends Controller
{
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'personal_code' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($request->input('country') === 'LT' && ! preg_match('/^\d{11}$/', $value)) {
                        $fail('The personal code must be exactly 11 digits for Lithuania.');
                    }
                },
            ],
            'country' => ['required', 'string', 'in:LT,EE,LV'],
        ]);

        $client = app(SmartIdClient::class);

        try {
            $session = $client->createNotificationAuthentication()
                ->withSemanticsIdentifier(SemanticsIdentifier::forPerson($validated['country'], $validated['personal_code']))
                ->withAllowedInteractionsOrder([
                    NotificationInteraction::displayTextAndPin('Patvirtinkite tapatybę'),
                ])
                ->initiate();
        } catch (SessionNotFoundException) {
            return response()->json(['message' => 'Personal code is not registered with Smart-ID.'], 422);
        } catch (SmartIdException) {
            return response()->json(['message' => 'Smart-ID service error. Please try again.'], 503);
        }

        $pollingToken = Str::random(32);

        Cache::put(
            "smart_id_poll_{$pollingToken}",
            ['sessionId' => $session->getSessionId(), 'userId' => $request->user()->id],
            now()->addMinutes(3)
        );

        return response()->json([
            'verification_code' => $session->getVerificationCode(),
            'polling_token' => $pollingToken,
        ]);
    }

    public function poll(Request $request, string $token): JsonResponse
    {
        $cacheEntry = Cache::get("smart_id_poll_{$token}");

        if ($cacheEntry === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($cacheEntry['userId'] !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $sessionId = $cacheEntry['sessionId'];

        if (config('smart-id.demo_auto_confirm')) {
            $pollCount = ($cacheEntry['pollCount'] ?? 0) + 1;
            Cache::put("smart_id_poll_{$token}", array_merge($cacheEntry, ['pollCount' => $pollCount]), now()->addMinutes(3));
            if ($pollCount >= 3) {
                $this->confirmVerification($request->user(), $token);

                return response()->json(['status' => 'ok']);
            }

            return response()->json(['status' => 'running']);
        }

        try {
            $client = app(SmartIdClient::class);
            $client->setPollTimeoutMs(0);

            $status = $client->getSessionStatusPoller()->poll($sessionId);
        } catch (UserRefusedException) {
            Cache::forget("smart_id_poll_{$token}");

            return response()->json(['status' => 'failed', 'reason' => 'refused']);
        } catch (SessionTimeoutException) {
            Cache::forget("smart_id_poll_{$token}");

            return response()->json(['status' => 'failed', 'reason' => 'timeout']);
        } catch (SmartIdException) {
            Cache::forget("smart_id_poll_{$token}");

            return response()->json(['status' => 'failed', 'reason' => 'error']);
        }

        if ($status->isRunning()) {
            return response()->json(['status' => 'running']);
        }

        // Session complete with OK result (validateResult did not throw)
        $this->confirmVerification($request->user(), $token);

        return response()->json(['status' => 'ok']);
    }

    private function confirmVerification(User $user, string $token): void
    {
        $user->update(['smart_id_verified_at' => now()]);
        Cache::forget("smart_id_poll_{$token}");

        $user->appointments()
            ->where('status', AppointmentStatus::Pending->value)
            ->get()
            ->each(fn ($appt) => $appt->update(['status' => AppointmentStatus::Confirmed]));
    }
}
