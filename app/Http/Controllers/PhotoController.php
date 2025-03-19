<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;

class PhotoController extends Controller
{
    public function index()
    {
        $photos = Photo::all()->pluck('url'); // Получаем только URL фотографий
        return response()->json($photos);
    }
    public function destroy($id)
    {
        // Проверка, что id является числом
        if (!is_numeric($id)) {
            return response()->json(['error' => 'Некорректный ID фотографии'], 400);
        }

        $photo = Photo::find($id);

        if (!$photo) {
            return response()->json(['error' => 'Фотография не найдена'], 404);
        }

        $photo->delete();

        return response()->json(['message' => 'Фотография успешно удалена']);
    }
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
    public function getUserPhotos($user_id)
    {
        // Получаем фотографии пользователя по user_id
        $photos = Photo::where('user_id', $user_id)->get();

        if ($photos->isEmpty()) {
            return response()->json(['message' => 'Фотографии не найдены'], 404);
        }

        return response()->json(['photos' => $photos]);
    }
}