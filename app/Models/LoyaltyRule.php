<?php

namespace App\Models;

use Database\Factories\LoyaltyRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class LoyaltyRule extends Model
{
    /** @use HasFactory<LoyaltyRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'service_id',
        'points_earned',
        'discount_pct',
        'valid_months',
        'is_active',
    ];

    protected $casts = [
        'points_earned' => 'integer',
        'discount_pct'  => 'decimal:2',
        'valid_months'  => 'integer',
        'is_active'     => 'boolean',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $q): void {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $q->whereNull('valid_months')
                        ->orWhereRaw("datetime(created_at, '+' || CAST(valid_months AS TEXT) || ' months') > CURRENT_TIMESTAMP");
                } else {
                    $q->whereNull('valid_months')
                        ->orWhereRaw('DATE_ADD(created_at, INTERVAL valid_months MONTH) > NOW()');
                }
            });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
