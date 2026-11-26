<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('request_token', 64)->unique();
            $table->string('name');
            $table->string('email', 100);
            $table->string('mobile', 50);
            $table->string('booking_type', 20);
            $table->string('destination');
            $table->date('pickup_date');
            $table->string('flight_number', 50)->nullable();
            $table->time('arrival_time')->nullable();
            $table->unsignedInteger('passengers');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
