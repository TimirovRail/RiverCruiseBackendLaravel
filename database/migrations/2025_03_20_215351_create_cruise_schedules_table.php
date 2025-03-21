<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cruise_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cruise_id')->constrained()->onDelete('cascade'); // Связь с круизом
            $table->dateTime('departure_datetime'); // Дата и время отправления
            $table->dateTime('arrival_datetime'); // Дата и время возвращения
            $table->integer('total_places'); // Общее количество мест для этого рейса
            $table->integer('available_places'); // Доступные места
            $table->enum('status', ['planned', 'active', 'completed', 'canceled'])->default('planned'); // Статус рейса
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cruise_schedules');
    }
};