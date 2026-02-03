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
        Schema::create('customer_services', function (Blueprint $table) {
            $table->unsignedBigInteger('cus_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('service_cost')->nullable();
            
            // Add indexes for better performance
            $table->index('cus_id');
            $table->index('service_id');
            
            // Add foreign key constraints (optional, uncomment if needed)
            // $table->foreign('cus_id')->references('id')->on('customers')->onDelete('cascade');
            // $table->foreign('service_id')->references('id')->on('interviews')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_services');
    }
};
