<?php

namespace App\Models;

use Database\Factories\LoyaltyAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LoyaltyAccount extends Model
{
    /** @use HasFactory<LoyaltyAccountFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['points_balance', 'tier'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'patient_id',
        'points_balance',
        'tier',
    ];

    protected $casts = [
        'points_balance' => 'integer',
    ];

    /** @return BelongsTo<User, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /** @return HasMany<LoyaltyTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
