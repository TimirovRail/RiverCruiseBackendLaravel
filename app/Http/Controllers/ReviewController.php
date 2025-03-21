<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cruise_id' => 'required|exists:cruises,id',
            'comment' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $user = auth()->user();
        $booking = Booking::where('user_id', $user->id)
            ->whereHas('cruiseSchedule', function ($query) use ($validated) {
                $query->where('cruise_id', $validated['cruise_id'])
                      ->where('arrival_datetime', '<', now());
            })
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Вы не можете оставить отзыв: круиз не завершён или вы в нём не участвовали'], 403);
        }

        if ($booking->review) {
            return response()->json(['error' => 'Вы уже оставили отзыв для этого круиза'], 403);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'cruise_id' => $validated['cruise_id'],
            'booking_id' => $booking->id,
            'comment' => $validated['comment'],
            'rating' => $validated['rating'],
        ]);

        return response()->json(['message' => 'Отзыв добавлен', 'review' => $review], 201);
    }
}