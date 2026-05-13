<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('history')) {
            return;
        }

        Schema::create('history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('candidates')->cascadeOnDelete();
            $table->text('desc')->nullable();
            $table->dateTime('date_time')->default(now());
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};
