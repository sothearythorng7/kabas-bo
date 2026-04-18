<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['raw_material_id', 'supplier_id']);
        });

        // Migrate existing supplier_id data into pivot table
        $materials = DB::table('raw_materials')->whereNotNull('supplier_id')->get();
        foreach ($materials as $material) {
            DB::table('raw_material_supplier')->insert([
                'raw_material_id' => $material->id,
                'supplier_id' => $material->supplier_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_supplier');
    }
};
