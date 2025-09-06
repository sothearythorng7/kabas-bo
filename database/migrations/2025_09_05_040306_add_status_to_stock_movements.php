<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('status', ['draft', 'validated', 'in_transit', 'received', 'cancelled'])
                ->default('draft')
                ->after('note');
        });
    }

    public function down()
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
