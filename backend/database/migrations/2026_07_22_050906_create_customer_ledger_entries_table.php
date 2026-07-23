<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sale', 'payment', 'adjustment']);
            $table->decimal('amount', 12, 2); // positive = increases debt, negative = reduces debt
            $table->decimal('balance_after', 12, 2); // running balance snapshot, for fast history reads
            $table->nullableMorphs('reference'); // links to Sale, Payment, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_ledger_entries');
    }
};
