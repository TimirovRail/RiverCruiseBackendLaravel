<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservedSeatsTable extends Migration
{
    /**
     * Выполнение миграции: создание таблицы reserved_seats
     */
    public function up()
    {
        Schema::create('reserved_seats', function (Blueprint $table) {
            $table->id(); // Автоинкрементный первичный ключ
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade'); // Внешний ключ на таблицу bookings с каскадным удалением
            $table->foreignId('schedule_id')
                  ->constrained('cruise_schedules')
                  ->onDelete('cascade'); // Внешний ключ на таблицу cruise_schedules с каскадным удалением
            $table->string('category'); // Категория места: 'economy', 'standard', 'luxury'
            $table->integer('seat_number'); // Номер места в категории
            $table->timestamps(); // Поля created_at и updated_at
            $table->unique(['schedule_id', 'category', 'seat_number']); // Уникальный индекс для предотвращения дублирования мест
        });
    }

    /**
     * Откат миграции: удаление таблицы reserved_seats
     */
    public function down()
    {
        Schema::dropIfExists('reserved_seats'); // Удаляет таблицу, если она существует
    }
}