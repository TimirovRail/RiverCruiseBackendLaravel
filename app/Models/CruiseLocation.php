<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CruiseLocation extends Model
{
    protected $fillable = ['cruise_id', 'latitude', 'longitude', 'recorded_at'];

    public function cruise()
    {
        return $this->belongsTo(Cruise::class);
    }
}