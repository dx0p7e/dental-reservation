<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->user->name,
            ] : null,
            'service' => [
                'id' => $this->service->id,
                'name' => $this->service->name,
                'price' => $this->service->price,
            ],
            'slot' => $this->slot ? [
                'id' => $this->slot->id,
                'date' => $this->slot->date->format('Y-m-d'),
                'start_time' => $this->slot->start_time,
            ] : null,
            'preferred_date' => $this->preferred_date?->format('Y-m-d'),
            'rescheduled_at' => $this->rescheduled_at?->toISOString(),
            'discount_pct' => $this->discount_pct,
            'final_price' => $this->final_price,
        ];
    }
}
