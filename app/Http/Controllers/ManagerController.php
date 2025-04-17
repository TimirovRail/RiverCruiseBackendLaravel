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
        $request->validate([
            'booking_id' => 'required|integer',
            'user_id' => 'required|integer',
            'cruise_schedule_id' => 'required|integer',
            'economy_seats' => 'required|integer',
            'standard_seats' => 'required|integer',
            'luxury_seats' => 'required|integer',
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user_id)
            ->where('cruise_schedule_id', $request->cruise_schedule_id)
            ->first();

        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Бронирование не найдено',
            ], 404);
        }

        if (
            $booking->economy_seats != $request->economy_seats ||
            $booking->standard_seats != $request->standard_seats ||
            $booking->luxury_seats != $request->luxury_seats
        ) {
            return response()->json([
                'valid' => false,
                'message' => 'Данные мест не совпадают с бронированием',
            ], 400);
        }

        if (!$booking->is_paid) {
            return response()->json([
                'valid' => false,
                'message' => 'Бронирование не оплачено',
            ], 400);
        }

        $departureDate = new \DateTime($booking->departure_datetime);
        $now = new \DateTime();
        if ($departureDate < $now) {
            return response()->json([
                'valid' => false,
                'message' => 'Срок действия билета истёк',
            ], 400);
        }

        if ($booking->status === 'used') {
            return response()->json([
                'valid' => false,
                'message' => 'Билет уже использован',
            ], 400);
        }
        $booking->status = 'used';
        $booking->save();

        return response()->json([
            'valid' => true,
            'message' => 'Билет успешно подтверждён',
            'ticket' => [
                'cruise_name' => $booking->cruise_name,
                'departure_datetime' => $booking->departure_datetime,
                'economy_seats' => $booking->economy_seats,
                'standard_seats' => $booking->standard_seats,
                'luxury_seats' => $booking->luxury_seats,
            ],
        ]);
    }
}