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
        Schema::create('user_allowed_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('per_id'); // Permission ID from user_permissions table
            $table->unsignedBigInteger('user_id'); // Customer or department user ID
            $table->integer('user_type')->default(0)->comment('1 for dep_user; 2 for customer');
            
            // Add indexes for better performance
            $table->index('per_id');
            $table->index('user_id');
            $table->index('user_type');
            
            // Add foreign key constraints (optional, uncomment if needed)
            // $table->foreign('per_id')->references('id')->on('user_permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_allowed_permissions');
    }
};
