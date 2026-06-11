<?php

declare(strict_types=1);

namespace App\Services\Candidate;

use App\Mail\CandidateStatusMail;
use App\Services\EmailTemplateRenderer;
use App\Services\Invoice\TaskInvoiceNotificationService;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Models\CandidateMessage;
use App\Models\Customer;
use App\Models\Status;
use App\Models\StatusServiceLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Handles the complete status-change workflow for a candidate:
 *   1. Update candidate status + auto-set dates
 *   2. Log history entry
 *   3. Resolve email template from messages table
 *   4. Check email permission (allowed_emails)
 *   5. Replace template variables
 *   6. Send email to customer (+ candidate on canceled)
 *   7. Save email to emails table for resend
 *   8. Handle the "combine" feature (BK → Security flow)
 */
class StatusWorkflowService
{
    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * @param array{
     *   date: string,
     *   comment: string,
     *   send_email?: bool,
     * } $options
     */
    /**
     * @param array{
     *   date: string,
     *   comment: string,
     *   send_email?: bool,
     *   combine_interview_id?: int|null,  // admin-chosen target service (overrides stored value)
     * } $options
     * @throws \RuntimeException when combine validation fails (caller surfaces this to UI)
     */
    public function handle(Candidate $candidate, int $newStatusId, array $options = []): void
    {
        $status = Status::find($newStatusId);
        if (! $status) {
            return;
        }

        $date = $options['date'] ?? now()->toDateString();
        $comment = $options['comment'] ?? '';
        $sendEmail = $options['send_email'] ?? true;

        // Reload all relations we'll need throughout the workflow.
        $candidate->load(['customer.user', 'serviceType', 'staff', 'placeRelation']);

        // Allow the caller to pass a combine target at request time (e.g. from the panel).
        if (! empty($options['combine_interview_id'])) {
            $candidate->combine_interview_id = (int) $options['combine_interview_id'];
        }

        // Pre-flight: validate combine before opening the transaction.
        $combineService = app(CombineInterviewService::class);
        if ($combineService->wouldTrigger($candidate, $status)) {
            $preCheck = $combineService->execute(
                clone $candidate,   // dry-check: clone so we don't mutate yet
                $status
            );

            // If combine would trigger but validation failed, throw immediately.
            // This propagates to the panel / controller before any DB write.
            if ($preCheck['triggered'] && ! $preCheck['success']) {
                throw new \RuntimeException($preCheck['error'] ?? __('Combine interview validation failed.'));
            }
        }

        DB::transaction(function () use ($candidate, $status, $date, $comment, $sendEmail, $combineService): void {
            // 1. Update candidate status + special date fields.
            $this->updateCandidateStatus($candidate, $status, $date);

            // 2. Log history.
            $this->logHistory($candidate, $status, $comment);

            // 3. Send email (if not suppressed).
            if ($sendEmail) {
                $this->triggerEmail($candidate, $status, $date, $comment);
            }

            // 4. Invoice task notification (before combine — so it sees the original service type).
            app(TaskInvoiceNotificationService::class)->notifyIfRequired($candidate, $status);

            // 5. Combine feature: BK → Security transfer.
            //    CombineInterviewService handles its own DB writes + history.
            $candidate->refresh();  // pick up the status written in step 1
            $combineResult = $combineService->execute($candidate, $status);

            if ($combineResult['triggered'] && ! $combineResult['success']) {
                // This shouldn't happen (we pre-validated above), but guard just in case.
                throw new \RuntimeException($combineResult['error'] ?? __('Combine interview transfer failed.'));
            }
        });
    }

    // -------------------------------------------------------------------------
    // Step 1 — Update candidate status + date fields
    // -------------------------------------------------------------------------

    private function updateCandidateStatus(Candidate $candidate, Status $status, string $date): void
    {
        $update = ['status' => $status->id];

        switch ($status->variable) {
            case 'booked':
                $update['booked'] = $date;
                break;

            case 'rebooking':
                $update['booked'] = null;
                break;

            case 'approval_received':
                // Auto-calculate delivery date: +3 days for service 10, +5 days otherwise.
                // Skip weekends.
                $days = ($candidate->interview_id == 10) ? 3 : 5;
                $delivery = date('Y-m-d', strtotime("{$date} +{$days} days"));
                if (date('N', strtotime($delivery)) >= 6) {
                    $daysToAdd = 8 - (int) date('N', strtotime($delivery));
                    $delivery = date('Y-m-d', strtotime("{$delivery} +{$daysToAdd} days"));
                }
                $update['delivery_date'] = $delivery;
                break;
        }

        $candidate->update($update);
        $candidate->refresh();
    }

    // -------------------------------------------------------------------------
    // Step 2 — History log  (delegates to shared CandidateHistoryService)
    // -------------------------------------------------------------------------

    private function logHistory(Candidate $candidate, Status $status, string $comment): void
    {
        app(CandidateHistoryService::class)->logStatusChange($candidate, $status, $comment);
    }

    // -------------------------------------------------------------------------
    // Step 3 — Email trigger
    // -------------------------------------------------------------------------

