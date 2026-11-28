<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_keyword', 500)->nullable();
            $table->string('meta_description', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_keyword', 'meta_description']);
        });
    }
};
