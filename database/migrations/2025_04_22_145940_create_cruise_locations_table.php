<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCruiseLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('cruise_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cruise_id')->constrained('cruises')->onDelete('cascade');
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cruise_locations');
    }
}