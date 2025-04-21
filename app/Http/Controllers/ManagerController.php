<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class ManagerController extends Controller
{
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
                ->with(['cruiseSchedule.cruise'])
                ->first();

            if (!$booking) {
                return response()->json(['valid' => false, 'message' => 'Билет не найден или не оплачен'], 404);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Билет действителен',
                'ticket' => [
                    'cruise_name' => $booking->cruiseSchedule->cruise->name,
                    'departure_datetime' => $booking->cruiseSchedule->departure_datetime,
                    'economy_seats' => $booking->economy_seats,
                    'standard_seats' => $booking->standard_seats,
                    'luxury_seats' => $booking->luxury_seats,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Ошибка проверки билета: ' . $e->getMessage());
            return response()->json(['valid' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }
}