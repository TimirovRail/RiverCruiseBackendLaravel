<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartTable extends Migration
{
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // Автоинкрементный ID
            $table->unsignedBigInteger('user_id'); // ID пользователя
            $table->unsignedBigInteger('souvenir_id'); // ID сувенира
            $table->integer('quantity')->default(1); // Количество товара
            $table->timestamps(); // Поля created_at и updated_at

            // Внешние ключи
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('souvenir_id')->references('id')->on('souvenirs')->onDelete('cascade');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
}