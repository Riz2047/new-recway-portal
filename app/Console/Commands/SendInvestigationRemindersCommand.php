<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cron\InvestigationReminderService;
use Illuminate\Console\Command;

/**
 * Send reminder emails to company managers for candidates that have been
 * under investigation (with a report uploaded) for ≥ 5 working days
 * without a reminder being sent.
 *
 * Mirrors sendInvestigationReminderEmails() from the old PHP cron_jobs.php.
 *
 * Schedule: weekdays at 17:00 Europe/Stockholm (matches old system's trigger window).
 */
class SendInvestigationRemindersCommand extends Command
{
    protected $signature = 'reminders:investigation
        {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send investigation-reminder emails to company managers (≥5 working days without update)';

    public function handle(InvestigationReminderService $service): int
    {
        $this->info('Starting investigation reminder job — ' . now()->toDateTimeString());

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN mode — no emails will be sent.');
            // Still run for reporting; service logs to Laravel log only.
        }

        $stats = $service->run();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Reminders sent',    $stats['sent']],
                ['Candidates skipped (< 5 working days)', $stats['skipped']],
                ['Errors',            $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->error('Some candidates had errors — check laravel.log for details.');
            return self::FAILURE;
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
