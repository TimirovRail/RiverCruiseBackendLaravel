<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Получить все услуги.
     */
    public function index()
    {
        return response()->json(Service::all(), 200);
    }

    /**
     * Получить одну услугу.
     */
    public function show($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Услуга не найдена'], 404);
        }
        return response()->json($service, 200);
    }

    /**
     * Создать новую услугу.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'img' => 'nullable|string',
        ]);

        $service = Service::create($validated);
        return response()->json($service, 201);
    }

    /**
     * Обновить услугу.
     */
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Услуга не найдена'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'img' => 'nullable|string',
        ]);

        $service->update($validated);
        return response()->json($service, 200);
    }

    /**
     * Удалить услугу.
     */
    public function destroy($id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => 'Услуга не найдена'], 404);
        }

        $service->delete();
        return response()->json(['message' => 'Услуга удалена'], 200);
    }
}
