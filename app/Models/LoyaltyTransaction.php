<?php

namespace App\Models;

use Database\Factories\LoyaltyTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LoyaltyTransaction extends Model
{
    /** @use HasFactory<LoyaltyTransactionFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['points_delta', 'type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'loyalty_account_id',
        'appointment_id',
        'points_delta',
        'type',
    ];

    protected $casts = [
        'points_delta' => 'integer',
    ];

    /** @return BelongsTo<LoyaltyAccount, $this> */
    public function loyaltyAccount(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