    private function triggerEmail(
        Candidate $candidate,
        Status $status,
        string $date,
        string $comment
    ): void {
        if (! Schema::hasTable('messages') || ! Schema::hasTable('status_services')) {
            return;
        }

        // Get the msg_col name for this status + service combination.
        $link = StatusServiceLink::where('status_id', $status->id)
            ->where('service_id', $candidate->interview_id)
            ->first();

        if (! $link || empty($link->msg_col)) {
            return;
        }

        // Get the customer's message template row.
        $messageRow = CandidateMessage::where('cus_id', $candidate->cus_id)
            ->where('interview_id', $candidate->interview_id)
            ->first();

        if (! $messageRow) {
            return;
        }

        // Look up the template by the msg_col key in the JSON column.
        $templateBody = $messageRow->getBodyForKey($link->msg_col);
        if (empty($templateBody)) {
            return;
        }

        // Check whether this customer allows emails for this status.
        $customer = $candidate->customer;
        if (! $customer) {
            return;
        }

        $customerEmail = $customer->user?->email ?? '';
        $customerName = $customer->user?->name ?? '';
        $customerCompany = $customer->company ?? '';

        if (! $this->isEmailAllowed($customer, $status->id)) {
            return;
        }

        // Replace template variables.
        $body = $this->replaceVariables($templateBody, $candidate, $status, $customerName, $customerCompany, $date, $comment);

        // Attach SPI report to approved/denied emails when customer has send_security_report = true.
        $attachmentPath = null;
        if (
            in_array($status->variable, ['approved', 'denied', 'approval_received'], true)
            && (bool) $customer->send_security_report
        ) {
            $reportService = app(InterviewReportService::class);
            $reports = $reportService->getReports($candidate);
            $spiFilename = $reports[InterviewReportService::TYPE_SPI] ?? null;

            if ($spiFilename) {
                $abs = $reportService->getAbsolutePath($spiFilename);
                if (file_exists($abs)) {
                    $attachmentPath = $abs;
                }
            }
        }

        // Send to main customer.
        if ($customerEmail) {
            $this->sendAndSave(
                body: $body,
                to: $customerEmail,
                name: $customerName,
                subject: $status->status,
                userType: 'Customer',
                userName: $customerName,
                orderId: $candidate->order_id,
                msgType: $status->status . ' Message',
                attachmentPath: $attachmentPath
            );
        }

        // Send to additional customers (CC contacts on the customer account).
        // Mirrors old system: pages.php — "additional customers email send" blocks.
        if (Schema::hasTable('additional_customers')) {
            $additionalContacts = DB::table('additional_customers')
                ->where('cus_id', $candidate->cus_id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            foreach ($additionalContacts as $contact) {
                $this->sendAndSave(
                    body: $body,
                    to: $contact->email,
                    name: $contact->name ?? $contact->email,
                    subject: $status->status,
                    userType: 'Additional Customer',
                    userName: $contact->name ?? $contact->email,
                    orderId: $candidate->order_id,
                    msgType: $status->status . ' Message',
                    attachmentPath: $attachmentPath
                );
            }
        }

        // On "canceled", also send to the candidate themselves.
        if ($status->variable === 'canceled' && $candidate->email) {
            $candidateBody = $this->replaceVariables(
                $templateBody,
                $candidate,
                $status,
                $customerName,
                $customerCompany,
                $date,
                $comment
            );
            $this->sendAndSave(
                body: $candidateBody,
                to: $candidate->email,
                name: trim($candidate->name . ' ' . $candidate->surname),
                subject: 'Order Canceled',
                userType: 'Candidate',
                userName: trim($candidate->name . ' ' . $candidate->surname),
                orderId: $candidate->order_id,
                msgType: 'Order Cancel Candidate'
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if the customer allows email notifications for this status.
     * After the migration refactor, allowed_emails has a JSON `allowed_status_ids` array per customer.
     */
    private function isEmailAllowed(Customer $customer, int $statusId): bool
    {
        if (! Schema::hasTable('allowed_emails')) {
            return true; // default: allow if table doesn't exist
        }

        $row = DB::table('allowed_emails')
            ->where('cus_id', $customer->id)
            ->first();

        if (! $row) {
            return false; // no row = emails not configured = don't send
        }

        $allowedIds = json_decode((string) ($row->allowed_status_ids ?? '[]'), true);
        if (! is_array($allowedIds)) {
            return false;
        }

        return in_array($statusId, array_map('intval', $allowedIds), true);
    }

    /**
     * Replace all {variable} placeholders using the central EmailTemplateRenderer.
     */
    private function replaceVariables(
        string $text,
        Candidate $candidate,
        Status $status,
        string $customerName,
        string $customerCompany,
        string $date,
        string $comment
    ): string {
        return app(EmailTemplateRenderer::class)->renderForCandidate(
            $text,
            $candidate,
            $status,
            $date,
            $comment
        );
    }

    /**
     * Send email and immediately save a copy to the `emails` table for later resending.
     * $attachmentPath — absolute file path to attach (e.g. security report PDF).
     */
    private function sendAndSave(
        string  $body,
        string  $to,
        string  $name,
        string  $subject,
        string  $userType,
        string  $userName,
        string  $orderId,
        string  $msgType,
        ?string $attachmentPath = null
    ): void {
        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => $userType,
                'user_name' => $userName,
                'order_id' => $orderId,
                'msg_type' => $msgType,
                'text' => $body,
                'email' => $to,
                'subject' => $subject,
            ]);
        }

        try {
            Mail::to($to, $name)->queue(new CandidateStatusMail($subject, $body, $attachmentPath));
        } catch (\Throwable $e) {
            Log::error('CandidateStatusMail failed', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
