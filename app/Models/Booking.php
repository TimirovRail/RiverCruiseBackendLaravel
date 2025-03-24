<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cruise_schedule_id',
        'economy_seats',
        'standard_seats',
        'luxury_seats',
        'total_price',
        'extras',
        'comment',
    ];

    protected $casts = [
        'extras' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cruiseSchedule()
    {
        return $this->belongsTo(CruiseSchedule::class, 'cruise_schedule_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}