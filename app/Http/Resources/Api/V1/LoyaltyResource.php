<?php

namespace App\Http\Resources\Api\V1;

use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoyaltyAccount */
class LoyaltyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $current = LoyaltyTier::where('tier', $this->tier)->first();
        $next = LoyaltyTier::where('points_threshold', '>', $this->points_balance)
            ->orderBy('points_threshold')
            ->first();

        return [
            'points_balance' => $this->points_balance,
            'tier' => $this->tier,
            'tier_color' => $current?->color ?? '#6b7280',
            'next_tier' => $next?->tier,
            'points_to_next_tier' => $next ? $next->points_threshold - $this->points_balance : null,
            'transactions' => $this->whenLoaded('transactions', fn () => $this->transactions->map(fn ($tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'points_delta' => $tx->points_delta,
                'service_name' => $tx->appointment?->service?->name,
                'created_at' => $tx->created_at->toDateString(),
            ])),
        ];
    }
}
