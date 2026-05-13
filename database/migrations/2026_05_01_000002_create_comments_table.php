<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('comments')) {
            return;
        }

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('candidates')->cascadeOnDelete();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('author_type', 50)->default('admin'); // admin | staff
            $table->text('comment');
            $table->text('read_by_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
