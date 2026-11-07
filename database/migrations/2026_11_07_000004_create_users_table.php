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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('role_id');
            $table->string('fullname', 150);
            $table->string('email', 320)->unique();
            $table->string('password_hash', 255);
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->string('status', 20)->default('Active');
            $table->dateTime('created_at', 7);
            $table->dateTime('updated_at', 7);

            $table->foreign('role_id')->references('id')->on('roles');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_status CHECK (status IN ('Active', 'Suspended'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
