<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Total value of returns applied to this sale (sum of its sale_returns
     * rows). Stored redundantly alongside subtotal/total_amount so the bill
     * total is readable without joining sale_returns every time.
     * total_amount already reflects subtotal - return_amount.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('return_amount', 12, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('return_amount');
        });
    }
};
