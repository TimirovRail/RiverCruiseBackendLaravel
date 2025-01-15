<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'feedback' => 'required|string',
            'cruise' => 'required|string',
        ]);

        $feedback = Feedback::create($validated);

        return response()->json($feedback, 201);
    }

    public function index()
    {
        $feedbacks = Feedback::all();
        return response()->json($feedbacks);
    }
}

