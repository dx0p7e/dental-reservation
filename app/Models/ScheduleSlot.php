<?php

namespace App\Models;

use Database\Factories\ScheduleSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScheduleSlot extends Model
{
    /** @use HasFactory<ScheduleSlotFactory> */
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'date',
        'start_time',
        'end_time',
        'slot_type',
        'is_booked',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'is_booked' => 'boolean',
    ];

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /** @return HasOne<Appointment, $this> */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class, 'slot_id');
    }
}
