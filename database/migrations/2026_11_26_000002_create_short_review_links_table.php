<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_review_links', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->text('target_url');
            $table->dateTime('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_review_links');
    }
};
