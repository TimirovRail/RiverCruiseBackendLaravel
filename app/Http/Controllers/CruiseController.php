<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cruise;
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
            'cabins' => 'required|integer',
            'price_per_person' => 'required|numeric',
            'image_path' => 'nullable|string',
            'features' => 'nullable|array',
            'departure_datetime' => 'required|date',
            'arrival_datetime' => 'required|date|after:departure_datetime',
            'status' => 'required|in:planned,active,completed,canceled',
        ]);

        $cruise = Cruise::create($validated);

        // Создаём 4-6 рейсов
        $departure = \Carbon\Carbon::parse($validated['departure_datetime']);
        for ($i = 0; $i < rand(4, 6); $i++) {
            $cruise->schedules()->create([
                'departure_datetime' => $departure->copy()->addDays($i * 7),
                'arrival_datetime' => $departure->copy()->addDays($i * 7 + 3), // +3 дня для примера
                'total_places' => 50,
                'available_places' => 50,
                'status' => 'planned',
            ]);
        }

        return response()->json($cruise->load('schedules'), 201);
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