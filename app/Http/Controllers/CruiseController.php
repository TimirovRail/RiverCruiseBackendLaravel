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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Валидация для основного изображения
            'panorama_url' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.name' => 'required_with:features|string',
            'features.*.price' => 'required_with:features|numeric|min:0',
            'cabins_by_class' => 'required|json',
            'luxury_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Валидация для изображения кают
            'economy_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'standard_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Обрабатываем основное изображение круиза
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public'); // Сохраняем в public/storage/images
        }

        // Обрабатываем изображения кают
        $cabinsByClass = json_decode($request->input('cabins_by_class'), true);
        $classes = ['luxury', 'economy', 'standard'];
        foreach ($classes as $class) {
            if ($request->hasFile("{$class}_image")) {
                $cabinImagePath = $request->file("{$class}_image")->store('images/cabins', 'public');
                $cabinsByClass[$class]['image_path'] = $cabinImagePath;
            } else {
                $cabinsByClass[$class]['image_path'] = null; // Если изображение не загружено
            }
        }

        // Подготавливаем данные для создания круиза
        $cruiseData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'river' => $validated['river'],
            'cabins' => $validated['cabins'],
            'price_per_person' => $validated['price_per_person'],
            'total_distance' => $validated['total_distance'],
            'image_path' => $imagePath,
            'panorama_url' => $validated['panorama_url'] ?? null,
            'cabins_by_class' => $cabinsByClass,
            'features' => $validated['features'] ?? [],
            'created_at' => now(),
            'updated_at' => now(),
            'departure_datetime' => now(), // Временное значение
            'arrival_datetime' => now()->addDay(), // Временное значение
            'status' => 'planned', // Значение по умолчанию
        ];

        $cruise = Cruise::create($cruiseData);
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

            $validated['updated_at'] = now();

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