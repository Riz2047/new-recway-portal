<?php

declare(strict_types=1);

namespace App\Services\Cron;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Services\EmailTemplateRenderer;
use App\Models\CandidateHistory;
use App\Models\EmailTemplate;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Replicates sendStaffReminderEmails() from the old system.
 *
 * Trigger condition: a candidate has an assigned staff member, is in one of
 * the "active / needs staff action" statuses, and ≥ 5 working days have
 * passed since the last staff reminder comment OR since the candidate first
 * entered this status.
 *
 * Recipients: the single staff member (User) assigned to the candidate.
 *
 * Email template variable: "staff_reminder"
 * (previously "Staff Reminder Email for Status", id=4)
 *
 * Status variables considered "active / needs staff action":
 *   booked, rebooking, pending, under_investigation
 *   (mirrors the spirit of the old hardcoded IDs; configurable via
 *   config('cron.staff_reminder_status_variables'))
 */
class StaffReminderService
{
    private int $workingDaysThreshold;

    // Stored in comments to track when the last reminder was sent.
    public const COMMENT_MARKER = 'Reminder email send to assigned staff';

    // System comment author id — we use auth user id = 0/null for system
    private const SYSTEM_AUTHOR_TYPE = 'system';

    /**
     * Status variables that require staff reminders.
     * Adjust in config('cron.staff_reminder_status_variables') to override.
     */
    private const DEFAULT_STATUS_VARIABLES = [
        'booked',
        'rebooking',
        'pending',
        'under_investigation',
    ];

    public function __construct(
        private readonly SwedenWorkingHoursService $workingHours,
    ) {
        $this->workingDaysThreshold = (int) config('cron.staff_reminder.working_days_threshold', 5);
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

        if (! Schema::hasTable('candidates')) {
            return $stats;
        }

        $variables = config('cron.staff_reminder_status_variables', self::DEFAULT_STATUS_VARIABLES);
        $statusIds = Status::whereIn('variable', $variables)->pluck('id')->toArray();

        if (empty($statusIds)) {
            Log::info('StaffReminderService: no matching statuses found');
            return $stats;
        }

        $candidates = Candidate::with(['customer.user', 'serviceType', 'statusRelation', 'staff', 'placeRelation'])
            ->whereIn('status', $statusIds)
            ->whereNotNull('staff_id')
            ->where('staff_id', '!=', 0)
            ->where('expired', 0)
            ->get();

        foreach ($candidates as $candidate) {
            try {
                $result = $this->processCandidate($candidate);
                $result ? $stats['sent']++ : $stats['skipped']++;
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('StaffReminderService error', [
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
        $staff = $candidate->staff;
        if (! $staff || ! $staff->email) {
            return false;
        }

        // When did the candidate first enter this status?
        $statusDetail = $candidate->statusRelation?->status_detail;
        $firstStatusEntry = $statusDetail && Schema::hasTable('history')
            ? CandidateHistory::where('order_id', $candidate->id)
                ->where('desc', $statusDetail)
                ->orderBy('date_time')
                ->first(['date_time'])
            : null;

        if (! $firstStatusEntry) {
            // Fallback: use candidate creation date.
            $fromDate = optional($candidate->created_at)->toDateString() ?? now()->toDateString();
        } else {
            $fromDate = $firstStatusEntry->date_time->toDateString();
        }

        // When was the last staff reminder sent (from comments)?
        $lastReminderDate = $this->getLastReminderDate($candidate->id);
        if ($lastReminderDate) {
            $fromDate = $lastReminderDate;
        }

        $workingDaysPassed = $this->workingHours->workingDaysSince($fromDate);

        if ($workingDaysPassed < $this->workingDaysThreshold) {
            return false;
        }

        $emailBody = $this->buildEmailBody($candidate, $staff);
        $this->sendToStaff($candidate, $staff, $emailBody);
        $this->logReminderComment($candidate->id, $staff->name);

        return true;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function getLastReminderDate(int $candidateId): ?string
    {
        if (! Schema::hasTable('comments')) {
            return null;
        }

        $comment = DB::table('comments')
            ->where('order_id', $candidateId)
            ->where('comment', 'like', self::COMMENT_MARKER . '%')
            ->orderByDesc('id')
            ->first();

        if (! $comment) {
            return null;
        }

        $dateField = $comment->created_at ?? $comment->created ?? null;

        return $dateField ? \Carbon\Carbon::parse($dateField)->toDateString() : null;
    }

    private function buildEmailBody(Candidate $candidate, User $staff): string
    {
        $template = EmailTemplate::where('variable', 'staff_reminder')->first();

        $statusName = $candidate->statusRelation?->status ?? '';
        $serviceTitle = $candidate->serviceType?->name ?? '';
        $placeName = $candidate->placeRelation?->name ?? '';
        $customerName = $candidate->customer?->user?->name ?? '';
        $company = $candidate->customer?->company ?? '';

        $renderer = app(EmailTemplateRenderer::class);

        if ($template && ! empty($template->body)) {
            $body = $renderer->renderForCandidate(
                $template->body,
                $candidate,
                $candidate->statusRelation,
                now()->format('Y-m-d'),
                '',
                '',
                ['staff' => $staff->name, 'staff_email' => $staff->email]  // ensure staff context
            );
        } else {
            $statusName = $candidate->statusRelation?->status ?? '';
            $body = "Dear {$staff->name},<br><br>"
                . "This is a reminder that order <strong>{$candidate->order_id}</strong> "
                . "for candidate <strong>{$candidate->name} {$candidate->surname}</strong> "
                . "is still in status <strong>\"{$statusName}\"</strong>.<br><br>"
                . "Best regards,<br>Recway AB";
        }

        return $body;
    }

    private function sendToStaff(Candidate $candidate, User $staff, string $body): void
    {
        $statusName = $candidate->statusRelation?->status ?? '';
        $subject = "Reminder: Order {$candidate->order_id} in status \"{$statusName}\"";

        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => 'Staff',
                'user_name' => $staff->name,
                'order_id' => $candidate->order_id,
                'msg_type' => 'Staff Reminder',
                'text' => $body,
                'email' => $staff->email,
                'subject' => $subject,
            ]);
        }

        try {
            Mail::to($staff->email, $staff->name)
                ->send(new CandidateStatusMail($subject, $body));
        } catch (\Throwable $e) {
            Log::error('StaffReminderService: mail failed', [
                'to' => $staff->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logReminderComment(int $candidateId, string $staffName): void
    {
        if (! Schema::hasTable('comments')) {
            return;
        }

        DB::table('comments')->insert([
            'order_id' => $candidateId,
            'author_id' => 0,
            'author_type' => self::SYSTEM_AUTHOR_TYPE,
            'comment' => self::COMMENT_MARKER . ' ' . $staffName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
