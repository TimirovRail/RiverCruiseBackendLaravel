<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CruiseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'cruise_id',
        'departure_datetime',
        'arrival_datetime',
        'total_places',
        'available_places',
        'economy_places',
        'standard_places',
        'luxury_places',
        'available_economy_places',
        'available_standard_places',
        'available_luxury_places',
        'status',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
    ];

    public function cruise()
    {
        return $this->belongsTo(Cruise::class, 'cruise_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isCompleted()
    {
        return $this->arrival_datetime->isPast();
    }
}