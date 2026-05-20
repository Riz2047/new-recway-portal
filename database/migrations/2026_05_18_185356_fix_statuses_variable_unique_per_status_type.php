<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix statuses.variable uniqueness.
 *
 * Old (wrong): variable is globally unique — prevents two service categories
 *              from having a status with the same variable name (e.g. "approved").
 *
 * New (correct): variable is unique per status_type (service category) —
 *                matches the old system's data model and real business rules.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table): void {
            $indexes = array_column(Schema::getIndexes('statuses'), 'name');

            // Drop the old global unique index if it exists.
            if (in_array('statuses_variable_unique', $indexes, true)) {
                $table->dropUnique('statuses_variable_unique');
            }

            // Add composite unique: variable must be unique within a service category.
            if (! in_array('statuses_variable_status_type_unique', $indexes, true)) {
                $table->unique(['variable', 'status_type'], 'statuses_variable_status_type_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table): void {
            $indexes = array_column(Schema::getIndexes('statuses'), 'name');

            if (in_array('statuses_variable_status_type_unique', $indexes, true)) {
                $table->dropUnique('statuses_variable_status_type_unique');
            }

            if (! in_array('statuses_variable_unique', $indexes, true)) {
                $table->unique('variable');
            }
        });
    }
};
