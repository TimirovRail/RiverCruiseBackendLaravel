<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Booking::all();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'cruise' => 'required|string|max:255',
            'date' => 'required|date',
            'seats' => 'required|integer|min:1|max:10',
            'cabinClass' => 'required|string|max:255',
            'extras' => 'nullable|array',
            'extras.*' => 'string|max:255',
            'comment' => 'nullable|string|max:1000',
            'user_id' => 'nullable|exists:users,id', // Добавляем user_id
        ]);

        $booking = Booking::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'cruise' => $validatedData['cruise'],
            'date' => $validatedData['date'],
            'seats' => $validatedData['seats'],
            'cabin_class' => $validatedData['cabinClass'],
            'extras' => json_encode($validatedData['extras']),
            'comment' => $validatedData['comment'],
            'user_id' => $validatedData['user_id'], // Сохраняем user_id
        ]);

        return response()->json(['message' => 'Booking successfully created!', 'data' => $booking], 201);
    }
}