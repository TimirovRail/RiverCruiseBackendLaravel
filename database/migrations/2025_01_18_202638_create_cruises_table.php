<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cruises', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Имя круиза
            $table->text('description'); // Описание круиза
            $table->string('river'); // Река (например, Волга, Лена и т.д.)
            $table->integer('total_places'); // Общее количество мест
            $table->integer('available_places'); // Количество доступных мест
            $table->integer('cabins'); // Количество кают
            $table->date('start_date'); // Дата начала круиза
            $table->date('end_date'); // Дата окончания круиза
            $table->decimal('price_per_person', 8, 2); // Стоимость за человека
            $table->decimal('total_distance', 10, 2)->nullable(); // Общая длина маршрута (км)
            $table->json('features')->nullable(); // Особенности круиза (например, питание, развлечения)
            $table->string('image_path')->nullable(); // Путь к изображению
            $table->timestamps(); // created_at и updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cruises');
    }
};
