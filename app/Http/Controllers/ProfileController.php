<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Review;
use App\Models\Booking;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Пользователь не аутентифицирован'], 401);
        }

        $userWithRelations = User::with(['feedbacks', 'bookings'])->where('id', $user->id)->first();

        return response()->json([
            'user' => $userWithRelations,
            'feedbacks' => $userWithRelations->feedbacks,
            'bookings' => $userWithRelations->bookings,
        ]);
    }

    public function allData()
    {
        try {
            // Загружаем отзывы из таблицы reviews
            $reviews = Review::with('cruise')->get()->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_id' => $review->user_id,
                    'comment' => $review->comment,
                    'rating' => $review->rating,
                    'cruise' => $review->cruise ? $review->cruise->name : 'Не указан',
                ];
            });

            // Загружаем бронирования с данными о расписании и круизе
            $bookings = Booking::with('cruiseSchedule.cruise')->get()->map(function ($booking) {
                \Log::info('Booking cruise:', [
                    'booking_id' => $booking->id,
                    'cruiseSchedule' => $booking->cruiseSchedule,
                    'cruise' => $booking->cruiseSchedule ? $booking->cruiseSchedule->cruise : null,
                ]);

                $extras = $booking->extras;
                if (is_string($extras)) {
                    $extras = array_map('trim', explode(',', $extras));
                } elseif (!is_array($extras)) {
                    $extras = [];
                }

                return [
                    'id' => $booking->id,
                    'user_id' => $booking->user_id,
                    'cruise' => $booking->cruiseSchedule && $booking->cruiseSchedule->cruise && is_object($booking->cruiseSchedule->cruise) ? $booking->cruiseSchedule->cruise->name : 'Не указан',
                    'date' => $booking->cruiseSchedule ? $booking->cruiseSchedule->departure_datetime : 'Не указана',
                    'seats' => $booking->seats,
                    'cabin_class' => $booking->cabin_class,
                    'total_price' => $booking->total_price,
                    'extras' => $extras,
                    'comment' => $booking->comment,
                ];
            });

            return response()->json([
                'reviews' => $reviews,
                'bookings' => $bookings,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}