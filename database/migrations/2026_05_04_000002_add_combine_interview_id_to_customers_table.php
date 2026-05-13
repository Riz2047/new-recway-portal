<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds combine_interview_id (FK → service_types) to the customers table
 * as the authoritative customer-level default for the BK → Security transfer.
 *
 * The existing combine_interview_service column stores the same concept as a
 * plain string; this column is the typed FK version and is used by
 * CombineInterviewService as the customer-level fallback.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'combine_interview_id')) {
                $table->foreignId('combine_interview_id')
                    ->nullable()
                    ->after('combine_interview_service')
                    ->constrained('service_types')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'combine_interview_id')) {
                $table->dropForeign(['combine_interview_id']);
                $table->dropColumn('combine_interview_id');
            }
        });
    }
};
