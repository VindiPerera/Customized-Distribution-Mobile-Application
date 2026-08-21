<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A "return" is a customer bringing back a product from an earlier
     * purchase while a new sale is being billed — its refunded amount is
     * deducted from the new sale's total instead of paid out separately.
     * Each row links the new sale to the original sale item being returned,
     * so both invoices stay traceable to each other.
     */
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2); // refunded price per unit (the original discounted price)
            $table->decimal('amount', 12, 2); // unit_price * quantity, deducted from the new sale's total
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');
    }
};
