<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            // Удаляем поля total_places и available_places
            $table->dropColumn(['total_places', 'available_places']);
            // Добавляем поле cabins_by_class (JSON)
            $table->json('cabins_by_class')->nullable()->after('cabins');
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            // Восстанавливаем удалённые поля
            $table->integer('total_places')->default(0);
            $table->integer('available_places')->default(0);
            // Удаляем добавленное поле
            $table->dropColumn('cabins_by_class');
        });
    }
};