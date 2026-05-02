<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'description'          => $this->description,
            'duration_minutes'     => $this->duration_minutes,
            'price'                => $this->price,
            'loyalty_discount_pct' => $request->attributes->get('loyalty_discount_pct'),
            'promo_discount_pct'   => $this->whenLoaded('loyaltyRule', fn ($rule) => $rule ? (float) $rule->discount_pct : null, null),
        ];
    }
}
