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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('status', 30)->default('Pending');
            $table->decimal('subtotal', 18, 2);
            $table->decimal('shipping_fee', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2);
            $table->text('shipping_address_snapshot');
            $table->string('payment_method', 20);
            $table->dateTime('payment_expires_at', 7)->nullable();
            $table->string('note', 500)->nullable();
            $table->dateTime('created_at', 7);
            $table->dateTime('updated_at', 7);
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_status CHECK (status IN ('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_subtotal CHECK (subtotal >= 0)");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_shipping_fee CHECK (shipping_fee >= 0)");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_total_amount CHECK (total_amount >= 0)");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_payment_method CHECK (payment_method IN ('COD','VNPay'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT chk_orders_shipping_address_snapshot_isjson CHECK (ISJSON(shipping_address_snapshot) = 1)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
