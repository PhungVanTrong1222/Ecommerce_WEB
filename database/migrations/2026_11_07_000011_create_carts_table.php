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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('guest_token_hash', 64)->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('created_at', 7);
            $table->dateTime('updated_at', 7);
        });

        DB::statement("ALTER TABLE carts ADD CONSTRAINT chk_carts_status CHECK (status IN ('Active', 'Merged', 'Abandoned'))");
        DB::statement("ALTER TABLE carts ADD CONSTRAINT chk_carts_owner CHECK (user_id IS NOT NULL OR guest_token_hash IS NOT NULL)");
        DB::statement("CREATE UNIQUE INDEX uq_cart_active_user ON carts(user_id) WHERE status = 'Active' AND user_id IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX uq_cart_active_guest ON carts(guest_token_hash) WHERE status = 'Active' AND guest_token_hash IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
