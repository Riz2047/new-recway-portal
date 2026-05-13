<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_manager_customer_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_customer_id');
            $table->unsignedBigInteger('target_customer_id');
            $table->string('action');           // 'email' | 'phone' | 'is_active'
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_manager_customer_audit_logs');
    }
};
