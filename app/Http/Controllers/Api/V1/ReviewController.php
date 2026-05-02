<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\PatientReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $reviews = PatientReview::with('patient')
            ->where('is_published', true)
            ->latest()
            ->limit(50)
            ->get();

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request): ReviewResource|JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body'   => ['required', 'string', 'min:10', 'max:1000'],
            'title'  => ['nullable', 'string', 'max:150'],
        ]);

        if ($request->user()->review()->exists()) {
            return response()->json(['message' => 'Jūs jau palikote atsiliepimą.'], 409);
        }

        $review = PatientReview::create([
            'patient_id' => $request->user()->id,
            'rating'     => $validated['rating'],
            'body'       => $validated['body'],
            'title'      => $validated['title'] ?? null,
        ]);

        return (new ReviewResource($review->load('patient')))->response()->setStatusCode(201);
    }
}
