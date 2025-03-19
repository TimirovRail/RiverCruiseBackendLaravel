<?php
namespace App\Http\Controllers;

use App\Models\Souvenir;
use Illuminate\Http\Request;

class SouvenirController extends Controller
{
    public function index()
    {
        $souvenirs = Souvenir::all(); // Получаем все сувениры из базы данных
        return response()->json($souvenirs);
    }
}