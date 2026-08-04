<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POS billing now only supports Cash and Credit — card/bank_transfer/split
     * are removed. Any existing sales recorded under those methods are kept
     * as history but the enum no longer accepts new rows of that type.
     *
     * The bill-level discount is also removed: discounting now happens per
     * line item (see the sale_items discount_percent/discounted_price
     * migration), so the flat `sales.discount` column is no longer written.
     * The column itself is dropped since nothing reads it once the app stops
     * writing it.
     */
    public function up(): void
    {
        DB::statement("UPDATE sales SET payment_type = 'cash' WHERE payment_type NOT IN ('cash', 'credit')");
        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'credit') NOT NULL DEFAULT 'cash'");

        Schema::table('sales', function ($table) {
            $table->dropColumn('discount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function ($table) {
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
        });

        DB::statement("ALTER TABLE sales MODIFY payment_type ENUM('cash', 'card', 'bank_transfer', 'credit', 'split') NOT NULL DEFAULT 'cash'");
    }
};
