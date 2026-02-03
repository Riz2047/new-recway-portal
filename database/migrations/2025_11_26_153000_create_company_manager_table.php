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
        Schema::create('company_manager', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cus_id');
            $table->string('company', 500)->nullable();
            $table->string('statuses', 1100)->nullable();
            $table->boolean('can_view_report')->default(false);
            $table->mediumText('email_template')->nullable();

            $table->index('cus_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_manager');
    }
};


