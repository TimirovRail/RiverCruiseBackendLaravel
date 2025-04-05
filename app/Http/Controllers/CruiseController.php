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
            'cabins' => 'required|integer|min:0',
            'total_distance' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'image_path' => 'nullable|string|max:255',
            'panorama_url' => 'nullable|string|max:255',
            'price_per_person' => 'required|array',
            'cabins_by_class' => 'required|array',
        ]);

        $cruise = Cruise::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'river' => $validated['river'],
            'cabins' => $validated['cabins'],
            'total_distance' => $validated['total_distance'],
            'features' => $validated['features'] ?? [],
            'image_path' => $validated['image_path'] ?? null,
            'panorama_url' => $validated['panorama_url'] ?? null,
            'price_per_person' => $validated['price_per_person'],
            'cabins_by_class' => $validated['cabins_by_class'],
        ]);

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
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'river' => 'sometimes|string|max:255',
            'cabins' => 'sometimes|integer|min:0',
            'total_distance' => 'sometimes|numeric|min:0',
            'features' => 'sometimes|array',
            'image_path' => 'sometimes|string|max:255',
            'panorama_url' => 'sometimes|string|max:255',
            'price_per_person' => 'sometimes|array',
            'cabins_by_class' => 'sometimes|array',
        ]);

        $cruise = Cruise::findOrFail($id);
        $cruise->update($validated);
        return response()->json($cruise);
    }

    public function destroy($id)
    {
        $cruise = Cruise::findOrFail($id);
        $cruise->delete();
        return response()->json(['message' => 'Круиз успешно удалён']);
    }

    public function updateSchedule(Request $request, $id)
    {
        $validated = $request->validate([
            'departure_datetime' => 'sometimes|date',
            'arrival_datetime' => 'sometimes|date|after:departure_datetime',
            'economy_places' => 'sometimes|integer|min:0',
            'standard_places' => 'sometimes|integer|min:0',
            'luxury_places' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:planned,active,completed,canceled',
        ]);

        $schedule = CruiseSchedule::findOrFail($id);

        if (isset($validated['economy_places']) || isset($validated['standard_places']) || isset($validated['luxury_places'])) {
            $economy_places = $validated['economy_places'] ?? $schedule->economy_places;
            $standard_places = $validated['standard_places'] ?? $schedule->standard_places;
            $luxury_places = $validated['luxury_places'] ?? $schedule->luxury_places;

            $totalPlaces = $economy_places + $standard_places + $luxury_places;

            $validated['total_places'] = $totalPlaces;
            $validated['available_places'] = $totalPlaces; // Можно добавить логику для пересчёта доступных мест
            $validated['available_economy_places'] = $economy_places;
            $validated['available_standard_places'] = $standard_places;
            $validated['available_luxury_places'] = $luxury_places;
        }

        $schedule->update($validated);
        return response()->json($schedule);
    }

    public function destroySchedule($id)
    {
        $schedule = CruiseSchedule::findOrFail($id);
        $schedule->delete();
        return response()->json(['message' => 'Расписание успешно удалено']);
    }
}