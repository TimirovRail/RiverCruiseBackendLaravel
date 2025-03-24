<?php

namespace App\Http\Controllers;

use App\Models\CruiseSchedule;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // Логируем входящие данные
        \Log::info('BookingController: Входящие данные', $request->all());

        $validated = $request->validate([
            'cruise_schedule_id' => 'required|exists:cruise_schedules,id',
            'total_seats' => 'required|integer|min:1',
            'economy_seats' => 'required|integer|min:0',
            'standard_seats' => 'required|integer|min:0',
            'luxury_seats' => 'required|integer|min:0',
            'extras' => 'nullable|array',
            'comment' => 'nullable|string|max:1000',
            'user_id' => 'required|exists:users,id',
        ]);

        $schedule = CruiseSchedule::findOrFail($validated['cruise_schedule_id']);

        // Проверяем, что сумма мест по классам совпадает с total_seats
        $totalSeats = (int) $validated['total_seats'];
        $economySeats = (int) $validated['economy_seats'];
        $standardSeats = (int) $validated['standard_seats'];
        $luxurySeats = (int) $validated['luxury_seats'];
        $sumOfSeats = $economySeats + $standardSeats + $luxurySeats;

        \Log::info('BookingController: Проверка суммы мест', [
            'total_seats' => $totalSeats,
            'economy_seats' => $economySeats,
            'standard_seats' => $standardSeats,
            'luxury_seats' => $luxurySeats,
            'sum_of_seats' => $sumOfSeats,
        ]);

        if ($sumOfSeats !== $totalSeats) {
            return response()->json(['error' => "Сумма мест по классам ($sumOfSeats) не совпадает с общим количеством мест ($totalSeats)"], 400);
        }

        // Проверяем доступность мест для каждого класса
        if ($economySeats > $schedule->available_economy_places) {
            return response()->json(['error' => 'Недостаточно мест для класса "Эконом"'], 400);
        }
        if ($standardSeats > $schedule->available_standard_places) {
            return response()->json(['error' => 'Недостаточно мест для класса "Стандарт"'], 400);
        }
        if ($luxurySeats > $schedule->available_luxury_places) {
            return response()->json(['error' => 'Недостаточно мест для класса "Люкс"'], 400);
        }

        $cruise = $schedule->cruise;

        // Рассчитываем цену с учётом множителей для каждого класса
        $totalPrice = 0;
        $totalPrice += $economySeats * $cruise->price_per_person * 1; // Эконом: x1
        $totalPrice += $standardSeats * $cruise->price_per_person * 1.5; // Стандарт: x1.5
        $totalPrice += $luxurySeats * $cruise->price_per_person * 2; // Люкс: x2

        // Преобразуем extras в массив, если он пришёл как строка
        $extras = $validated['extras'];
        if (is_string($extras)) {
            $extras = json_decode($extras, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Некорректный формат дополнительных услуг'], 400);
            }
        }

        // Создаём бронирование
        $booking = Booking::create([
            'user_id' => $validated['user_id'],
            'cruise_schedule_id' => $validated['cruise_schedule_id'],
            'economy_seats' => $economySeats,
            'standard_seats' => $standardSeats,
            'luxury_seats' => $luxurySeats,
            'total_price' => $totalPrice,
            'extras' => $extras ?? [],
            'comment' => $validated['comment'],
        ]);

        \Log::info('BookingController: Создано бронирование', [
            'booking_id' => $booking->id,
            'economy_seats' => $booking->economy_seats,
            'standard_seats' => $booking->standard_seats,
            'luxury_seats' => $booking->luxury_seats,
        ]);

        // Уменьшаем количество доступных мест для каждого класса
        $schedule->decrement('available_economy_places', $economySeats);
        $schedule->decrement('available_standard_places', $standardSeats);
        $schedule->decrement('available_luxury_places', $luxurySeats);

        // Обновляем общее количество доступных мест
        $schedule->available_places = $schedule->available_economy_places +
            $schedule->available_standard_places +
            $schedule->available_luxury_places;
        $schedule->save();

        return response()->json(['message' => 'Бронь создана', 'booking' => $booking], 201);
    }

    public function index()
    {
        $bookings = Booking::with('cruiseSchedule.cruise')->where('user_id', auth()->id())->get();
        return response()->json($bookings);
    }
}