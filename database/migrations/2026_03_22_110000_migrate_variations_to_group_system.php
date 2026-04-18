<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create variation_groups table
        Schema::create('variation_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // 2. Add variation_group_id to products
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('variation_group_id')->nullable()->after('id')
                ->constrained('variation_groups')->nullOnDelete();
        });

        // 3. Create product_variation_attributes table
        Schema::create('product_variation_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_group_id')->constrained('variation_groups')->cascadeOnDelete();
            $table->foreignId('variation_type_id')->constrained('variation_types')->cascadeOnDelete();
            $table->foreignId('variation_value_id')->constrained('variation_values')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'variation_group_id', 'variation_type_id'], 'pva_product_group_type_unique');
        });

        // 4. Migrate existing data
        $this->migrateData();

        // 5. Drop old tables
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_variation_links');
    }

    public function down(): void
    {
        // Recreate old tables
        Schema::create('product_variation_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'related_product_id']);
        });

        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('linked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variation_type_id')->nullable()->constrained('variation_types')->nullOnDelete();
            $table->foreignId('variation_value_id')->nullable()->constrained('variation_values')->nullOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'variation_type_id', 'variation_value_id'], 'product_variations_unique');
        });

        Schema::dropIfExists('product_variation_attributes');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variation_group_id');
        });
        Schema::dropIfExists('variation_groups');
    }

    private function migrateData(): void
    {
        // Build adjacency graph from cross-referencing rows
        $edges = DB::table('product_variations')
            ->whereColumn('product_id', '!=', 'linked_product_id')
            ->select('product_id', 'linked_product_id')
            ->distinct()
            ->get();

        // Also collect self-referencing products (they have their own attributes)
        $selfRows = DB::table('product_variations')
            ->whereColumn('product_id', 'linked_product_id')
            ->get();

        // Union-Find to discover connected components
        $parent = [];

        foreach ($edges as $e) {
            $this->unite($parent, $e->product_id, $e->linked_product_id);
        }
        foreach ($selfRows as $s) {
            if (!isset($parent[$s->product_id])) {
                $parent[$s->product_id] = $s->product_id;
            }
        }

        // Group by root
        $groups = [];
        foreach ($parent as $id => $_) {
            $root = $this->find($parent, $id);
            $groups[$root][] = $id;
        }

        // For each group, create a variation_group and migrate attributes
        foreach ($groups as $root => $productIds) {
            $productIds = array_unique($productIds);

            // Get first product name for group naming
            $firstProduct = DB::table('products')->where('id', min($productIds))->first();
            $name = null;
            if ($firstProduct && $firstProduct->name) {
                $nameArr = json_decode($firstProduct->name, true);
                $name = $nameArr['fr'] ?? $nameArr['en'] ?? reset($nameArr) ?? null;
            }

            $groupId = DB::table('variation_groups')->insertGetId([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Set variation_group_id on all products in the group
            DB::table('products')
                ->whereIn('id', $productIds)
                ->update(['variation_group_id' => $groupId]);

            // Collect each product's own attributes
            // Priority: self-referencing rows (product_id == linked_product_id)
            // Fallback: from cross-referencing rows where product appears as product_id
            foreach ($productIds as $productId) {
                $attrs = DB::table('product_variations')
                    ->where('product_id', $productId)
                    ->where('linked_product_id', $productId)
                    ->whereNotNull('variation_type_id')
                    ->whereNotNull('variation_value_id')
                    ->select('variation_type_id', 'variation_value_id')
                    ->distinct()
                    ->get();

                if ($attrs->isEmpty()) {
                    // Fallback: use cross-referencing rows
                    $attrs = DB::table('product_variations')
                        ->where('product_id', $productId)
                        ->whereColumn('product_id', '!=', 'linked_product_id')
                        ->whereNotNull('variation_type_id')
                        ->whereNotNull('variation_value_id')
                        ->select('variation_type_id', 'variation_value_id')
                        ->distinct()
                        ->get();
                }

                foreach ($attrs as $attr) {
                    // Avoid duplicates (same product + group + type)
                    $exists = DB::table('product_variation_attributes')
                        ->where('product_id', $productId)
                        ->where('variation_group_id', $groupId)
                        ->where('variation_type_id', $attr->variation_type_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('product_variation_attributes')->insert([
                            'product_id' => $productId,
                            'variation_group_id' => $groupId,
                            'variation_type_id' => $attr->variation_type_id,
                            'variation_value_id' => $attr->variation_value_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function find(array &$parent, int $x): int
    {
        if (!isset($parent[$x])) $parent[$x] = $x;
        if ($parent[$x] !== $x) $parent[$x] = $this->find($parent, $parent[$x]);
        return $parent[$x];
    }

    private function unite(array &$parent, int $a, int $b): void
    {
        $ra = $this->find($parent, $a);
        $rb = $this->find($parent, $b);
        if ($ra !== $rb) $parent[$ra] = $rb;
    }
};
