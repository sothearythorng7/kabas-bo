<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ajout du type de fournisseur
        Schema::table('suppliers', function (Blueprint $table) {
            $table->enum('type', ['buyer', 'consignment'])->default('buyer')->after('address');
        });

        Schema::create('sale_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['draft', 'validated', 'invoiced'])->default('draft');
            $table->decimal('total_amount_theoretical', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sale_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_sold');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_report_products');
        Schema::dropIfExists('sale_reports');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
