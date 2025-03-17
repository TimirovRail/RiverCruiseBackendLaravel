<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;

class PhotoController extends Controller
{
    public function store(Request $request)
    {
        $userId = $request->input('user_id');
        if (!$userId || !is_numeric($userId)) {
            return response()->json(['error' => 'Некорректный user_id'], 400);
        }

        $photos = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('public/user_photos');
                $url = Storage::url($path);
                $name = $photo->getClientOriginalName();

                $photoModel = new Photo([
                    'user_id' => (int) $userId,
                    'name' => $name,
                    'url' => $url,
                ]);
                $photoModel->save();

                $photos[] = [
                    'url' => $url,
                    'name' => $name,
                    'user_id' => (int) $userId,
                ];
            }
        }

        return response()->json(['photos' => $photos]);
    }
}