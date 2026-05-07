<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the translatable JSON `name` + JSON `description` with a single
 * plain VARCHAR `name` used only internally (never shown to customers).
 *
 * Existing JSON values are squashed into the new column via a temporary
 * staging column so no data is lost.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('promotion_rules', 'name_tmp')) {
            Schema::table('promotion_rules', function (Blueprint $table) {
                $table->string('name_tmp', 255)->nullable()->after('name');
            });
        }

        foreach (DB::table('promotion_rules')->get(['id', 'name']) as $row) {
            $decoded = json_decode($row->name, true);
            $plain = is_array($decoded)
                ? ($decoded['en'] ?? $decoded['fr'] ?? reset($decoded) ?: ('rule-'.$row->id))
                : (string) ($row->name ?? ('rule-'.$row->id));

            DB::table('promotion_rules')->where('id', $row->id)->update(['name_tmp' => mb_substr($plain, 0, 255)]);
        }

        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->dropColumn('name');
            if (Schema::hasColumn('promotion_rules', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->renameColumn('name_tmp', 'name');
        });

        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->string('name', 255)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->renameColumn('name', 'name_tmp');
        });

        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->json('name')->after('id');
            $table->json('description')->nullable()->after('name');
        });

        foreach (DB::table('promotion_rules')->get(['id', 'name_tmp']) as $row) {
            DB::table('promotion_rules')->where('id', $row->id)->update([
                'name' => json_encode(['en' => $row->name_tmp, 'fr' => $row->name_tmp]),
                'description' => json_encode(['en' => '', 'fr' => '']),
            ]);
        }

        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->dropColumn('name_tmp');
        });
    }
};
