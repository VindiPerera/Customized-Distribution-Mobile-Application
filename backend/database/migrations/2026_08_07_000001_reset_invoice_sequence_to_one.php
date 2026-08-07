<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reset the invoice sequence counter back to 1 so new sales start from 1,
     * ignoring old 100000+ series numbers.
     */
    public function up(): void
    {
        if (DB::table('invoice_sequences')->exists()) {
            DB::table('invoice_sequences')->update([
                'next_number' => 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('invoice_sequences')->insert([
                'next_number' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // No-op
    }
};
