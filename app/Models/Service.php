<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price',
        'complexity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    /** @return HasOne<LoyaltyRule, $this> */
    public function loyaltyRule(): HasOne
    {
        return $this->hasOne(LoyaltyRule::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return BelongsToMany<Doctor, $this> */
    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_service');
    }
}
