<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cruise;
use App\Models\CruiseSchedule;
use Illuminate\Http\JsonResponse;

class CruiseController extends Controller
{
    public function index()
    {
        $cruises = Cruise::with('schedules')->get();
        return response()->json($cruises);
    }

    public function show($id)
    {
        $cruise = Cruise::with('schedules')->find($id);

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
            'cabins' => 'required|integer',
            'price_per_person' => 'required|numeric',
            'image_path' => 'nullable|string',
            'features' => 'nullable|array',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'cabins_by_class' => 'nullable|array', // Например, ['economy' => 10, 'standard' => 10, 'luxury' => 5]
        ]);

        $cruise = Cruise::create($validated);

        return response()->json($cruise, 201);
    }

    public function storeSchedule(Request $request, $cruiseId)
    {
        $validated = $request->validate([
            'departure_datetime' => 'required|date',
            'arrival_datetime' => 'required|date|after:departure_datetime',
            'economy_places' => 'required|integer|min:0',
            'standard_places' => 'required|integer|min:0',
            'luxury_places' => 'required|integer|min:0',
            'status' => 'required|in:planned,active,completed,canceled',
        ]);

        $cruise = Cruise::findOrFail($cruiseId);

        $totalPlaces = $validated['economy_places'] + $validated['standard_places'] + $validated['luxury_places'];

        $schedule = CruiseSchedule::create([
            'cruise_id' => $cruise->id,
            'departure_datetime' => $validated['departure_datetime'],
            'arrival_datetime' => $validated['arrival_datetime'],
            'total_places' => $totalPlaces,
            'available_places' => $totalPlaces,
            'economy_places' => $validated['economy_places'],
            'standard_places' => $validated['standard_places'],
            'luxury_places' => $validated['luxury_places'],
            'available_economy_places' => $validated['economy_places'],
            'available_standard_places' => $validated['standard_places'],
            'available_luxury_places' => $validated['luxury_places'],
            'status' => $validated['status'],
        ]);

        return response()->json(['message' => 'Расписание создано', 'schedule' => $schedule], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'river' => 'sometimes|string',
            'cabins' => 'sometimes|integer',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'price_per_person' => 'sometimes|numeric',
            'cabins_by_class' => 'sometimes|array',
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