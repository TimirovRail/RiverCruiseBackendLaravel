<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Проверяем наличие cruise_schedule_id и делаем его nullable, если он есть
        if (Schema::hasColumn('bookings', 'cruise_schedule_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('cruise_schedule_id')->nullable()->constrained('cruise_schedules')->onDelete('cascade')->change();
            });
        } else {
            // Если его нет, добавляем
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreignId('cruise_schedule_id')->nullable()->constrained('cruise_schedules')->onDelete('cascade')->after('user_id');
            });
        }

        // 2. Переносим данные из cruise и date в cruise_schedules
        $bookings = DB::table('bookings')->whereNull('cruise_schedule_id')->get();
        foreach ($bookings as $booking) {
            $cruise = DB::table('cruises')->where('name', $booking->cruise)->first();
            if ($cruise) {
                $schedule = DB::table('cruise_schedules')
                    ->where('cruise_id', $cruise->id)
                    ->where('departure_datetime', $booking->date . ' 00:00:00')
                    ->first();

                if (!$schedule) {
                    $scheduleId = DB::table('cruise_schedules')->insertGetId([
                        'cruise_id' => $cruise->id,
                        'departure_datetime' => $booking->date . ' 00:00:00',
                        'arrival_datetime' => $booking->date . ' 00:00:00', // Уточни логику для arrival_datetime
                        'total_places' => 50, // Укажи реальное значение
                        'available_places' => 50 - $booking->seats,
                        'status' => 'planned',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $scheduleId = $schedule->id;
                    DB::table('cruise_schedules')
                        ->where('id', $scheduleId)
                        ->decrement('available_places', $booking->seats);
                }

                DB::table('bookings')
                    ->where('id', $booking->id)
                    ->update(['cruise_schedule_id' => $scheduleId]);
            }
        }

        // 3. Удаляем старые столбцы
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'cruise')) {
                $table->dropColumn('cruise');
            }
            if (Schema::hasColumn('bookings', 'date')) {
                $table->dropColumn('date');
            }
        });

        // 4. Делаем cruise_schedule_id NOT NULL
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('cruise_schedule_id')->nullable(false)->constrained('cruise_schedules')->onDelete('cascade')->change();
        });
    }

    public function down(): void
    {
        // 1. Добавляем старые столбцы обратно
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('cruise')->after('email');
            $table->date('date')->after('cruise');
        });

        // 2. Переносим данные обратно
        DB::statement("
            UPDATE bookings
            SET cruise = (SELECT name FROM cruises WHERE cruises.id = (SELECT cruise_id FROM cruise_schedules WHERE cruise_schedules.id = bookings.cruise_schedule_id)),
                date = (SELECT DATE(departure_datetime) FROM cruise_schedules WHERE cruise_schedules.id = bookings.cruise_schedule_id)
        ");

        // 3. Удаляем cruise_schedule_id
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['cruise_schedule_id']);
            $table->dropColumn('cruise_schedule_id');
        });
    }
};