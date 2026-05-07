<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Migrer les valeurs `name` varchar existantes vers un format JSON {en, fr}
        //    (en gardant la valeur actuelle pour les deux locales — à retravailler manuellement par le staff).
        //    Les chaînes vides sont converties en NULL (JSON refuse les strings vides).
        DB::table('variation_groups')->where('name', '')->update(['name' => null]);

        $rows = DB::table('variation_groups')->whereNotNull('name')->get(['id', 'name']);
        foreach ($rows as $row) {
            $decoded = json_decode($row->name, true);
            if (is_array($decoded)) {
                continue; // déjà JSON-shaped
            }
            DB::table('variation_groups')
                ->where('id', $row->id)
                ->update(['name' => json_encode(['en' => $row->name, 'fr' => $row->name], JSON_UNESCAPED_UNICODE)]);
        }

        // 2) Changer le type de `name` en JSON (les valeurs sont maintenant toutes valides JSON ou NULL).
        DB::statement('ALTER TABLE variation_groups MODIFY COLUMN name JSON NULL');

        // 3) Ajouter `description` (JSON traduit) et `display_product_id` (FK -> products, nullOnDelete).
        Schema::table('variation_groups', function (Blueprint $table) {
            $table->json('description')->nullable()->after('name');
            $table->foreignId('display_product_id')
                ->nullable()
                ->after('description')
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('variation_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('display_product_id');
            $table->dropColumn('description');
        });

        // Reconvertir `name` en VARCHAR en extrayant la locale fr (fallback en).
        $rows = DB::table('variation_groups')->get(['id', 'name']);
        foreach ($rows as $row) {
            if ($row->name === null) {
                continue;
            }
            $decoded = json_decode($row->name, true);
            if (!is_array($decoded)) {
                continue;
            }
            $str = $decoded['fr'] ?? $decoded['en'] ?? (reset($decoded) ?: null);
            DB::table('variation_groups')
                ->where('id', $row->id)
                ->update(['name' => is_string($str) ? $str : null]);
        }

        DB::statement('ALTER TABLE variation_groups MODIFY COLUMN name VARCHAR(255) NULL');
    }
};
