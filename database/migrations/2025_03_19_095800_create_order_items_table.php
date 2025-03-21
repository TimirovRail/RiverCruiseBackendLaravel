<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // Автоинкрементный ID
            $table->unsignedBigInteger('order_id'); // ID заказа
            $table->unsignedBigInteger('souvenir_id'); // ID товара
            $table->integer('quantity'); // Количество товара
            $table->decimal('price', 10, 2); // Цена товара на момент заказа
            $table->timestamps(); // Поля created_at и updated_at

            // Внешние ключи
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('souvenir_id')->references('id')->on('souvenirs')->onDelete('cascade');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
}