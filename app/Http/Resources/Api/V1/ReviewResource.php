<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $nameParts = explode(' ', $this->patient?->name ?? '');
        $first = $nameParts[0] ?? '';
        $last = isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1).'.' : '';
        $patientName = trim("$first $last");

        return [
            'id'           => $this->id,
            'rating'       => $this->rating,
            'title'        => $this->title,
            'body'         => $this->body,
            'patient_name' => $patientName,
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
