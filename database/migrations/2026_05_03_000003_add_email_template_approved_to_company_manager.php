<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('company_manager')) {
            return;
        }

        Schema::table('company_manager', function (Blueprint $table): void {
            if (! Schema::hasColumn('company_manager', 'email_template_approved')) {
                $table->mediumText('email_template_approved')->nullable()->after('email_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_manager', function (Blueprint $table): void {
            if (Schema::hasColumn('company_manager', 'email_template_approved')) {
                $table->dropColumn('email_template_approved');
            }
        });
    }
};
