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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('variant_name', 255);
            $table->decimal('price', 18, 2);
            $table->decimal('sale_price', 18, 2)->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('created_at', 7);
            $table->dateTime('updated_at', 7);
        });

        DB::statement("ALTER TABLE product_variants ADD CONSTRAINT chk_product_variants_price CHECK (price > 0)");
        DB::statement("ALTER TABLE product_variants ADD CONSTRAINT chk_product_variants_sale_price CHECK (sale_price IS NULL OR sale_price > 0)");
        DB::statement("ALTER TABLE product_variants ADD CONSTRAINT chk_product_variants_status CHECK (status IN ('Active', 'Inactive'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
