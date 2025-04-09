<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;

class PhotoController extends Controller
{
    public function index()
    {
        $photos = Photo::with('user')->get()->map(function ($photo) {
            return [
                'id' => $photo->id,
                'user_id' => $photo->user_id,
                'name' => $photo->name,
                'url' => $photo->url,
                'user_name' => $photo->user ? $photo->user->name : '—',
                'created_at' => $photo->created_at,
                'updated_at' => $photo->updated_at,
            ];
        });

        return response()->json($photos);
    }

    public function destroy($id)
    {
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
        \Log::info('Upload photos request:', [
            'user_id' => $request->input('user_id'),
            'files' => $request->file('photos'),
        ]);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Максимум 2MB на файл
        ]);

        if (!$request->hasFile('photos')) {
            \Log::error('No photos uploaded');
            return response()->json(['error' => 'Файлы не были загружены'], 400);
        }

        Storage::disk('public')->makeDirectory('user_photos');

        $photos = [];

        foreach ($request->file('photos') as $photo) {
            try {
                $path = $photo->store('user_photos', 'public');
                $url = Storage::url($path);
                $name = $photo->getClientOriginalName();

                $photoModel = new Photo([
                    'user_id' => (int) $validated['user_id'],
                    'name' => $name,
                    'url' => $url,
                ]);
                $photoModel->save();

                $photos[] = [
                    'url' => $url,
                    'name' => $name,
                    'user_id' => (int) $validated['user_id'],
                ];
            } catch (\Exception $e) {
                \Log::error('Failed to save photo: ' . $e->getMessage());
                return response()->json(['error' => 'Ошибка при сохранении фотографии: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['photos' => $photos], 200);
    }

    public function getUserPhotos($user_id)
    {
        $photos = Photo::where('user_id', $user_id)->get();

        if ($photos->isEmpty()) {
            return response()->json(['message' => 'Фотографии не найдены'], 404);
        }

        return response()->json(['photos' => $photos]);
    }
}