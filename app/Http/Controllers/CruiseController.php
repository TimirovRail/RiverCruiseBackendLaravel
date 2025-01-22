<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'river' => 'required|string|max:255',
            'total_places' => 'required|integer|min:1',
            'cabins' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price_per_person' => 'required|numeric|min:0',
            'available_places' => 'required|integer|min:1',
        ]);

        try {
            $cruise = Cruise::create($validated);
            return response()->json($cruise, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'river' => 'sometimes|string|max:255',
            'total_places' => 'sometimes|integer|min:1',
            'cabins' => 'sometimes|integer|min:1',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'price_per_person' => 'sometimes|numeric|min:0',
            'available_places' => 'sometimes|integer|min:1',
        ]);

        $cruise = Cruise::findOrFail($id);
        $cruise->update($validated);

        return response()->json($cruise, 200);
    }

    public function destroy($id)
    {
        $cruise = Cruise::findOrFail($id);
        $cruise->delete();

        return response()->json(null, 204);
    }

}