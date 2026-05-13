<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SetupStorage::class,
        Commands\CreatePlaceholderImages::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Demo database refresh (demo mode only).
        $schedule->command('demo:refresh-database')->everyFifteenMinutes();

        // ── Invoice generation ─────────────────────────────────────────────
        // Auto-generate customer invoices every weekday at 09:00 Stockholm.
        // The command itself checks calendar boundaries (Mon for weekly, 1st for monthly).
        $schedule->command('invoices:generate')
            ->weekdays()
            ->at('09:00')
            ->timezone('Europe/Stockholm')
            ->appendOutputTo(storage_path('logs/invoices-generate.log'));

        // ── Delayed-email delivery ─────────────────────────────────────────
        // Send any emails saved with email_delay = 1 (queued outside working hours).
        $schedule->command('emails:process-delayed')
            ->weekdays()
            ->at('08:05')
            ->timezone('Europe/Stockholm')
            ->appendOutputTo(storage_path('logs/emails-delayed.log'));

        // ── Investigation reminders ────────────────────────────────────────
        // Notify company managers when a candidate stays "under investigation"
        // with an uploaded report for ≥ 5 working days.
        $schedule->command('reminders:investigation')
            ->weekdays()
            ->at(config('cron.reminders_time', '17:00'))
            ->timezone('Europe/Stockholm')
            ->appendOutputTo(storage_path('logs/reminders-investigation.log'));

        // ── Staff reminders ───────────────────────────────────────────────
        // Remind assigned staff when a candidate stalls in an active status
        // for ≥ 5 working days without action.
        $schedule->command('reminders:staff')
            ->weekdays()
            ->at(config('cron.reminders_time', '17:00'))
            ->timezone('Europe/Stockholm')
            ->appendOutputTo(storage_path('logs/reminders-staff.log'));

        // ── OTP cleanup ───────────────────────────────────────────────────
        // Remove expired OTP verification records (> 24 h old).
        $schedule->command('cleanup:otp')
            ->daily()
            ->at('00:05')
            ->appendOutputTo(storage_path('logs/cleanup-otp.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
