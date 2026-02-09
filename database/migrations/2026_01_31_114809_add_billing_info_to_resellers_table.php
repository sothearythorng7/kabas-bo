<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->string('address')->nullable()->after('type');
            $table->string('address2')->nullable()->after('address');
            $table->string('city')->nullable()->after('address2');
            $table->string('postal_code')->nullable()->after('city');
            $table->string('country')->nullable()->after('postal_code');
            $table->string('phone')->nullable()->after('country');
            $table->string('email')->nullable()->after('phone');
            $table->string('tax_id')->nullable()->after('email')->comment('Tax ID / VAT number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resellers', function (Blueprint $table) {
            $table->dropColumn(['address', 'address2', 'city', 'postal_code', 'country', 'phone', 'email', 'tax_id']);
        });
    }
};
