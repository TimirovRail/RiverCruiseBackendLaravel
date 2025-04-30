<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use Illuminate\Http\Request;

class CruiseLocationController extends Controller
{
    public function getCurrentLocations()
    {
        try {
            $cruises = Cruise::with('latestLocation')->get();

            $locations = $cruises->map(function ($cruise) {
                $location = $cruise->latestLocation;
                if (!$location) {
                    return null; // Если нет последней локации, пропускаем круиз
                }
                return [
                    'id' => $cruise->id,
                    'name' => $cruise->name,
                    'river' => $cruise->river,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ];
            })->filter()->values(); // Удаляем null значения и сбрасываем индексы

            return response()->json($locations);
        } catch (\Exception $e) {
            \Log::error('Error fetching cruise locations: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при получении местоположений круизов'], 500);
        }
    }

    public function updateLocations(Request $request)
    {
        try {
            $cruises = Cruise::all();

            foreach ($cruises as $cruise) {
                // Используем последнюю запись из cruise_locations как основу
                $latestLocation = $cruise->latestLocation;

                // Если у круиза нет координат в cruise_locations, задаём начальные значения
                $currentLatitude = $latestLocation ? $latestLocation->latitude : 55.7558;
                $currentLongitude = $latestLocation ? $latestLocation->longitude : 37.6173;

                // Симуляция обновления координат
                $newLatitude = $currentLatitude + (rand(-10, 10) / 10000);
                $newLongitude = $currentLongitude + (rand(-10, 10) / 10000);

                // Сохраняем новое местоположение только в таблице cruise_locations
                $cruise->locations()->create([
                    'latitude' => $newLatitude,
                    'longitude' => $newLongitude,
                    'recorded_at' => now(),
                ]);
            }

            return response()->json(['message' => 'Местоположения круизов обновлены'], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating cruise locations: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при обновлении местоположений круизов'], 500);
        }
    }
}