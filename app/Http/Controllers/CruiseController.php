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
            if (is_string($cruise->features)) {
                $cruise->features = json_decode($cruise->features, true);
            }

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
        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'river' => 'sometimes|string',
            'total_places' => 'sometimes|integer',
            'cabins' => 'sometimes|integer',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'price_per_person' => 'sometimes|numeric',
            'available_places' => 'sometimes|integer',
        ]);

        $cruise = Cruise::findOrFail($id);
        $cruise->update($request->all());
        return response()->json($cruise);
    }

    public function destroy($id)
    {
        $cruise = Cruise::findOrFail($id);
        $cruise->delete();
        return response()->json(['message' => 'Круиз успешно удалён']);
    }
}