<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Models\InvoiceStaffEmail;
use App\Models\Status;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Replicates sendTaskInvoiceEmails() from the old functions.php.
 *
 * Triggered inside StatusWorkflowService after a status change.
 * If the new status is a terminal one (Approved, Under Investigation, etc.)
 * and the candidate previously had a "Booked" history entry, we send
 * a notification email to all "Manager with statistics" users saying
 * "interview complete — please send invoice to customer".
 *
 * Each (candidate, manager) pair is tracked in invoice_staff_email to
 * prevent duplicate notifications.
 */
class TaskInvoiceNotificationService
{
    /**
     * Status variables that, when reached, mean an interview outcome
     * occurred and an invoice should be raised.
     */
    private const TERMINAL_VARIABLES = [
        'approved',
        'denied',
        'interview_interrupted',
        'not_show_up',
        'approval_received',
        'deviation',
        'under_investigation',
    ];

    /**
     * Main entry point — called from StatusWorkflowService.
     */
    public function notifyIfRequired(Candidate $candidate, Status $status): void
    {
        if (! Schema::hasTable('invoice_staff_email')) {
            return;
        }

        // Only act on terminal statuses.
        if (! in_array($status->variable, self::TERMINAL_VARIABLES, true)) {
            return;
        }

        // The candidate must have had a "Booked" history entry (interview actually happened).
        if (! $this->wasEverBooked($candidate->id)) {
            return;
        }

        // Get managers with "Manager with statistics" role.
        $managers = $this->getManagers();
        if ($managers->isEmpty()) {
            return;
        }

        $emailBody = $this->buildEmailBody($candidate, $status);

        foreach ($managers as $manager) {
            $this->sendToManager($candidate, $manager, $emailBody);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function wasEverBooked(int $candidateId): bool
    {
        if (! Schema::hasTable('history')) {
            return false;
        }

        $bookedDetail = Status::where('variable', 'booked')->value('status_detail');
        if (! $bookedDetail) {
            return false;
        }

        return DB::table('history')
            ->where('order_id', $candidateId)
            ->where('desc', $bookedDetail)
            ->exists();
    }

    private function getManagers(): \Illuminate\Database\Eloquent\Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return User::whereHas('roles', fn ($q) => $q->where('name', 'Manager with statistics'))
            ->whereNotNull('email')
            ->get(['id', 'name', 'email']);
    }

    private function buildEmailBody(Candidate $candidate, Status $status): string
    {
        $candidate->loadMissing(['customer.user', 'serviceType']);

        $customerName = $candidate->customer?->user?->name ?? 'Unknown';
        $customerCompany = $candidate->customer?->company ?? '';
        $serviceTitle = $candidate->serviceType?->name ?? '';
        $invoicePeriod = $candidate->customer?->invoice_period ?? '';

        $periodText = match (strtolower($invoicePeriod)) {
            'day' => 'daily',
            'week' => 'weekly',
            'month' => 'monthly',
            default => $invoicePeriod,
        };

        // Try to find a custom template (EmailTemplate with variable = 'task_invoice_email')
        $template = \App\Models\EmailTemplate::where('variable', 'task_invoice_email')->first();

        if ($template && ! empty($template->body)) {
            // Replace placeholders using the central renderer.
            $body = app(EmailTemplateRenderer::class)->renderForCandidate(
                $template->body,
                $candidate,
                $status,
                now()->format('Y-m-d'),
                '',
                '',
                ['invoice_period' => $periodText]  // add invoice_period extra
            );
        } else {
            // Fallback template (mirrors old PHP fallback).
            $body = "Dear Manager,<br><br>"
                . "This is a task invoice notification for order <strong>{$candidate->order_id}</strong> "
                . "for candidate <strong>{$candidate->name} {$candidate->surname}</strong>.<br><br>"
                . "Interview outcome: <strong>{$status->status}</strong><br>"
                . "Customer: <strong>{$customerName}</strong>"
                . ($customerCompany ? " — {$customerCompany}" : '') . "<br>"
                . "Service: <strong>{$serviceTitle}</strong><br><br>"
                . ($periodText ? "This customer has <strong>{$periodText}</strong> invoice period. Please send the invoice to the customer.<br><br>" : '')
                . "Best regards,<br>Recway AB";
        }

        return $body;
    }

    private function sendToManager(Candidate $candidate, User $manager, string $body): void
    {
        // Guard against duplicate notification (same candidate + manager).
        $alreadySent = InvoiceStaffEmail::where('order_id', $candidate->id)
            ->where('staff_id', $manager->id)
            ->where('invoice_email', 1)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $subject = "Task Invoice: Order {$candidate->order_id} — Send Invoice to Customer";

        // Send the email.
        try {
            Mail::to($manager->email, $manager->name)->send(
                new CandidateStatusMail($subject, $body)
            );
        } catch (\Throwable $e) {
            Log::error('TaskInvoiceNotificationService: mail failed', [
                'manager' => $manager->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Save to emails log (always, regardless of mail success).
        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => 'Staff',
                'user_name' => $manager->name,
                'order_id' => $candidate->order_id,
                'msg_type' => 'Task Invoice Email',
                'text' => $body,
                'email' => $manager->email,
                'subject' => $subject,
            ]);
        }

        // Mark as sent to prevent duplicate.
        InvoiceStaffEmail::firstOrCreate(
            ['order_id' => $candidate->id, 'staff_id' => $manager->id],
            ['invoice_email' => 1]
        );
    }
}
