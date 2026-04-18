<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_positions', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->index();
            $table->string('device_name', 100)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->nullable()->comment('km/h');
            $table->integer('heading')->nullable()->comment('degrees 0-360');
            $table->integer('altitude')->nullable()->comment('meters');
            $table->integer('satellites')->nullable();
            $table->boolean('gps_fixed')->default(false);
            $table->decimal('battery_level', 5, 2)->nullable();
            $table->string('alarm_type', 50)->nullable();
            $table->timestamp('device_time')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'created_at']);
        });

        Schema::create('gps_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 50)->unique();
            $table->string('name', 100);
            $table->string('model', 100)->nullable();
            $table->string('sim_number', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_positions');
        Schema::dropIfExists('gps_devices');
    }
};
