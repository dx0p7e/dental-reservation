<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SlotResource;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SlotController extends Controller
{
    public function index(Request $request, Doctor $doctor): AnonymousResourceCollection
    {
        $slots = $doctor->slots()
            ->where('is_booked', false)
            ->when($request->date, fn ($q) => $q->whereDate('date', $request->date))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return SlotResource::collection($slots);
    }
}
