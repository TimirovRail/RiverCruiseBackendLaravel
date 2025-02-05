<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        try {
            $user = Auth::user()->load(['feedbacks', 'bookings']);
            return response()->json([
                'user' => $user,
                'feedbacks' => $user->feedbacks,
                'bookings' => $user->bookings,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}

