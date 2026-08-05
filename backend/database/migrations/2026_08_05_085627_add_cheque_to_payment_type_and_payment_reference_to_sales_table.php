<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 'cheque' to payment_type enum
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'credit', 'cheque') NOT NULL DEFAULT 'cash'");

        // 2. Add payment_reference column
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });

        // Revert payment_type enum (safely set cheque payments to cash before reverting)
        DB::statement("UPDATE sales SET payment_type = 'cash' WHERE payment_type = 'cheque'");
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'credit') NOT NULL DEFAULT 'cash'");
    }
};
