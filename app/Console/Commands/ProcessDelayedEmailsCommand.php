<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\CandidateStatusMail;
use App\Models\CandidateEmail;
use App\Services\Cron\SwedenWorkingHoursService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Processes emails saved with email_delay = 1 (queued for next working hour).
 *
 * When the old system sent emails outside 08:00-18:00 Mon–Fri, it stored them
 * with email_delay = 1. This command is the equivalent of the "send queued
 * emails" step — it runs at 08:00 weekdays and delivers anything marked delayed.
 *
 * Schedule: weekdays at 08:05 (just after work starts).
 */
class ProcessDelayedEmailsCommand extends Command
{
    protected $signature = 'emails:process-delayed
        {--limit=100 : Max number of delayed emails to process in one run}
        {--dry-run   : Preview without sending}';

    protected $description = 'Send emails that were queued as delayed (email_delay = 1)';

    public function handle(SwedenWorkingHoursService $workingHours): int
    {
        if (! Schema::hasTable('emails')) {
            $this->line('emails table does not exist — skipping.');
            return self::SUCCESS;
        }

        if (! $workingHours->isWorkingHours()) {
            $this->warn('Outside working hours — delayed emails should only be sent during 08:00-18:00 Mon-Fri (Sweden).');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN — no emails will be sent.');
        }

        $emails = CandidateEmail::where('email_delay', 1)
            ->limit($limit)
            ->get();

        if ($emails->isEmpty()) {
            $this->info('No delayed emails to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$emails->count()} delayed email(s)…");
        $sent = 0;
        $failed = 0;

        foreach ($emails as $email) {
            if (! $dryRun) {
                try {
                    Mail::to($email->email, $email->user_name ?? '')
                        ->send(new CandidateStatusMail($email->subject ?? '', $email->text ?? ''));

                    // Mark as processed so it won't be re-sent.
                    $email->update(['email_delay' => 0]);
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('ProcessDelayedEmailsCommand: failed to send', [
                        'email_id' => $email->id,
                        'to' => $email->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->line("  Would send to: {$email->email} | Subject: {$email->subject}");
                $sent++;
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $emails->count()],
                ['Sent',      $sent],
                ['Failed',    $failed],
            ]
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
