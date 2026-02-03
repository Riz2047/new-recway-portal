<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mirror the legacy `messages` table structure so existing logic works
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cus_id')->default(0);
            $table->unsignedBigInteger('interview_id')->default(0);

            $table->text('cus_msg')->nullable();
            $table->text('can_msg')->nullable();
            $table->text('can_msg_2')->nullable();
            $table->text('admin_msg')->nullable();
            $table->text('staff_msg')->nullable();
            $table->text('pending_msg')->nullable();
            $table->text('booked_msg')->nullable();
            $table->text('approved_msg')->nullable();
            $table->text('approved_msg_2')->nullable();
            $table->text('invest_msg')->nullable();
            $table->text('spo_msg')->nullable();
            $table->text('denied_msg')->nullable();
            $table->text('denied_msg_2')->nullable();
            $table->text('notshow_msg')->nullable();
            $table->text('canceled_msg')->nullable();
            $table->text('noans_msg')->nullable();
            $table->text('staff_cancel')->nullable();
            $table->text('can_cancel')->nullable();
            $table->text('cus_msg_background')->nullable();
            $table->text('can_msg_background')->nullable();
            $table->text('pending_background')->nullable();
            $table->text('consent_msg')->nullable();
            $table->text('approval_received_msg')->nullable();
            $table->text('research_started_msg')->nullable();
            $table->text('results_received_msg')->nullable();
            $table->text('REbook_interviews')->nullable();
            $table->text('deviation')->nullable();
            $table->text('approved_msg_bc')->nullable();
            $table->text('still_not_booked_msg')->nullable();
            $table->text('not_available_msg')->nullable();
            $table->text('person_startedf')->nullable();
            $table->text('candidate_msg')->nullable();
            $table->text('Pending')->nullable();
            $table->text('Booked')->nullable();
            $table->text('Candidatedidntshowup')->nullable();
            $table->text('Approved_followup')->nullable();
            $table->text('Candidate_Cancel')->nullable();
            $table->text('follow_up_under_investigation')->nullable();
            $table->text('bk_can_cancel')->nullable();
            $table->text('Candidate_interup_followup')->nullable();
            $table->text('denid_followup')->nullable();
            $table->text('still_not_booked_followup')->nullable();
            $table->text('Contact_established')->nullable();
            $table->text('no_avaible_followup')->nullable();
            $table->text('Customer_flow')->nullable();
            $table->text('withoutdeviation')->nullable();
            $table->text('Cus_msg_exit')->nullable();

            // Indexes used by legacy logic
            $table->index(['cus_id', 'interview_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};


