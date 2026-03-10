<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add column, FK and new unique index
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->foreignId('shipping_carrier_id')->nullable()->after('shipping_country_id')->constrained()->cascadeOnDelete();
            $table->unique(['shipping_country_id', 'shipping_carrier_id', 'weight_from', 'weight_to'], 'shipping_rates_country_carrier_weight_unique');
        });

        // Step 2: Drop old unique index (now safe because the FK on shipping_country_id is covered by the new composite index)
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropUnique(['shipping_country_id', 'weight_from', 'weight_to']);
        });
    }

    public function down(): void
    {
        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->unique(['shipping_country_id', 'weight_from', 'weight_to']);
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropUnique('shipping_rates_country_carrier_weight_unique');
            $table->dropForeign(['shipping_carrier_id']);
            $table->dropColumn('shipping_carrier_id');
        });
    }
};
