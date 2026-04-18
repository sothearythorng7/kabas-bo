<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add ACC status + altitude storage to gps_positions
        Schema::table('gps_positions', function (Blueprint $table) {
            $table->boolean('acc_on')->nullable()->after('gps_fixed')->comment('Ignition/ACC status from JT808');
        });

        // 2. Create geocode cache table
        Schema::create('gps_geocode_cache', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('location_name', 500);
            $table->timestamps();

            $table->unique(['latitude', 'longitude']);
        });

        // 3. Migrate old positions from device_id 2566655 to 9590074321
        DB::table('gps_positions')
            ->where('device_id', '2566655')
            ->update(['device_id' => '9590074321']);
    }

    public function down(): void
    {
        Schema::table('gps_positions', function (Blueprint $table) {
            $table->dropColumn('acc_on');
        });

        Schema::dropIfExists('gps_geocode_cache');

        DB::table('gps_positions')
            ->where('device_id', '9590074321')
            ->where('created_at', '<', '2026-04-05 14:00:00')
            ->update(['device_id' => '2566655']);
    }
};
