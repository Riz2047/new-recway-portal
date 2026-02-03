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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->integer('service_cat_id')->unsigned();
            $table->text('title');
            $table->text('desc')->nullable();
            $table->integer('place')->default(0);
            $table->integer('country')->default(0);
            $table->integer('cost')->nullable();
            $table->integer('delivery_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
