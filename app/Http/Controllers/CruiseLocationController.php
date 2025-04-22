<?php

namespace App\Http\Controllers;

use App\Models\Cruise;
use App\Models\CruiseLocation;
use Illuminate\Http\Request;

class CruiseLocationController extends Controller
{
    public function getCurrentLocations()
    {
        $cruises = Cruise::with('latestLocation')->get()->map(function ($cruise) {
            return [
                'id' => $cruise->id,
                'name' => $cruise->name,
                'river' => $cruise->river,
                'latitude' => $cruise->latestLocation ? $cruise->latestLocation->latitude : null,
                'longitude' => $cruise->latestLocation ? $cruise->latestLocation->longitude : null,
            ];
        });

        return response()->json($cruises);
    }
    public function updateLocations()
    {
        $cruises = Cruise::all();
        foreach ($cruises as $cruise) {
            $latest = $cruise->latestLocation;
            // Если координат ещё нет, задаём начальные значения
            $newLat = $latest ? $latest->latitude + 0.005 : 57.6261;
            $newLng = $latest ? $latest->longitude + 0.005 : 39.8845;

            CruiseLocation::create([
                'cruise_id' => $cruise->id,
                'latitude' => $newLat,
                'longitude' => $newLng,
                'recorded_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Locations updated']);
    }
}