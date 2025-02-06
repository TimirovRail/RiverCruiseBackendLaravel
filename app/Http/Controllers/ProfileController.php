<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Feedback;
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
        $feedbacks = Feedback::all();
        $bookings = Booking::all();

        return response()->json([
            'feedbacks' => $feedbacks,
            'bookings' => $bookings,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
}



}

