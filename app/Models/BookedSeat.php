<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BookedSeat extends Model
{
    protected $table = 'booked_seats';
    protected $fillable = ['booking_id', 'cruise_schedule_id', 'seat_number', 'seat_type'];
}