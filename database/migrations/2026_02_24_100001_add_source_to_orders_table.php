<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('website')->after('store_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('source');
            $table->string('payment_link_url', 2000)->nullable()->after('admin_notes');
            $table->timestamp('payment_link_expires_at')->nullable()->after('payment_link_url');

            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'created_by_user_id', 'payment_link_url', 'payment_link_expires_at']);
        });
    }
};
