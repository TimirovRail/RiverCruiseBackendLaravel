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
        'total_places',
        'cabins',
        'start_date',
        'end_date',
        'price_per_person',
        'available_places',
    ];
}
