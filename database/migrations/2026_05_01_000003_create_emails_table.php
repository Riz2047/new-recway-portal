<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('emails')) {
            return;
        }

        Schema::create('emails', function (Blueprint $table): void {
            $table->id();
            $table->string('user_type', 50)->nullable();
            $table->string('user_name')->nullable();
            $table->string('order_id', 50)->nullable()->index();
            $table->string('msg_type')->nullable();
            $table->longText('text')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('subject')->nullable();
            $table->integer('email_delay')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
