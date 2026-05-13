<?php

declare(strict_types=1);

namespace App\Services\Candidate;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Models\CompanyManager;
use App\Models\Customer;
use App\Models\Status;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Handles all interview/security report uploads.
 *
 * The candidates.interview_report field stores JSON:
 *   {"spi": "filename.pdf", "ellevio": "filename.pdf", "timra": "filename.pdf"}
 *
 * Allowed report types:
 *   spi     → main SPI security interview report  (shown when interview_upload_allowed = true)
 *   ellevio → Ellevio report                      (shown when ellevio_report = true)
 *   timra   → Timrå reference report              (shown when timra_report = true)
 *
 * Storage path: storage/app/security-reports/{candidate_id}/{type}/{filename}
 * Served via a secured controller route.
 */
class InterviewReportService
{
    public const DISK = 'local';
    public const BASE_PATH = 'security-reports';

    public const TYPE_SPI = 'spi';
    public const TYPE_ELLEVIO = 'ellevio';
    public const TYPE_TIMRA = 'timra';

    public const ALLOWED_TYPES = [
        self::TYPE_SPI,
        self::TYPE_ELLEVIO,
        self::TYPE_TIMRA,
    ];

    private const TYPE_LABELS = [
        self::TYPE_SPI => 'Interview SPI Report',
        self::TYPE_ELLEVIO => 'Interview Ellevio Report',
        self::TYPE_TIMRA => 'Timrå Interview Report',
    ];

    private const HISTORY_LABELS = [
        self::TYPE_SPI => 'Interview SPI Report Uploaded',
        self::TYPE_ELLEVIO => 'Interview Ellevio Report Uploaded',
        self::TYPE_TIMRA => 'Timrå Interview Report Uploaded',
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Upload a report file for a candidate.
     *
     * @param  string $type  One of TYPE_SPI | TYPE_ELLEVIO | TYPE_TIMRA
     */
    public function upload(Candidate $candidate, UploadedFile $file, string $type): string
    {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException("Unknown report type: {$type}");
        }

        // Delete old file of the same type if it exists.
        $existing = $this->getReports($candidate);
        if (! empty($existing[$type])) {
            $this->deleteFile($existing[$type]);
        }

        // Store new file.
        $filename = $this->storeFile($candidate, $file, $type);

        // Update the JSON field.
        $existing[$type] = $filename;
        $candidate->update(['interview_report' => json_encode($existing, JSON_THROW_ON_ERROR)]);

        // Log to history.
        $actor = Auth::user()?->name ?? 'system';
        $label = self::HISTORY_LABELS[$type] ?? 'Interview Report Uploaded';

        app(CandidateHistoryService::class)->log(
            $candidate->id,
            $label,
            "By {$actor}"
        );

        // Notify company managers (if they have can_view_report).
        $this->notifyCompanyManagers($candidate, $type, $filename);

        return $filename;
    }

    /**
     * Delete a report of the given type.
     */
    public function delete(Candidate $candidate, string $type): void
    {
        $existing = $this->getReports($candidate);

        if (empty($existing[$type])) {
            return;
        }

        $this->deleteFile($existing[$type]);
        unset($existing[$type]);

        $candidate->update([
            'interview_report' => empty($existing) ? null : json_encode($existing, JSON_THROW_ON_ERROR),
        ]);

        app(CandidateHistoryService::class)->log(
            $candidate->id,
            (self::TYPE_LABELS[$type] ?? 'Interview Report') . ' Deleted',
            'By ' . (Auth::user()?->name ?? 'system')
        );
    }

    /**
     * Parse candidates.interview_report into a typed array.
     *
     * @return array<string, string>  e.g. ['spi' => 'filename.pdf', 'ellevio' => ...]
     */
    public function getReports(Candidate $candidate): array
    {
        $raw = $candidate->interview_report;
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        // Legacy: old system stored a single filename string (not JSON).
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [self::TYPE_SPI => (string) $raw];
        }

