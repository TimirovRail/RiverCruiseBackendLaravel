<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'address' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $user = Auth::user();

        // Создаем заказ
        $order = Order::create([
            'user_id' => $user->id,
            'address' => $request->address,
            'payment_method' => $request->payment_method,
            'total' => 0, // Здесь можно добавить логику расчета суммы заказа
        ]);

        // Очищаем корзину
        Cart::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Заказ успешно оформлен']);
    }
}