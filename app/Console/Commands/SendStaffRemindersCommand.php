<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cron\StaffReminderService;
use Illuminate\Console\Command;

/**
 * Send reminder emails to assigned staff for candidates that have been
 * in an "active / needs staff action" status for ≥ 5 working days.
 *
 * Mirrors sendStaffReminderEmails() from the old PHP cron_jobs.php.
 *
 * Schedule: weekdays at 17:00 Europe/Stockholm.
 */
class SendStaffRemindersCommand extends Command
{
    protected $signature = 'reminders:staff
        {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send staff-reminder emails for stalled candidates (≥5 working days in active status)';

    public function handle(StaffReminderService $service): int
    {
        $this->info('Starting staff reminder job — ' . now()->toDateTimeString());

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN mode — no emails will be sent.');
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
