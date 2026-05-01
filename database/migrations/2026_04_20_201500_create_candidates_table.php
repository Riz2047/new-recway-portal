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
        if (Schema::hasTable('candidates')) {
            return;
        }

        Schema::create('candidates', function (Blueprint $table): void {
            $table->id();

            // Core order identifiers
            $table->string('order_id', 50)->nullable()->index();
            $table->string('vasc_id')->nullable();
            $table->string('security')->nullable();

            // Candidate profile
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('place')->nullable()->constrained('places')->nullOnDelete();
            $table->string('country')->nullable();
            $table->text('cv')->nullable();

            // Billing and internal notes
            $table->string('referensperson')->nullable();
            $table->string('reference')->nullable();
            $table->text('comment')->nullable();
            $table->text('note')->nullable();

            // Main relations
            $table->foreignId('cus_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('interview_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->foreignId('status')->nullable()->constrained('statuses')->nullOnDelete();

            // Legacy workflow fields
            $table->date('date')->nullable();
            $table->unsignedTinyInteger('reported')->default(0);
            $table->unsignedTinyInteger('invoice_sent')->default(0);
            $table->date('invoice_date')->nullable();
            $table->string('economy')->nullable();
            $table->string('criminal_record')->nullable();
            $table->string('social')->nullable();
            $table->unsignedTinyInteger('background_checked')->default(0);
            $table->dateTime('created')->nullable();
            $table->dateTime('booked')->nullable()->index();
            $table->unsignedTinyInteger('expired')->default(0)->index();
            $table->date('background_check_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->longText('report')->nullable();
            $table->string('report_status')->nullable();
            $table->longText('interview_report')->nullable();
            $table->unsignedBigInteger('dep_user')->nullable();
            $table->unsignedBigInteger('dep_id')->nullable();
            $table->longText('cus_qs_ans')->nullable();
            $table->longText('meta_data')->nullable();
            $table->unsignedTinyInteger('reported_to_sm')->default(0);
            $table->dateTime('reported_to_sm_on')->nullable();
            $table->text('interview_template')->nullable();
            $table->longText('meta_info')->nullable();
            $table->decimal('service_cost', 10, 2)->nullable();
            $table->decimal('travel_cost', 10, 2)->nullable();
            $table->text('basic_investigation_result')->nullable();
            $table->string('BIR_interview_place')->nullable();
            $table->foreignId('combine_interview_id')->nullable()->constrained('service_types')->nullOnDelete();
            $table->boolean('is_verified')->default(false);
            $table->text('verified_document_path')->nullable();
            $table->boolean('hasPersonalId')->default(false);
            $table->unsignedTinyInteger('invoice_genrated')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
