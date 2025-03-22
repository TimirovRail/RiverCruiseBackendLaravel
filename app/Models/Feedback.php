<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    protected $table = 'feedbacks';

    protected $fillable = ['name', 'email', 'feedback', 'cruise', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function cruise()
    {
        return $this->belongsTo(Cruise::class, 'cruise_id');
    }
}
