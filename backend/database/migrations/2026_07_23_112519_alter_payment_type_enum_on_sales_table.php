<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'card', 'bank_transfer', 'credit', 'split') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE sales SET payment_type = 'cash' WHERE payment_type NOT IN ('cash', 'credit')");
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'credit') NOT NULL DEFAULT 'cash'");
    }
};
