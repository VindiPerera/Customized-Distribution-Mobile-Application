<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-line discounts can now be entered as a percentage OR a flat rupee
     * amount off the unit price. `discount_type` says which of
     * `discount_percent` / `discount_amount` is the one actually applied;
     * the other stays 0. `discounted_price` / `line_total` are always the
     * resolved result regardless of which mode was used, so nothing else
     * that reads them needs to know about discount_type at all.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'amount'])->default('percent')->after('unit_price');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_amount']);
        });
    }
};
