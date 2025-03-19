<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Souvenir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $request->validate([
            'souvenir_id' => 'required|exists:souvenirs,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $cartItem = Cart::where('user_id', $user->id)
            ->where('souvenir_id', $request->souvenir_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity ?? 1;
            $cartItem->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'souvenir_id' => $request->souvenir_id,
                'quantity' => $request->quantity ?? 1,
            ]);
        }

        return response()->json(['message' => 'Товар добавлен в корзину'], 200);
    }

    public function getCart()
    {
        $cartItems = Cart::with('souvenir')
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($cartItems, 200);
    }
    public function updateQuantity(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'action' => 'required|in:increase,decrease', // Добавляем тип действия
        ]);

        $cartItem = Cart::where('id', $request->cart_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($request->action === 'increase') {
            $cartItem->quantity += 1;
        } elseif ($request->action === 'decrease' && $cartItem->quantity > 1) {
            $cartItem->quantity -= 1;
        }

        $cartItem->save();

        return response()->json([
            'message' => 'Количество обновлено',
            'quantity' => $cartItem->quantity
        ], 200);
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'cart_id' => 'required|exists:carts,id',
        ]);

        $deleted = Cart::where('id', $request->cart_id)
            ->where('user_id', Auth::id())
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Товар не найден'], 404);
        }

        return response()->json(['message' => 'Товар удален из корзины'], 200);
    }
}