<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single-row counter for sequential, digits-only invoice numbers.
     * New sales get `next_number` and then it's incremented — existing
     * sales (which used the old 'INV-XXXXXXXX' random scheme) are left
     * untouched, so numbering starts fresh at 100000 from here on.
     */
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('next_number')->default(100000);
            $table->timestamps();
        });

        DB::table('invoice_sequences')->insert([
            'next_number' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
