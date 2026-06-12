<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn(['place', 'country']);
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->boolean('place')->default(false)->after('description');
            $table->boolean('country')->default(false)->after('place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn(['place', 'country']);
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->string('place')->nullable()->after('description');
            $table->string('country')->nullable()->after('place');
        });
    }
};
