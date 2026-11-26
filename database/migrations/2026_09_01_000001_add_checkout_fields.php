<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_packages', function (Blueprint $table) {
            $table->decimal('price_usd', 10, 2)->nullable();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->string('checkout_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['checkout_token']);
            $table->dropColumn('checkout_token');
        });

        Schema::table('tour_packages', function (Blueprint $table) {
            $table->dropColumn('price_usd');
        });
    }
};
