<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CruiseSchedule;
use App\Models\Review;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FeedbackController extends Controller
{
    /**
     * Получить список круизов, доступных для отзыва
     */
    public function getAvailableCruises(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            \Log::error('Пользователь не аутентифицирован в getAvailableCruises');
            return response()->json(['error' => 'Пользователь не аутентифицирован'], 401);
        }

        \Log::info('Получение доступных круизов для пользователя:', ['user_id' => $user->id]);

        // Загружаем бронирования с расписаниями и круизами
        $bookings = Booking::where('user_id', $user->id)
            ->with(['cruiseSchedule.cruise'])
            ->get();

        \Log::info('Найдено бронирований:', ['count' => $bookings->count(), 'bookings' => $bookings->toArray()]);

        // Загружаем существующие отзывы пользователя
        $existingReviews = Review::where('user_id', $user->id)
            ->pluck('booking_id')
            ->toArray();

        \Log::info('Существующие отзывы:', ['booking_ids' => $existingReviews]);

        // Фильтруем бронирования
        $availableCruises = $bookings->filter(function ($booking) use ($existingReviews) {
            // Пропускаем, если для бронирования уже есть отзыв
            if (in_array($booking->id, $existingReviews)) {
                \Log::info('Бронирование уже имеет отзыв:', ['booking_id' => $booking->id]);
                return false;
            }

            if (!$booking->cruiseSchedule) {
                \Log::warning('Бронирование без расписания:', ['booking_id' => $booking->id]);
                return false;
            }

            $departureDate = Carbon::parse($booking->cruiseSchedule->departure_datetime);
            \Log::info('Проверка даты круиза:', [
                'booking_id' => $booking->id,
                'departure_datetime' => $booking->cruiseSchedule->departure_datetime,
                'is_past' => $departureDate->isPast(),
            ]);
            return $departureDate->isPast();
        })->map(function ($booking) {
            if (!$booking->cruiseSchedule->cruise) {
                \Log::warning('Расписание без круиза:', ['schedule_id' => $booking->cruiseSchedule->id]);
                return null;
            }

            return [
                'cruise_id' => $booking->cruiseSchedule->cruise->id,
                'cruise_name' => $booking->cruiseSchedule->cruise->name,
                'booking_id' => $booking->id,
                'departure_datetime' => $booking->cruiseSchedule->departure_datetime,
            ];
        })->filter()->values();

        \Log::info('Доступные круизы:', ['available_cruises' => $availableCruises->toArray()]);

        return response()->json($availableCruises);
    }

    /**
     * Сохранить отзыв
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cruise_id' => 'required|exists:cruises,id',
            'booking_id' => 'required|exists:bookings,id',
            'comment' => 'required|string|max:1000',
            'rating' => 'required|integer|between:1,5',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = $request->user();
        if ($user->id !== (int) $validated['user_id']) {
            return response()->json(['error' => 'Вы не можете оставить отзыв от имени другого пользователя'], 403);
        }

        // Проверяем, что бронирование принадлежит пользователю
        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', $user->id)
            ->with('cruiseSchedule.cruise')
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Бронирование не найдено или не принадлежит вам'], 404);
        }

        // Проверяем, что круиз соответствует бронированию
        if ($booking->cruiseSchedule->cruise->id !== (int) $validated['cruise_id']) {
            return response()->json(['error' => 'Круиз не соответствует бронированию'], 400);
        }

        // Проверяем, что дата круиза истекла
        $departureDate = Carbon::parse($booking->cruiseSchedule->departure_datetime);
        if (!$departureDate->isPast()) {
            return response()->json(['error' => 'Вы можете оставить отзыв только после завершения круиза'], 400);
        }

        // Проверяем, не оставлял ли пользователь уже отзыв для этого бронирования
        $existingReview = Review::where('booking_id', $validated['booking_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return response()->json(['error' => 'Вы уже оставили отзыв для этого бронирования'], 400);
        }

        // Создаём отзыв
        $review = Review::create([
            'user_id' => $validated['user_id'],
            'cruise_id' => $validated['cruise_id'],
            'booking_id' => $validated['booking_id'],
            'comment' => $validated['comment'],
            'rating' => $validated['rating'],
        ]);

        return response()->json(['message' => 'Отзыв успешно добавлен', 'review' => $review], 201);
    }

    /**
     * Получить все отзывы (для отображения на сайте)
     */
    public function index()
    {
        $reviews = Review::with(['user', 'cruise'])->get();
        return response()->json($reviews);
    }
}