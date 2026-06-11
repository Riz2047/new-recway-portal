<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restore msg_col to status_services.
 *
 * msg_col is the key used in messages.templates JSON.
 * e.g. msg_col = 'approved_msg' → templates['approved_msg'] = '<html>…</html>'
 * This keeps the template naming consistent with how the whole project refers to messages.
 */
return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('status_services', 'msg_col')) {
            Schema::table('status_services', function (Blueprint $table): void {
                $table->string('msg_col')->nullable()->after('service_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('status_services', 'msg_col')) {
            Schema::table('status_services', function (Blueprint $table): void {
                $table->dropColumn('msg_col');
            });
        }
    }
};
