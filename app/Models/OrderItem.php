<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',      // ID заказа
        'souvenir_id',  // ID товара
        'quantity',      // Количество товара
        'price',        // Цена товара на момент заказа
    ];

    /**
     * Связь с моделью Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Связь с моделью Souvenir.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function souvenir()
    {
        return $this->belongsTo(Souvenir::class);
    }
}