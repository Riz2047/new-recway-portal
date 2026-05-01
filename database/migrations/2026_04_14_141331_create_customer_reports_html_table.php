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
        if (Schema::hasTable('customer_reports_html')) {
            return;
        }

        Schema::create('customer_reports_html', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cus_id')->default(0);
            $table->unsignedBigInteger('interview_id');
            $table->string('lang', 5)->default('en');
            $table->longText('report_data')->nullable();
            $table->longText('meta_info')->nullable();
            $table->timestamps();

            $table->unique(['cus_id', 'interview_id', 'lang'], 'customer_reports_unique_template');
            $table->index(['interview_id', 'lang'], 'customer_reports_service_lang_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_reports_html');
    }
};
