<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}