<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'El. paštas jau patvirtintas.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Patvirtinimo nuoroda išsiųsta.']);
    }
}
