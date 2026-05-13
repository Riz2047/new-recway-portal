<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('customer_invoices')) {
            return;
        }

        Schema::create('customer_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // Billing period that produced this invoice
            $table->enum('period', ['day', 'week', 'month'])->index();

            // Financial summary
            $table->decimal('invoice_amount', 10, 2)->default(0);

            // Workflow status
            $table->enum('status', ['to_be_invoiced', 'sent'])->default('to_be_invoiced')->index();

            // JSON array of candidate IDs included in this invoice
            $table->json('candidate_ids')->nullable();

            // Human-readable notes (auto-filled at generation time)
            $table->text('notes')->nullable();

            // Payment terms
            $table->date('due_date')->nullable();

            // When the cron created this record
            $table->datetime('created_date')->nullable();

            // When admin marked it as sent
            $table->datetime('sent_at')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['customer_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
