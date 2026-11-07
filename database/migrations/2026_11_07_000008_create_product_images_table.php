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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->dateTime('created_at', 7);
        });

        DB::statement("CREATE UNIQUE INDEX uq_product_primary_image ON product_images(product_id) WHERE is_primary = 1");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
