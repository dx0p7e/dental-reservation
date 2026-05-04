<?php

namespace App\Models;

use Database\Factories\LoyaltyTierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    /** @use HasFactory<LoyaltyTierFactory> */
    use HasFactory;

    protected $fillable = [
        'tier',
        'points_threshold',
        'discount_bonus_pct',
    ];

    protected $casts = [
        'points_threshold' => 'integer',
        'discount_bonus_pct' => 'decimal:2',
    ];
}
