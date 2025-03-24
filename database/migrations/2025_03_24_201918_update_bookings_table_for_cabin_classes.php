<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Удаляем старые поля
            $table->dropColumn(['seats', 'cabin_class']);
            // Добавляем новые поля для мест по классам
            $table->integer('economy_seats')->default(0)->after('cruise_schedule_id');
            $table->integer('standard_seats')->default(0)->after('economy_seats');
            $table->integer('luxury_seats')->default(0)->after('standard_seats');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Восстанавливаем старые поля
            $table->integer('seats')->default(0);
            $table->string('cabin_class')->nullable();
            // Удаляем новые поля
            $table->dropColumn(['economy_seats', 'standard_seats', 'luxury_seats']);
        });
    }
};