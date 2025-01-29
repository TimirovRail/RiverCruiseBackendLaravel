<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id(); // Автоматическое поле для ID
            $table->string('title'); // Заголовок услуги
            $table->string('subtitle'); // Подзаголовок услуги
            $table->text('description'); // Описание услуги
            $table->decimal('price', 10, 2); // Цена услуги (с точностью до двух знаков после запятой)
            $table->string('img')->nullable(); // Путь к изображению (может быть пустым)
            $table->timestamps(); // Столбцы created_at и updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service');
    }
};
