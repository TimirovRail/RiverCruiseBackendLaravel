<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservedSeat extends Model
{
    use HasFactory;

    protected $fillable = ['booking_id', 'schedule_id', 'category', 'seat_number'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function cruiseSchedule()
    {
        return $this->belongsTo(CruiseSchedule::class, 'schedule_id');
    }
}