        return array_filter($decoded, fn ($v) => ! empty($v));
    }

    /**
     * Full storage path for a given filename (used to attach to emails).
     */
    public function getAbsolutePath(string $filename): string
    {
        return Storage::disk(self::DISK)->path($filename);
    }

    /**
     * Check whether the candidate has a specific report type uploaded.
     */
    public function hasReport(Candidate $candidate, string $type): bool
    {
        return ! empty($this->getReports($candidate)[$type]);
    }

    /**
     * Return which report types are enabled for a customer.
     *
     * @return array<string, bool>
     */
    public function enabledTypesForCustomer(Customer $customer): array
    {
        return [
            self::TYPE_SPI => (bool) $customer->interview_upload_allowed,
            self::TYPE_ELLEVIO => (bool) $customer->ellevio_report,
            self::TYPE_TIMRA => (bool) $customer->timra_report,
        ];
    }

    // -------------------------------------------------------------------------
    // File storage helpers
    // -------------------------------------------------------------------------

    private function storeFile(Candidate $candidate, UploadedFile $file, string $type): string
    {
        $dir = self::BASE_PATH . '/' . $candidate->id . '/' . $type;
        $filename = uniqid('', true) . '_' . $file->getClientOriginalName();

        Storage::disk(self::DISK)->putFileAs($dir, $file, $filename);

        return $dir . '/' . $filename;
    }

    private function deleteFile(string $storagePath): void
    {
        try {
            if (Storage::disk(self::DISK)->exists($storagePath)) {
                Storage::disk(self::DISK)->delete($storagePath);
            }
        } catch (\Throwable $e) {
            Log::warning('InterviewReportService: could not delete file', [
                'path' => $storagePath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Company-manager notification
    // -------------------------------------------------------------------------

    /**
     * After a report is uploaded, notify any company_manager rows for the
     * candidate's customer that have can_view_report = true.
     * Uses the `email_template` (under-investigation) or `email_template_approved`
     * depending on the candidate's current status.
     */
    private function notifyCompanyManagers(Candidate $candidate, string $type, string $filename): void
    {
        if (! Schema::hasTable('company_manager')) {
            return;
        }

        $candidate->loadMissing(['customer.user', 'serviceType', 'statusRelation', 'staff', 'placeRelation']);
        $customer = $candidate->customer;

        if (! $customer) {
            return;
        }

        // Find manager rows for this customer's company.
        $managers = CompanyManager::where('cus_id', $customer->id)
            ->where('can_view_report', 1)
            ->get();

        // Also check by company name (old system checked company match).
        if ($managers->isEmpty() && $customer->company) {
            $managers = CompanyManager::where('company', $customer->company)
                ->where('can_view_report', 1)
                ->get();
        }

        if ($managers->isEmpty()) {
            return;
        }

        foreach ($managers as $manager) {
            $this->sendManagerNotification($candidate, $customer, $manager, $type, $filename);
        }
    }

    private function sendManagerNotification(
        Candidate $candidate,
        Customer $customer,
        CompanyManager $manager,
        string $type,
        string $filename
    ): void {
        // Choose template based on candidate status variable.
        $statusVariable = $candidate->statusRelation?->variable ?? '';
        $templateBody = $statusVariable === 'approved'
            ? ($manager->email_template_approved ?: $manager->email_template)
            : $manager->email_template;

        if (empty($templateBody)) {
            return;
        }

        // Find the manager user (by matching the company's customer email or user).
        $managerCustomer = $manager->customer;
        $managerEmail = $managerCustomer?->user?->email;
        $managerName = $managerCustomer?->user?->name ?? 'Manager';

        if (! $managerEmail) {
            return;
        }

        // Replace template variables (same as old system).
        $body = $this->replaceVariables(
            $templateBody,
            $candidate,
            $customer,
            $statusVariable
        );

        $subject = 'Interview Report Uploaded';

        // Attach the SPI report if available.
        $attachmentPath = null;
        if ($type === self::TYPE_SPI) {
            $abs = $this->getAbsolutePath($filename);
            if (file_exists($abs)) {
                $attachmentPath = $abs;
            }
        }

        try {
            $mailable = new CandidateStatusMail($subject, $body, $attachmentPath);
            Mail::to($managerEmail, $managerName)->send($mailable);
        } catch (\Throwable $e) {
            Log::error('InterviewReportService: manager notification failed', [
                'manager' => $managerEmail,
                'error' => $e->getMessage(),
            ]);
        }

        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => 'Customer',
                'user_name' => $managerName,
                'order_id' => $candidate->order_id,
                'msg_type' => 'Interview Report Uploaded',
                'text' => $body,
                'email' => $managerEmail,
                'subject' => $subject,
            ]);
        }
    }

    private function replaceVariables(string $text, Candidate $candidate, Customer $customer, string $statusLabel): string
    {
        $statusObj = $candidate->statusRelation;

        return app(EmailTemplateRenderer::class)->renderForCandidate(
            $text,
            $candidate,
            $statusObj,
            now()->format('Y-m-d'),
            '',
            '',
            ['status' => $statusLabel]  // override status label
        );
    }
}
