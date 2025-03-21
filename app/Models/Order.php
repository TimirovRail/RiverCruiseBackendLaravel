<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Поля, которые можно массово назначать.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',       // ID пользователя
        'address',       // Адрес доставки
        'payment_method', // Способ оплаты
        'total',         // Общая сумма заказа
        'status',        // Статус заказа (например, "в обработке", "доставлен")
    ];

    /**
     * Связь с моделью User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь с моделью OrderItem (если у вас есть отдельная таблица для товаров в заказе).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}