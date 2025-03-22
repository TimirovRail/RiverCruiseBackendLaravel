<?php

namespace App\Http\Controllers;

use App\Models\CruiseSchedule;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cruise_schedule_id' => 'required|exists:cruise_schedules,id',
            'seats' => 'required|integer|min:1',
            'cabin_class' => 'required',
            'extras' => 'nullable|array',
            'comment' => 'nullable|string|max:1000',
            'user_id' => 'required|exists:users,id',
        ]);

        $schedule = CruiseSchedule::findOrFail($validated['cruise_schedule_id']);
        if ($schedule->available_places < $validated['seats']) {
            return response()->json(['error' => 'Недостаточно мест'], 400);
        }

        $cruise = $schedule->cruise;
        $totalPrice = $cruise->price_per_person * $validated['seats'];

        // Преобразуем extras в массив, если он пришёл как строка
        $extras = $validated['extras'];
        if (is_string($extras)) {
            $extras = json_decode($extras, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Некорректный формат дополнительных услуг'], 400);
            }
        }

        $booking = Booking::create([
            'user_id' => $validated['user_id'],
            'cruise_schedule_id' => $schedule->id,
            'seats' => $validated['seats'],
            'cabin_class' => $validated['cabin_class'],
            'total_price' => $totalPrice,
            'extras' => $extras ?? [],
            'comment' => $validated['comment'],
        ]);

        $schedule->decrement('available_places', $validated['seats']); // Уже есть, но проверим

        return response()->json(['message' => 'Бронь создана', 'booking' => $booking], 201);
    }

    public function index()
    {
        $bookings = Booking::with('cruiseSchedule.cruise')->where('user_id', auth()->id())->get();
        return response()->json($bookings);
    }
}