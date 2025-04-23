<?php

namespace App\Http\Controllers;


use App\Models\CruiseSchedule;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Models\ReservedSeat;
use Illuminate\Support\Facades\DB;
use App\Models\CruiseSeat;
use App\Models\BookedSeat;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Booking::with(['user', 'cruiseSchedule.cruise']);

        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $bookings = $query->get()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'user_id' => $booking->user_id,
                'cruise_schedule_id' => $booking->cruise_schedule_id,
                'economy_seats' => $booking->economy_seats,
                'standard_seats' => $booking->standard_seats,
                'luxury_seats' => $booking->luxury_seats,
                'total_price' => $booking->total_price,
                'extras' => $booking->extras,
                'comment' => $booking->comment,
                'is_paid' => $booking->is_paid,
                'status' => $booking->is_paid ? 'Подтверждено' : 'В ожидании',
                'user_name' => $booking->user ? $booking->user->name : '—',
                'user_email' => $booking->user ? $booking->user->email : '—',
                'cruise_name' => $booking->cruiseSchedule && $booking->cruiseSchedule->cruise ? $booking->cruiseSchedule->cruise->name : '—',
                'departure_datetime' => $booking->cruiseSchedule ? $booking->cruiseSchedule->departure_datetime : null,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
            ];
        });

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
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

        $totalPrice = 0;
        $totalPrice += $economySeats * $cruise->price_per_person * 1; // Эконом: x1
        $totalPrice += $standardSeats * $cruise->price_per_person * 1.5; // Стандарт: x1.5
        $totalPrice += $luxurySeats * $cruise->price_per_person * 2; // Люкс: x2

        $extras = $validated['extras'];
        if (is_string($extras)) {
            $extras = json_decode($extras, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Некорректный формат дополнительных услуг'], 400);
            }
        }

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

        $schedule->decrement('available_economy_places', $economySeats);
        $schedule->decrement('available_standard_places', $standardSeats);
        $schedule->decrement('available_luxury_places', $luxurySeats);

        $schedule->available_places = $schedule->available_economy_places +
            $schedule->available_standard_places +
            $schedule->available_luxury_places;
        $schedule->save();

        return response()->json(['message' => 'Бронь создана', 'booking' => $booking], 201);
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

        $newEconomySeats = count($seats['economy'] ?? []);
        $newStandardSeats = count($seats['standard'] ?? []);
        $newLuxurySeats = count($seats['luxury'] ?? []);

        foreach ($seats as $category => $seatNumbers) {
            $availableField = "available_{$category}_places";
            $currentAvailable = $schedule->$availableField;
            if (count($seatNumbers) > $currentAvailable) {
                return response()->json(['error' => "Недостаточно мест в категории $category"], 400);
            }

            $taken = ReservedSeat::where('schedule_id', $schedule->id)
                ->where('category', $category)
                ->whereIn('seat_number', $seatNumbers)
                ->exists();
            if ($taken) {
                return response()->json(['error' => "Некоторые места в категории $category уже заняты"], 400);
            }

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

        $booking->economy_seats = $newEconomySeats;
        $booking->standard_seats = $newStandardSeats;
        $booking->luxury_seats = $newLuxurySeats;

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
    public function destroy($id)
    {
        try {
            // Находим бронь
            $booking = Booking::findOrFail($id);

            // Проверяем, что бронь не оплачена
            if ($booking->is_paid) {
                return response()->json(['message' => 'Cannot cancel paid booking'], 400);
            }

            // Начинаем транзакцию для обеспечения целостности данных
            DB::beginTransaction();

            // 1. Освобождаем места в cruise_seats
            $cruiseScheduleId = $booking->cruise_schedule_id;

            // Обновляем available_seats для каждого типа мест
            if ($booking->economy_seats > 0) {
                CruiseSeat::where('cruise_schedule_id', $cruiseScheduleId)
                    ->where('seat_type', 'economy')
                    ->increment('available_seats', $booking->economy_seats);
            }

            if ($booking->standard_seats > 0) {
                CruiseSeat::where('cruise_schedule_id', $cruiseScheduleId)
                    ->where('seat_type', 'standard')
                    ->increment('available_seats', $booking->standard_seats);
            }

            if ($booking->luxury_seats > 0) {
                CruiseSeat::where('cruise_schedule_id', $cruiseScheduleId)
                    ->where('seat_type', 'luxury')
                    ->increment('available_seats', $booking->luxury_seats);
            }

            // 2. Удаляем записи из booked_seats (конкретные места)
            BookedSeat::where('booking_id', $booking->id)->delete();

            // 3. Удаляем саму бронь
            $booking->delete();

            // Фиксируем транзакцию
            DB::commit();

            return response()->json(['message' => 'Booking cancelled successfully'], 200);
        } catch (\Exception $e) {
            // Откатываем транзакцию в случае ошибки
            DB::rollBack();

            \Log::error("Error cancelling booking ID: {$id}, Error: " . $e->getMessage());
            return response()->json(['message' => 'Error cancelling booking: ' . $e->getMessage()], 500);
        }
    }
}