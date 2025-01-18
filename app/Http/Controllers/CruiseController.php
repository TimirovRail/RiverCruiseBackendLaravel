<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use Illuminate\Http\JsonResponse;

class CruiseController extends Controller
{
    public function index(): JsonResponse
    {
        $cruises = Cruise::all();
        return response()->json($cruises);
    }
    public function show($id)
    {
        $cruise = Cruise::find($id);

        if ($cruise) {
            return response()->json($cruise);
        }

        return response()->json(['message' => 'Круиз не найден'], 404);
    }
}