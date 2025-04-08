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
            'price_per_person' => 'required|numeric|min:0',
            'total_distance' => 'required|numeric|min:0',
            'image_path' => 'nullable|string',
            'panorama_url' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.name' => 'required_with:features|string',
            'features.*.price' => 'required_with:features|numeric|min:0',
            'cabins_by_class.luxury.places' => 'required|integer|min:0',
            'cabins_by_class.luxury.image_path' => 'nullable|string',
            'cabins_by_class.economy.places' => 'required|integer|min:0',
            'cabins_by_class.economy.image_path' => 'nullable|string',
            'cabins_by_class.standard.places' => 'required|integer|min:0',
            'cabins_by_class.standard.image_path' => 'nullable|string',
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
            'total_places' => 'required|integer|min:0',
            'available_places' => 'required|integer|min:0|lte:total_places',
            'status' => 'required|in:planned,active,completed,canceled',
        ]);

        $schedule = CruiseSchedule::create(array_merge($validated, ['cruise_id' => $cruiseId]));
        return response()->json($schedule, 201);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'river' => 'required|string|max:255',
                'cabins' => 'required|integer|min:0',
                'total_distance' => 'required|numeric|min:0',
                'price_per_person' => 'required|numeric|min:0',
                'image_path' => 'nullable|string',
                'features' => 'nullable|array',
                'features.*.name' => 'required_with:features|string',
                'features.*.price' => 'required_with:features|numeric|min:0',
                'cabins_by_class.luxury.places' => 'required|integer|min:0',
                'cabins_by_class.luxury.image_path' => 'nullable|string',
                'cabins_by_class.economy.places' => 'required|integer|min:0',
                'cabins_by_class.economy.image_path' => 'nullable|string',
                'cabins_by_class.standard.places' => 'required|integer|min:0',
                'cabins_by_class.standard.image_path' => 'nullable|string',
            ]);

            $cruise = Cruise::findOrFail($id);
            $cruise->update($validated);
            return response()->json($cruise);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $cruise = Cruise::findOrFail($id);
        if ($cruise->schedules()->exists()) {
            $cruise->schedules()->delete(); // Удаляем связанные расписания
        }
        $cruise->delete();
        return response()->json(['message' => 'Круиз и связанные расписания успешно удалены']);
    }

    public function updateSchedule(Request $request, $id)
    {
        $validated = $request->validate([
            'departure_datetime' => 'required|date',
            'arrival_datetime' => 'required|date|after:departure_datetime',
            'economy_places' => 'required|integer|min:0',
            'standard_places' => 'required|integer|min:0',
            'luxury_places' => 'required|integer|min:0',
            'total_places' => 'required|integer|min:0',
            'available_places' => 'required|integer|min:0',
            'status' => 'required|in:planned,active,completed,canceled',
        ]);

        $schedule = CruiseSchedule::findOrFail($id);
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