<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use Carbon\Carbon;

class ManagerController extends Controller
{
    /**
     * Display the manager profile.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = Auth::user();
        if (strtolower($user->role) !== 'manager') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'user' => $user,
            'message' => 'Welcome to Manager Profile!',
        ]);
    }

    /**
     * Verify the ticket based on QR code data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyTicket(Request $request)
    {
        try {
            $data = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
                'user_id' => 'required|integer|exists:users,id',
                'cruise_schedule_id' => 'required|integer|exists:cruise_schedules,id',
                'economy_seats' => 'nullable|integer|min:0',
                'standard_seats' => 'nullable|integer|min:0',
                'luxury_seats' => 'nullable|integer|min:0',
            ]);

            $data['economy_seats'] = (int) ($request->economy_seats ?? 0);
            $data['standard_seats'] = (int) ($request->standard_seats ?? 0);
            $data['luxury_seats'] = (int) ($request->luxury_seats ?? 0);

            $booking = Booking::where('id', $data['booking_id'])
                ->where('user_id', $data['user_id'])
                ->where('cruise_schedule_id', $data['cruise_schedule_id'])
                ->where('is_paid', true)
                ->with(['cruiseSchedule.cruise', 'user'])
                ->first();

            if (!$booking) {
                return response()->json(['valid' => false, 'message' => 'Билет не найден или не оплачен'], 404);
            }

            // Проверка соответствия мест
            if (
                $booking->economy_seats !== $data['economy_seats'] ||
                $booking->standard_seats !== $data['standard_seats'] ||
                $booking->luxury_seats !== $data['luxury_seats']
            ) {
                return response()->json(['valid' => false, 'message' => 'Количество мест не совпадает с бронированием'], 400);
            }

            // Проверка даты круиза (день в день)
            $departureDate = Carbon::parse($booking->cruiseSchedule->departure_datetime)->startOfDay();
            $currentDate = Carbon::now()->startOfDay();

            if (!$departureDate->equalTo($currentDate)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Круиз не проводится сегодня. Дата круиза: ' . $departureDate->toDateString(),
                ], 400);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Билет действителен',
                'ticket' => [
                    'booking_id' => $booking->id, // Добавляем booking_id для последующего использования
                    'user_id' => $booking->user_id, // Добавляем user_id
                    'cruise_schedule_id' => $booking->cruise_schedule_id, // Добавляем cruise可怕_schedule_id
                    'cruise_name' => $booking->cruiseSchedule->cruise->name,
                    'departure_datetime' => $booking->cruiseSchedule->departure_datetime,
                    'economy_seats' => $booking->economy_seats,
                    'standard_seats' => $booking->standard_seats,
                    'luxury_seats' => $booking->luxury_seats,
                    'total_price' => $booking->total_price,
                    'comment' => $booking->comment,
                    'extras' => $booking->extras,
                    'attended' => $booking->attended, // Возвращаем статус attended
                ],
                'user' => [
                    'name' => $booking->user->name,
                    'email' => $booking->user->email,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка проверки билета: ' . $e->getMessage());
            return response()->json(['valid' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark the booking as attended.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsAttended(Request $request)
    {
        try {
            $data = $request->validate([
                'booking_id' => 'required|integer|exists:bookings,id',
                'user_id' => 'required|integer|exists:users,id',
                'cruise_schedule_id' => 'required|integer|exists:cruise_schedules,id',
            ]);

            $booking = Booking::where('id', $data['booking_id'])
                ->where('user_id', $data['user_id'])
                ->where('cruise_schedule_id', $data['cruise_schedule_id'])
                ->where('is_paid', true)
                ->with(['cruiseSchedule'])
                ->first();

            if (!$booking) {
                return response()->json(['success' => false, 'message' => 'Билет не найден или не оплачен'], 404);
            }

            // Проверка даты круиза (день в день)
            $departureDate = Carbon::parse($booking->cruiseSchedule->departure_datetime)->startOfDay();
            $currentDate = Carbon::now()->startOfDay();

            if (!$departureDate->equalTo($currentDate)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя отметить участие: круиз не проводится сегодня. Дата круиза: ' . $departureDate->toDateString(),
                ], 400);
            }

            // Проверка, не отмечено ли уже участие
            if ($booking->attended) {
                return response()->json([
                    'success' => false,
                    'message' => 'Участие уже отмечено ранее',
                ], 400);
            }

            // Обновляем статус attended
            $booking->attended = true;
            $booking->save();

            return response()->json([
                'success' => true,
                'message' => 'Участие в круизе успешно отмечено',
                'attended' => $booking->attended,
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка отметки участия: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }
}