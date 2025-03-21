<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            // Добавляем новые столбцы, если их ещё нет
            if (!Schema::hasColumn('cruises', 'departure_datetime')) {
                $table->dateTime('departure_datetime')->nullable()->after('cabins');
            }
            if (!Schema::hasColumn('cruises', 'arrival_datetime')) {
                $table->dateTime('arrival_datetime')->nullable()->after('departure_datetime');
            }
            if (!Schema::hasColumn('cruises', 'status')) {
                $table->enum('status', ['planned', 'active', 'completed', 'canceled'])->default('planned')->after('image_path');
            }
            // Поле features уже существует, его не трогаем
        });

        // Переносим данные из start_date и end_date
        if (Schema::hasColumn('cruises', 'start_date') && Schema::hasColumn('cruises', 'end_date')) {
            DB::statement("
                UPDATE cruises
                SET departure_datetime = start_date::timestamp,
                    arrival_datetime = end_date::timestamp
                WHERE start_date IS NOT NULL AND end_date IS NOT NULL
            ");

            // Удаляем старые столбцы
            Schema::table('cruises', function (Blueprint $table) {
                $table->dropColumn(['start_date', 'end_date']);
            });
        }

        // Делаем новые столбцы NOT NULL
        Schema::table('cruises', function (Blueprint $table) {
            $table->dateTime('departure_datetime')->nullable(false)->change();
            $table->dateTime('arrival_datetime')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            $table->date('start_date')->after('cabins');
            $table->date('end_date')->after('start_date');
        });

        DB::statement("
            UPDATE cruises
            SET start_date = DATE(departure_datetime),
                end_date = DATE(arrival_datetime)
        ");

        Schema::table('cruises', function (Blueprint $table) {
            $table->dropColumn(['departure_datetime', 'arrival_datetime', 'status']);
        });
    }
};