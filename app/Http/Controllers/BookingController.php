<?php

namespace App\Http\Controllers;

use App\Models\CruiseSchedule;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\ReservedSeat;

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
    public function getSeats($scheduleId)
    {
        $schedule = CruiseSchedule::findOrFail($scheduleId);
        $reservedSeats = ReservedSeat::where('schedule_id', $scheduleId)->get()->groupBy('category');

        $seats = [
            'economy' => [
                'total' => $schedule->economy_places,
                'available' => $schedule->available_economy_places,
                'taken' => $reservedSeats->get('economy', collect())->pluck('seat_number')->all()
            ],
            'standard' => [
                'total' => $schedule->standard_places,
                'available' => $schedule->available_standard_places,
                'taken' => $reservedSeats->get('standard', collect())->pluck('seat_number')->all()
            ],
            'luxury' => [
                'total' => $schedule->luxury_places,
                'available' => $schedule->available_luxury_places,
                'taken' => $reservedSeats->get('luxury', collect())->pluck('seat_number')->all()
            ],
        ];

        return response()->json($seats);
    }

    public function reserveSeats(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $schedule = CruiseSchedule::findOrFail($booking->cruise_schedule_id);
        $seats = $request->input('seats');

        // Подсчитываем количество мест по категориям
        $newEconomySeats = count($seats['economy'] ?? []);
        $newStandardSeats = count($seats['standard'] ?? []);
        $newLuxurySeats = count($seats['luxury'] ?? []);

        foreach ($seats as $category => $seatNumbers) {
            $availableField = "available_{$category}_places";
            $currentAvailable = $schedule->$availableField;
            if (count($seatNumbers) > $currentAvailable) {
                return response()->json(['error' => "Недостаточно мест в категории $category"], 400);
            }

            // Проверяем, не заняты ли места
            $taken = ReservedSeat::where('schedule_id', $schedule->id)
                ->where('category', $category)
                ->whereIn('seat_number', $seatNumbers)
                ->exists();
            if ($taken) {
                return response()->json(['error' => "Некоторые места в категории $category уже заняты"], 400);
            }

            // Резервируем места
            foreach ($seatNumbers as $seatNumber) {
                ReservedSeat::create([
                    'booking_id' => $bookingId,
                    'schedule_id' => $schedule->id,
                    'category' => $category,
                    'seat_number' => $seatNumber,
                ]);
            }
            $schedule->decrement($availableField, count($seatNumbers));
        }

        // Обновляем количество мест в бронировании
        $booking->economy_seats = $newEconomySeats;
        $booking->standard_seats = $newStandardSeats;
        $booking->luxury_seats = $newLuxurySeats;

        // Пересчитываем стоимость
        $cruise = $schedule->cruise;
        $totalPrice = 0;
        $totalPrice += $newEconomySeats * $cruise->price_per_person * 1;   // Эконом: x1
        $totalPrice += $newStandardSeats * $cruise->price_per_person * 1.5; // Стандарт: x1.5
        $totalPrice += $newLuxurySeats * $cruise->price_per_person * 2;     // Люкс: x2
        $booking->total_price = $totalPrice;

        $booking->save();

        $schedule->available_places = $schedule->available_economy_places +
            $schedule->available_standard_places +
            $schedule->available_luxury_places;
        $schedule->save();

        return response()->json([
            'message' => 'Места успешно зарезервированы',
            'booking' => $booking
        ]);
    }
    public function markAsPaid($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $booking->is_paid = true;
        $booking->save();

        return response()->json(['message' => 'Билет отмечен как оплаченный', 'booking' => $booking]);
    }
}