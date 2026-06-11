<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CandidateHistory;
use App\Models\Customer;
use App\Services\Cron\InvestigationReminderService;
use App\Services\Cron\StaffReminderService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CronStatusController extends Controller
{
    public function index(): Renderable
    {
        $this->authorize('viewAny', Customer::class);
        $this->setBreadcrumbTitle(__('Cron Jobs'));

        $jobs       = $this->collectJobStats();
        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('failed_at')->get()
            : collect();

        return $this->renderViewWithBreadcrumbs('backend.pages.cron.index', [
            'jobs'       => $jobs,
            'failedJobs' => $failedJobs,
        ]);
    }

    public function retryJob(Request $request, int|string $id): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
            Artisan::call('queue:work', [
                '--once'  => true,
                '--queue' => 'default',
            ]);
            return back()->with('success', __('Job #:id has been retried.', ['id' => $id]));
        } catch (\Throwable $e) {
            return back()->with('error', __('Retry failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function retryAll(Request $request): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $count = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->count()
            : 0;

        if ($count === 0) {
            return back()->with('success', __('No failed jobs to retry.'));
        }

        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            Artisan::call('queue:work', [
                '--once'  => true,
                '--queue' => 'default',
            ]);
            return back()->with('success', __(':count failed job(s) pushed back to the queue.', ['count' => $count]));
        } catch (\Throwable $e) {
            return back()->with('error', __('Retry all failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function deleteFailedJob(Request $request, int|string $id): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        DB::table('failed_jobs')->where('id', $id)->delete();

        return back()->with('success', __('Failed job #:id deleted.', ['id' => $id]));
    }

    /**
     * Stream the last N lines of a cron log file.
     */
    public function showLog(string $file): \Illuminate\Http\Response
    {
        $this->authorize('viewAny', Customer::class);

        // Whitelist: only known log filenames.
        $allowed = [
            'reminders-investigation.log',
            'reminders-staff.log',
            'emails-delayed.log',
            'invoices-generate.log',
            'cleanup-otp.log',
        ];

        if (! in_array($file, $allowed, true)) {
            abort(404);
        }

        $path = storage_path('logs/' . $file);

        if (! file_exists($path)) {
            return response("Log file not found: {$file}", 404)->header('Content-Type', 'text/plain');
        }

        // Return last 200 lines.
        $lines = file($path);
        $tail = implode('', array_slice($lines, -200));

        return response($tail)->header('Content-Type', 'text/plain');
    }

    /**
     * Run a cron job manually from the admin UI.
     */
    public function runJob(Request $request): RedirectResponse
    {
        $this->authorize('update', Customer::class);

        $command = $request->input('command');

        $allowed = [
            'reminders:investigation',
            'reminders:staff',
            'reminders:all',
            'emails:process-delayed',
            'cleanup:otp',
            'invoices:generate',
        ];

        if (! in_array($command, $allowed, true)) {
            return back()->with('error', __('Unknown command.'));
        }

        try {
            $exitCode = Artisan::call($command);
            $output = Artisan::output();

            return back()->with(
                $exitCode === 0 ? 'success' : 'error',
                __('Command :cmd finished.', ['cmd' => $command]) . "\n" . $output
            );
        } catch (\Throwable $e) {
            return back()->with('error', __('Command failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    // -------------------------------------------------------------------------

    private function collectJobStats(): array
    {
        $jobs = [];

        // ── Investigation reminders ──────────────────────────────────────
        $lastInvestigation = Schema::hasTable('history')
            ? CandidateHistory::where('desc', InvestigationReminderService::HISTORY_DESC)
                ->max('date_time')
            : null;

        $pendingInvestigation = 0;
        if (Schema::hasTable('candidates') && Schema::hasTable('statuses')) {
            $underIds = DB::table('statuses')->where('variable', 'under_investigation')->pluck('id');
            $pendingInvestigation = DB::table('candidates')
                ->whereIn('status', $underIds)
                ->whereNotNull('interview_report')
                ->where('interview_report', '!=', '')
                ->where('expired', 0)
                ->count();
        }

        $jobs[] = [
            'name' => __('Investigation Reminders'),
            'command' => 'reminders:investigation',
            'description' => __('Notifies company managers when a candidate stays under investigation with an uploaded report for ≥ 5 working days.'),
            'schedule' => __('Weekdays at :time', ['time' => config('cron.reminders_time', '17:00')]),
            'last_run' => $lastInvestigation,
            'pending' => $pendingInvestigation,
            'log_file' => 'reminders-investigation.log',
        ];

        // ── Staff reminders ──────────────────────────────────────────────
        $lastStaff = Schema::hasTable('comments')
            ? DB::table('comments')
                ->where('comment', 'like', StaffReminderService::COMMENT_MARKER . '%')
                ->max('created_at')
            : null;

        $jobs[] = [
            'name' => __('Staff Reminders'),
            'command' => 'reminders:staff',
            'description' => __('Reminds assigned staff when a candidate stalls in an active status for ≥ 5 working days.'),
            'schedule' => __('Weekdays at :time', ['time' => config('cron.reminders_time', '17:00')]),
            'last_run' => $lastStaff,
            'pending' => null,
            'log_file' => 'reminders-staff.log',
        ];

        // ── Invoice generation ───────────────────────────────────────────
        $lastInvoice = Schema::hasTable('customer_invoices')
            ? DB::table('customer_invoices')->max('created_date')
            : null;

        $jobs[] = [
            'name' => __('Invoice Generation'),
            'command' => 'invoices:generate',
            'description' => __('Auto-generates customer invoices based on their invoice_period (daily/weekly/monthly).'),
            'schedule' => __('Weekdays at 09:00'),
            'last_run' => $lastInvoice,
            'pending' => null,
            'log_file' => 'invoices-generate.log',
        ];

        // ── Delayed emails ───────────────────────────────────────────────
        $pendingDelayed = Schema::hasTable('emails')
            ? DB::table('emails')->where('email_delay', 1)->count()
            : 0;

        $jobs[] = [
            'name' => __('Process Delayed Emails'),
            'command' => 'emails:process-delayed',
            'description' => __('Sends emails saved with email_delay=1 (queued outside working hours).'),
            'schedule' => __('Weekdays at 08:05'),
            'last_run' => null,
            'pending' => $pendingDelayed,
            'log_file' => 'emails-delayed.log',
        ];

        // ── OTP cleanup ──────────────────────────────────────────────────
        $jobs[] = [
            'name' => __('OTP Cleanup'),
            'command' => 'cleanup:otp',
            'description' => __('Deletes expired OTP verification records (> 24 hours old).'),
            'schedule' => __('Daily at 00:05'),
            'last_run' => null,
            'pending' => null,
            'log_file' => 'cleanup-otp.log',
        ];

        return $jobs;
    }
}
