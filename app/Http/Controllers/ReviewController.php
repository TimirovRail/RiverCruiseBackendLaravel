<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use App\Models\CruiseSchedule;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['user', 'cruise'])->get();
        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cruise_id' => 'required|exists:cruises,id',
            'booking_id' => 'required|exists:bookings,id',
            'comment' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();

        // Проверяем, что пользователь, отправляющий запрос, совпадает с user_id из запроса
        if ($user->id !== $validated['user_id']) {
            return response()->json(['error' => 'Вы не можете оставить отзыв от имени другого пользователя'], 403);
        }

        // Находим бронирование
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', $user->id)
            ->where('is_paid', true) // Проверяем, что билет оплачен
            ->where('attended', true) // Проверяем, что менеджер отметил участие
            ->whereHas('cruiseSchedule', function ($query) use ($validated) {
                $query->where('cruise_id', $validated['cruise_id'])
                    ->where('arrival_datetime', '<', now()); // Круиз завершён
            })
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Вы не можете оставить отзыв: круиз не завершён, не оплачен или вы в нём не участвовали'], 403);
        }

        // Проверяем, не оставил ли пользователь уже отзыв
        $existingReview = Review::where('booking_id', $booking->id)->first();
        if ($existingReview) {
            return response()->json(['error' => 'Вы уже оставили отзыв для этого круиза'], 403);
        }

        // Создаём отзыв
        $review = Review::create([
            'user_id' => $user->id,
            'cruise_id' => $validated['cruise_id'],
            'booking_id' => $booking->id,
            'comment' => $validated['comment'],
            'rating' => $validated['rating'],
        ]);

        return response()->json(['message' => 'Отзыв добавлен', 'review' => $review], 201);
    }

    public function cancel($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Отзыв не найден'], 404);
        }

        $review->is_active = false;
        $review->save();

        return response()->json(['message' => 'Отзыв отменён']);
    }

    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json(['message' => 'Отзыв не найден'], 404);
        }

        $review->delete();
        return response()->json(['message' => 'Отзыв удалён']);
    }

    // Новый метод для получения доступных круизов для отзыва
    public function availableCruises(Request $request)
    {
        $user = auth()->user();

        // Находим все бронирования пользователя, которые удовлетворяют условиям
        $bookings = Booking::where('user_id', $user->id)
            ->where('is_paid', true) // Билет оплачен
            ->where('attended', true) // Менеджер отметил участие
            ->whereDoesntHave('review') // Нет отзыва
            ->whereHas('cruiseSchedule', function ($query) {
                $query->where('arrival_datetime', '<', now()); // Круиз завершён
            })
            ->with(['cruiseSchedule.cruise'])
            ->get();

        // Формируем массив доступных круизов
        $availableCruises = $bookings->map(function ($booking) {
            return [
                'booking_id' => $booking->id,
                'cruise_id' => $booking->cruiseSchedule->cruise->id,
                'cruise_name' => $booking->cruiseSchedule->cruise->name,
                'departure_datetime' => $booking->cruiseSchedule->departure_datetime,
            ];
        });

        return response()->json($availableCruises);
    }
}