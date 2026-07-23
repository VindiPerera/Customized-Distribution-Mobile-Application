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
        Schema::table('products', function (Blueprint $table) {
            $table->string('item_code')->nullable()->after('sku');
            $table->string('barcode')->nullable()->after('item_code');
            $table->foreignId('category_id')->nullable()->after('barcode')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained()->restrictOnDelete();
            $table->date('expiry_date')->nullable()->after('reorder_level');
            $table->decimal('discount', 12, 2)->default(0)->after('selling_price');
        });

        DB::statement('ALTER TABLE products CHANGE reorder_level low_stock_alert INT NOT NULL DEFAULT 10');

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'General',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $supplierId = DB::table('suppliers')->insertGetId([
            'name' => 'Unspecified',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->whereNull('category_id')->update(['category_id' => $categoryId]);
        DB::table('products')->whereNull('supplier_id')->update(['supplier_id' => $supplierId]);

        DB::statement('ALTER TABLE products MODIFY category_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE products MODIFY supplier_id BIGINT UNSIGNED NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['item_code', 'barcode', 'category_id', 'supplier_id', 'expiry_date', 'discount']);
        });

        DB::statement('ALTER TABLE products CHANGE low_stock_alert reorder_level INT NOT NULL DEFAULT 0');
    }
};
