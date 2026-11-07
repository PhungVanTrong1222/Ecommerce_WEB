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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name', 255);
            $table->string('variant_name', 255);
            $table->string('sku', 100);
            $table->decimal('unit_price', 18, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 18, 2);
            $table->dateTime('created_at', 7);
        });

        DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_order_items_quantity CHECK (quantity > 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
