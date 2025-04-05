<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $fillable = ['user_id', 'name','url'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
