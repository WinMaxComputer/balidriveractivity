<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->text('destination')->change();
        });
    }

    public function down(): void
    {
        $lengthFunction = DB::connection()->getDriverName() === 'sqlite' ? 'length' : 'char_length';
        if (DB::table('transfer_orders')->whereRaw($lengthFunction.'(destination) > ?', [255])->exists()) {
            throw new \RuntimeException('Cannot shorten transfer destinations while values exceed 255 characters.');
        }

        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('destination')->change();
        });
    }
};
