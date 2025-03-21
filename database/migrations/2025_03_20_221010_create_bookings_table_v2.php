<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Связь с пользователем
            $table->foreignId('cruise_schedule_id')->constrained('cruise_schedules')->onDelete('cascade'); // Связь с рейсом
            $table->integer('seats')->unsigned(); // Количество мест
            $table->string('cabin_class'); // Класс каюты
            $table->decimal('total_price', 8, 2)->nullable(); // Общая стоимость (добавим позже через контроллер)
            $table->json('extras')->nullable(); // Дополнительные услуги
            $table->text('comment')->nullable(); // Комментарий
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};