<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'applied_promotion_code')) {
                $table->string('applied_promotion_code', 64)->nullable()->after('discount');
            }
            if (!Schema::hasColumn('orders', 'applied_promotion_ids')) {
                $table->json('applied_promotion_ids')->nullable()->after('applied_promotion_code');
            }
            if (!Schema::hasColumn('orders', 'promo_discount_total')) {
                $table->decimal('promo_discount_total', 13, 5)->default(0)->after('applied_promotion_ids');
            }
            if (!Schema::hasColumn('orders', 'promo_gift_cost')) {
                $table->decimal('promo_gift_cost', 13, 5)->default(0)->after('promo_discount_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = ['applied_promotion_code', 'applied_promotion_ids', 'promo_discount_total', 'promo_gift_cost'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
