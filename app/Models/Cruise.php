<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cruise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'river',
        'cabins',
        'cabins_by_class',
        'price_per_person',
        'total_distance',
        'panorama_url',
        'image_path',
        'features',
        'departure_datetime',
        'arrival_datetime',
        'status',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'features' => 'array',
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
        'cabins_by_class' => 'array',
        'images' => 'array',
        'price_per_person' => 'array',
    ];

    public function schedules()
    {
        return $this->hasMany(CruiseSchedule::class);
    }

    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, CruiseSchedule::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function locations()
    {
        return $this->hasMany(CruiseLocation::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(CruiseLocation::class)->latest('recorded_at');
    }
}