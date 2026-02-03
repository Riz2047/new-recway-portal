<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('standard_billing_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cus_id');
            $table->string('referenceperson', 500)->nullable();
            $table->string('reference', 500)->nullable();
            $table->string('comment', 500)->nullable();

            $table->index('cus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_billing_details');
    }
};


