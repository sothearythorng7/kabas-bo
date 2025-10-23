<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('category_translations', function (Blueprint $table) {
            $table->string('full_slug')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropColumn('full_slug');
        });
    }
};
