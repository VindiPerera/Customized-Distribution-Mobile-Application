<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separate from the shop's own `phone` — this is the distribution
     * company's contact number, printed on every receipt alongside the
     * hardcoded "Powered by JAAN Network (PVT) Ltd" line.
     */
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->string('company_phone')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn('company_phone');
        });
    }
};
