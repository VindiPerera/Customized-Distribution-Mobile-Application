<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['purchase_in', 'sale_out', 'adjustment', 'return_in']);
            $table->integer('quantity'); // positive = stock in, negative = stock out
            $table->integer('quantity_after'); // running stock snapshot
            $table->nullableMorphs('reference'); // links to Sale, etc.
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
