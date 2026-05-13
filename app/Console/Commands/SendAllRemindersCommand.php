<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cron\InvestigationReminderService;
use App\Services\Cron\StaffReminderService;
use Illuminate\Console\Command;

/**
 * Master cron command that runs all daily reminder jobs in one call.
 * Mirrors the old cron_jobs.php execution order.
 *
 * Schedule: weekdays at 17:00 Europe/Stockholm.
 *
 * Usage:
 *   php artisan reminders:all               # Run all reminders
 *   php artisan reminders:all --dry-run     # Preview without sending
 */
class SendAllRemindersCommand extends Command
{
    protected $signature = 'reminders:all
        {--dry-run : Preview without sending}';

    protected $description = 'Run all daily reminder jobs: investigation + staff reminders';

    public function handle(
        InvestigationReminderService $investigationService,
        StaffReminderService $staffService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $failed = false;

        if ($dryRun) {
            $this->warn('DRY-RUN — no emails will be sent.');
        }

        $this->info('=== Investigation Reminders ===');
        $investigationStats = $investigationService->run();
        $this->printStats($investigationStats);
        if ($investigationStats['errors'] > 0) {
            $failed = true;
        }

        $this->newLine();
        $this->info('=== Staff Reminders ===');
        $staffStats = $staffService->run();
        $this->printStats($staffStats);
        if ($staffStats['errors'] > 0) {
            $failed = true;
        }

        $this->newLine();
        $this->info('All reminder jobs completed — ' . now()->toDateTimeString());

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /** @param array{sent:int, skipped:int, errors:int} $stats */
    private function printStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Reminders sent',    $stats['sent']],
                ['Skipped',           $stats['skipped']],
                ['Errors',            $stats['errors']],
            ]
        );
    }
}
