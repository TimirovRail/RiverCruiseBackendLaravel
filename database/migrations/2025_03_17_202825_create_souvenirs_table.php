<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSouvenirsTable extends Migration
{
    /**
     * Запуск миграции.
     */
    public function up(): void
    {
        Schema::create('souvenirs', function (Blueprint $table) {
            $table->id(); // Автоинкрементный ID
            $table->string('title'); // Название сувенира
            $table->text('description'); // Описание сувенира
            $table->string('image'); // Путь к изображению
            $table->string('price'); // Цена сувенира
            $table->timestamps(); // Поля created_at и updated_at
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::dropIfExists('souvenirs');
    }
}