<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_guides', function (Blueprint $table) {
            $table->longText('photos')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('travel_guides', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
