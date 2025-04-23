<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CruiseSeat extends Model
{
    protected $table = 'cruise_seats';
    protected $fillable = ['cruise_schedule_id', 'seat_type', 'total_seats', 'available_seats'];
}