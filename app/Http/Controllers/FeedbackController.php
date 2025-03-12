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
            'user_id' => 'nullable|exists:users,id',
        ]);

        $feedback = Feedback::create($validated);

        return response()->json($feedback, 201);
    }

    public function index()
    {
        $feedbacks = Feedback::all();
        return response()->json($feedbacks);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email',
            'feedback' => 'sometimes|string',
            'cruise' => 'sometimes|string',
        ]);

        $feedback = Feedback::findOrFail($id);
        $feedback->update($request->all());
        return response()->json($feedback);
    }

    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();
        return response()->json(['message' => 'Отзыв успешно удалён']);
    }
}

