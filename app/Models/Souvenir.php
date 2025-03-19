<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Souvenir extends Model
{
    protected $fillable = ['id', 'title','description', 'image', 'price'];
}
