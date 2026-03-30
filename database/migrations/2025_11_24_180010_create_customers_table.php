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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('org_no')->nullable();
            $table->string('cost_place')->nullable();
            $table->text('statuses')->nullable(); // Comma-separated status IDs
            $table->mediumText('reg_email')->nullable(); // Registration email template
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('customers')->onDelete('set null');
            $table->unsignedBigInteger('dep_id')->nullable(); // Department ID
            $table->boolean('interview_template')->default(false);
            $table->boolean('send_security_report')->default(false);
            $table->boolean('sent_email')->default(false);
            $table->boolean('timra_report')->default(false);
            $table->boolean('interview_upload_allowed')->default(false);
            $table->boolean('ellevio_report')->default(false);
            $table->text('combine_bk_and_security')->nullable(); // Comma-separated service IDs or '0'
            $table->text('combine_status')->nullable();
            $table->text('combine_interview_service')->nullable();
            $table->enum('invoice_period', ['day', 'week', 'month'])->default('month');
            $table->date('last_invoice_sent')->nullable();
            $table->string('client_wish')->nullable();
            $table->text('groups')->nullable(); // Comma-separated group IDs
            $table->text('remainder_email_template')->nullable();
            $table->boolean('bk_interviewed')->default(false);
            $table->text('bk_remainder_email_template')->nullable();
            $table->integer('report_delete_duration')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->boolean('send_email_question')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('dep_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
