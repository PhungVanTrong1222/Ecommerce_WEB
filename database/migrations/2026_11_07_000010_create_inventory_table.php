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
        Schema::create('inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->primary();
            $table->integer('quantity')->default(0);
            $table->dateTime('updated_at', 7);

            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE inventory ADD CONSTRAINT chk_inventory_quantity CHECK (quantity >= 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
