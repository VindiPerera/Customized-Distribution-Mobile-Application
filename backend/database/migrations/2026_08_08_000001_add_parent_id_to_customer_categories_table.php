<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-referencing parent_id turns the flat customer_categories list
     * into a two-level tree: a category with parent_id = null is a
     * top-level category, one with parent_id set is a subcategory of it.
     * Only two levels are needed (no subcategories of subcategories), but
     * nothing here enforces that beyond the UI only ever offering
     * top-level categories as a parent choice.
     */
    public function up(): void
    {
        Schema::table('customer_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')
                ->constrained('customer_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
