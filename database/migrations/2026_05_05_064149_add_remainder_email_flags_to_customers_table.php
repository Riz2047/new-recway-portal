<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('remainder_email')->default(false)->after('remainder_email_template');
            $table->boolean('bk_remainder_email')->default(false)->after('bk_remainder_email_template');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['remainder_email', 'bk_remainder_email']);
        });
    }
};
