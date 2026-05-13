<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('otp_verification')) {
            return;
        }

        Schema::create('otp_verification', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('otp', 10);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('date_time')->default(now());
            $table->timestamps();

            $table->index(['email', 'date_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verification');
    }
};
