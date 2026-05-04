<?php

namespace App\Http\Resources\Api\V1;

use App\Models\ScheduleSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduleSlot */
class SlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
