<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Models\CandidateHistory;
use App\Models\EmailTemplate;
use App\Models\Status;
use App\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Replicates sendInvestigationReminderEmails() from the old system.
 *
 * Trigger condition: a candidate is in "under_investigation" status AND has
 * an interview report uploaded, and ≥ 5 working days have passed since the
 * report upload OR the last reminder (whichever is more recent).
 *
 * Recipients: all company_manager rows where can_view_report = 1.
 *
 * Email template variable: "investigation_reminder"
 * (previously "Active manager reminder order still under investigation", id=3)
 */
class InvestigationReminderService
{
    private const WORKING_DAYS_THRESHOLD = 5;

    // History description used to track that a reminder was already sent.
    public const HISTORY_DESC = 'Reminder email send to Active status manager';

    // History description that marks when the first report was uploaded.
    private const REPORT_UPLOAD_DESC_PATTERN = 'Interview%Report Uploaded%';

    public function __construct(
        private readonly SwedenWorkingHoursService $workingHours,
    ) {
    }

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * @return array{sent:int, skipped:int, errors:int}
     */
    public function run(): array
    {
        $stats = ['sent' => 0, 'skipped' => 0, 'errors' => 0];

        if (! Schema::hasTable('candidates') || ! Schema::hasTable('history')) {
            Log::info('InvestigationReminderService: required tables missing');
            return $stats;
        }

        $underInvestigationStatusIds = Status::where('variable', 'under_investigation')->pluck('id')->toArray();

        if (empty($underInvestigationStatusIds)) {
            Log::info('InvestigationReminderService: no under_investigation statuses found');
            return $stats;
        }

        // Candidates under investigation with an uploaded interview report.
        $candidates = Candidate::with(['customer.user', 'serviceType', 'statusRelation', 'staff', 'placeRelation'])
            ->whereIn('status', $underInvestigationStatusIds)
            ->whereNotNull('interview_report')
            ->where('interview_report', '!=', '')
            ->where('expired', 0)
            ->get();

        foreach ($candidates as $candidate) {
            try {
                $result = $this->processCandidate($candidate);
                $result ? $stats['sent']++ : $stats['skipped']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('InvestigationReminderService error', [
                    'candidate' => $candidate->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    // -------------------------------------------------------------------------
    // Per-candidate logic
    // -------------------------------------------------------------------------

    private function processCandidate(Candidate $candidate): bool
    {
        // Find when the first report was uploaded (earliest history entry matching "Interview%Report Uploaded%").
        $firstReportRow = CandidateHistory::where('order_id', $candidate->id)
            ->where('desc', 'like', self::REPORT_UPLOAD_DESC_PATTERN)
            ->orderBy('date_time')
            ->first(['date_time']);

        if (! $firstReportRow || empty($firstReportRow->date_time)) {
            return false; // No report upload in history → skip.
        }

        // Find the most recent reminder already sent (to avoid sending again too soon).
        $lastReminderRow = CandidateHistory::where('order_id', $candidate->id)
            ->where('desc', self::HISTORY_DESC)
            ->orderByDesc('date_time')
            ->first(['date_time']);

        $fromDate = $lastReminderRow
            ? $lastReminderRow->date_time->toDateString()
            : $firstReportRow->date_time->toDateString();

        $workingDaysPassed = $this->workingHours->workingDaysSince($fromDate);

        if ($workingDaysPassed < self::WORKING_DAYS_THRESHOLD) {
            return false; // Not yet time.
        }

        // Get managers for this customer's company.
        $customer = $candidate->customer;
        if (! $customer || ! $customer->company) {
            return false;
        }

        $managers = $this->getActiveManagers($customer->company);
        if (empty($managers)) {
            return false;
        }

        $emailBody = $this->buildEmailBody($candidate);
        $sent = false;

        foreach ($managers as $manager) {
            if (empty($manager['email'])) {
                continue;
            }
            $this->sendToManager($candidate, $manager, $emailBody);
            $sent = true;
        }

        if ($sent) {
            $this->logReminder($candidate->id);
        }

        return $sent;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array<int, array{name:string, email:string, company:string}> */
    private function getActiveManagers(string $company): array
    {
        if (! Schema::hasTable('company_manager')) {
            return [];
        }

        return DB::table('company_manager as cm')
            ->join('customers as cu', 'cm.cus_id', '=', 'cu.id')
            ->join('users as u', 'cu.user_id', '=', 'u.id')
            ->where('cm.company', $company)
            ->where('cm.can_view_report', 1)
            ->whereNotNull('u.email')
            ->select(['u.name', 'u.email', 'cm.company'])
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function buildEmailBody(Candidate $candidate): string
    {
        $template = EmailTemplate::where('variable', 'investigation_reminder')->first();

        $statusName = $candidate->statusRelation?->status ?? 'Under investigation';
        $serviceTitle = $candidate->serviceType?->name ?? '';
        $placeName = $candidate->placeRelation?->name ?? '';
        $staffName = $candidate->staff?->name ?? '';
        $customerName = $candidate->customer?->user?->name ?? '';
        $company = $candidate->customer?->company ?? '';
        $now = $this->workingHours->now()->format('Y-m-d H:i:s');

        $renderer = app(EmailTemplateRenderer::class);

        if ($template && ! empty($template->body)) {
            $body = $renderer->renderForCandidate(
                $template->body,
                $candidate,
                $candidate->statusRelation,
                $this->workingHours->now()->format('Y-m-d H:i:s'),
            );
        } else {
            $body = "Dear {$customerName},<br><br>"
                . "This is a reminder that order <strong>{$candidate->order_id}</strong> "
                . "for candidate <strong>{$candidate->name} {$candidate->surname}</strong> "
                . "is still in status <strong>\"{$statusName}\"</strong> and the interview report is available.<br><br>"
                . "Best regards,<br>Recway AB";
        }

        return $body;
    }

    /** @param array{name:string, email:string} $manager */
    private function sendToManager(Candidate $candidate, array $manager, string $body): void
    {
        $subject = "Reminder: Order {$candidate->order_id} under investigation";

        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => 'Customer',
                'user_name' => $manager['name'],
                'order_id' => $candidate->order_id,
                'msg_type' => 'Investigation Reminder',
                'text' => $body,
                'email' => $manager['email'],
                'subject' => $subject,
            ]);
        }

        try {
            Mail::to($manager['email'], $manager['name'])
                ->send(new CandidateStatusMail($subject, $body));
        } catch (\Throwable $e) {
            Log::error('InvestigationReminderService: mail failed', [
                'to' => $manager['email'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logReminder(int $candidateId): void
    {
        CandidateHistory::create([
            'order_id' => $candidateId,
            'desc' => self::HISTORY_DESC,
            'date_time' => now(),
            'comment' => 'By System (Investigation Reminder)',
        ]);
    }
}
