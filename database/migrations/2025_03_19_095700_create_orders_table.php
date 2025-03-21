<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Автоинкрементный ID заказа
            $table->unsignedBigInteger('user_id'); // ID пользователя
            $table->string('address'); // Адрес доставки
            $table->string('payment_method'); // Способ оплаты
            $table->decimal('total', 10, 2); // Общая сумма заказа
            $table->string('status')->default('в обработке'); // Статус заказа
            $table->timestamps(); // Поля created_at и updated_at

            // Внешний ключ для связи с таблицей users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}