<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY method ENUM('cash', 'card', 'bank_transfer', 'cheque', 'other') DEFAULT 'cash'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY method ENUM('cash', 'bank_transfer', 'cheque', 'other') DEFAULT 'cash'");
    }
};
