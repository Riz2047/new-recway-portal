<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoice_staff_email')) {
            return;
        }

        Schema::create('invoice_staff_email', function (Blueprint $table): void {
            $table->id();

            // References candidates.id (the PK, not the order_id string)
            $table->foreignId('order_id')->constrained('candidates')->cascadeOnDelete();

            // The manager/staff who received the notification
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();

            // 1 = email was sent (prevents re-sending)
            $table->unsignedTinyInteger('invoice_email')->default(1);

            $table->timestamps();

            $table->unique(['order_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_staff_email');
    }
};
