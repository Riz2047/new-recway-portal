<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->boolean('password_reset_required')->default(false);
            $table->boolean('mfa_verified')->default(false);
            $table->string('mfa_type')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
