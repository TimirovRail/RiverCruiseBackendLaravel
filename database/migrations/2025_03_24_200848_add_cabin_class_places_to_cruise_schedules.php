<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            $table->dropColumn(['total_places', 'available_places']);
            $table->json('cabins_by_class')->nullable()->after('cabins');
        });

        Schema::table('cruise_schedules', function (Blueprint $table) {
            $table->integer('economy_places')->default(0)->after('available_places');
            $table->integer('standard_places')->default(0)->after('economy_places');
            $table->integer('luxury_places')->default(0)->after('standard_places');
            $table->integer('available_economy_places')->default(0)->after('luxury_places');
            $table->integer('available_standard_places')->default(0)->after('available_economy_places');
            $table->integer('available_luxury_places')->default(0)->after('available_standard_places');
        });
    }

    public function down(): void
    {
        Schema::table('cruises', function (Blueprint $table) {
            $table->integer('total_places')->default(0);
            $table->integer('available_places')->default(0);
            $table->dropColumn('cabins_by_class');
        });

        Schema::table('cruise_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'economy_places',
                'standard_places',
                'luxury_places',
                'available_economy_places',
                'available_standard_places',
                'available_luxury_places',
            ]);
        });
    }
};