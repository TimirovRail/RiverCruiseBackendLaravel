<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CruiseController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SouvenirController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\ReviewController;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api')->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware('auth:api')->name('me');
    Route::get('/profile', [ProfileController::class, 'show'])->middleware('auth:api');
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/upload-photos', [PhotoController::class, 'store']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::get('/cart', [CartController::class, 'getCart']);
    Route::post('/cart/update', [CartController::class, 'updateQuantity']);
    Route::post('/cart/remove', [CartController::class, 'removeFromCart']);
    Route::post('/orders', [OrderController::class, 'store']);
});

Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return response()->json(['message' => 'Welcome, Admin!']);
    });
});

Route::get('/cruises', [CruiseController::class, 'index']);
Route::get('/cruise/{id}', [CruiseController::class, 'show']);
Route::post('/cruises', [CruiseController::class, 'store'])->middleware('auth:api');
Route::post('/cruises/{cruiseId}/schedules', [CruiseController::class, 'storeSchedule'])->middleware('auth:api');
Route::put('/cruises/{id}', [CruiseController::class, 'update'])->middleware('auth:api');
Route::delete('/cruises/{id}', [CruiseController::class, 'destroy'])->middleware('auth:api');

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{id}', [ServiceController::class, 'show']);
Route::post('/services', [ServiceController::class, 'store'])->middleware('auth:api');
Route::put('/services/{id}', [ServiceController::class, 'update'])->middleware('auth:api');
Route::delete('/services/{id}', [ServiceController::class, 'destroy'])->middleware('auth:api');

Route::get('/photos', [PhotoController::class, 'index']);

Route::delete('/photos/{id}', [PhotoController::class, 'destroy'])->middleware('auth:api');
Route::get('/user/photos/{user_id}', [PhotoController::class, 'getUserPhotos']);

Route::get('/souvenirs', [SouvenirController::class, 'index']);

Route::get('/feedbacks', [FeedbackController::class, 'index']);
Route::post('/feedbacks', [FeedbackController::class, 'store'])->middleware('auth:api');
Route::put('/feedbacks/{id}', [FeedbackController::class, 'update'])->middleware('auth:api');
Route::delete('/feedbacks/{id}', [FeedbackController::class, 'destroy'])->middleware('auth:api');

Route::get('/all-data', [ProfileController::class, 'allData']);

Route::middleware('auth:api')->group(function () {
    Route::get('/auth/available-cruises', [FeedbackController::class, 'getAvailableCruises']);
    Route::post('/auth/reviews', [FeedbackController::class, 'store']);
});

Route::get('/reviews', [FeedbackController::class, 'index']);
Route::middleware('auth:api')->group(function () {
    Route::get('/cruise-schedule/{schedule_id}/seats', [BookingController::class, 'getSeats']);
    Route::post('/bookings/{booking_id}/reserve-seats', [BookingController::class, 'reserveSeats']);
});
Route::post('/bookings/{bookingId}/mark-as-paid', [BookingController::class, 'markAsPaid']); // Без middleware