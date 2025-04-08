<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'cruise_id',
        'booking_id',
        'comment',
        'rating',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cruise()
    {
        return $this->belongsTo(Cruise::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}