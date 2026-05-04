<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Doctor */
class DoctorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'specialization' => $this->specialization,
            'bio' => $this->bio,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
        ];
    }
}
