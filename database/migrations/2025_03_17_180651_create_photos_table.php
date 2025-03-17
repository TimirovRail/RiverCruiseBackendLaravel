<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotosTable extends Migration
{
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id(); // Автоинкрементный ID
            $table->unsignedBigInteger('user_id'); // ID пользователя
            $table->string('name'); // Название фотографии
            $table->string('url'); // URL фотографии
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
        Schema::dropIfExists('photos');
    }
}
