<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('variable')->unique();
            $table->string('status');
            $table->string('status_sv')->nullable();
            $table->text('status_detail')->nullable();
            $table->string('status_icon')->nullable();
            $table->string('color')->nullable();
            $table->string('email_to')->comment('1=admin, 2=customer, 3=candidate')->nullable();
            $table->foreignId('status_type')->constrained('service_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
