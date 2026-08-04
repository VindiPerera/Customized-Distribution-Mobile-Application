<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-line percentage discount applied at billing time. `unit_price`
     * keeps the product's original price (unchanged, for record-keeping);
     * `discounted_price` is what the customer actually pays per unit after
     * the discount, and `line_total` is discounted_price * quantity.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('unit_price');
            $table->decimal('discounted_price', 12, 2)->nullable()->after('discount_percent');
        });

        // Backfill existing rows so discounted_price is never null going forward.
        \DB::statement('UPDATE sale_items SET discounted_price = unit_price WHERE discounted_price IS NULL');
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discounted_price']);
        });
    }
};
