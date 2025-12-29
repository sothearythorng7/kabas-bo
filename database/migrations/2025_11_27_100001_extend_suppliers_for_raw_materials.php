<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cette migration étend le système de fournisseurs pour supporter les matières premières
     * SANS détruire les données existantes.
     */
    public function up(): void
    {
        // 1. Ajouter les nouveaux champs à la table suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_raw_material_supplier')->default(false)->after('type');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->text('notes')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('notes');
        });

        // 2. Migrer les données de factory_suppliers vers suppliers
        $factorySuppliers = DB::table('factory_suppliers')->get();
        $supplierIdMapping = []; // old_id => new_id

        foreach ($factorySuppliers as $fs) {
            $newId = DB::table('suppliers')->insertGetId([
                'name' => $fs->name,
                'address' => $fs->address,
                'phone' => $fs->phone,
                'email' => $fs->email,
                'notes' => $fs->notes,
                'type' => 'buyer', // Les fournisseurs de matières premières sont en achat direct
                'is_raw_material_supplier' => true,
                'is_active' => $fs->is_active,
                'created_at' => $fs->created_at,
                'updated_at' => $fs->updated_at,
            ]);
            $supplierIdMapping[$fs->id] = $newId;
        }

        // 3. Ajouter supplier_id à raw_materials et migrer les références
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('factory_supplier_id')
                ->constrained('suppliers')->nullOnDelete();
        });

        // 4. Mettre à jour les raw_materials avec les nouveaux supplier_id
        foreach ($supplierIdMapping as $oldId => $newId) {
            DB::table('raw_materials')
                ->where('factory_supplier_id', $oldId)
                ->update(['supplier_id' => $newId]);
        }

        // 5. Ajouter order_type à supplier_orders pour différencier produits/matières premières
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->string('order_type')->default('product')->after('supplier_id');
            // 'product' = commande de produits (existant)
            // 'raw_material' = commande de matières premières (nouveau)
        });

        // 6. Créer la table pivot pour les commandes de matières premières
        Schema::create('supplier_order_raw_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained()->cascadeOnDelete();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('quantity_ordered', 12, 4)->default(0);
            $table->decimal('quantity_received', 12, 4)->nullable();
            $table->decimal('invoice_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['supplier_order_id', 'raw_material_id'], 'supplier_order_raw_material_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la table pivot
        Schema::dropIfExists('supplier_order_raw_material');

        // Retirer order_type de supplier_orders
        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->dropColumn('order_type');
        });

        // Retirer supplier_id de raw_materials
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        // Retirer les nouveaux champs de suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['is_raw_material_supplier', 'phone', 'email', 'notes', 'is_active']);
        });

        // Note: Les fournisseurs migrés resteront dans suppliers mais sans le flag
    }
};
