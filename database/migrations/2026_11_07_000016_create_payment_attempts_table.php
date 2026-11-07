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
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->integer('attempt_number');
            $table->string('transaction_reference', 100)->unique();
            $table->string('gateway_transaction_id', 100)->nullable();
            $table->string('status', 20)->default('Pending');
            $table->decimal('amount', 18, 2);
            $table->text('gateway_response')->nullable();
            $table->dateTime('created_at', 7);
            $table->dateTime('updated_at', 7);
        });

        DB::statement("ALTER TABLE payment_attempts ADD CONSTRAINT chk_payment_attempts_attempt_number CHECK (attempt_number > 0)");
        DB::statement("ALTER TABLE payment_attempts ADD CONSTRAINT chk_payment_attempts_status CHECK (status IN ('Pending','Processing','Paid','Failed','Cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
