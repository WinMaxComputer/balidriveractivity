<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_guides', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100);
            $table->string('title');
            $table->string('slug');
            $table->string('lang', 2);
            $table->longText('content');
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->timestamps();
            $table->unique(['code', 'lang']);
            $table->unique(['slug', 'lang']);
            $table->index(['lang', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_guides');
    }
};
