<?php

declare(strict_types=1);

namespace App\Services\Candidate;

use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Central service for writing candidate audit-trail entries.
 * Every public method produces a standardised description so history
 * reads consistently regardless of where the action was triggered.
 */
class CandidateHistoryService
{
    // -------------------------------------------------------------------------
    // Core writer
    // -------------------------------------------------------------------------

    public function log(int $candidateId, string $desc, string $comment = ''): void
    {
        if (! Schema::hasTable('history')) {
            return;
        }

        CandidateHistory::create([
            'order_id' => $candidateId,
            'desc' => $desc,
            'date_time' => now(),
            'comment' => $comment ?: null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Typed log helpers — each mirrors a distinct user action
    // -------------------------------------------------------------------------

    public function logStatusChange(Candidate $candidate, Status $status, string $comment = ''): void
    {
        $actor = Auth::user()?->name ?? 'system';

        // Use status_detail when available ("Candidate has been approved"),
        // otherwise fall back to the status name ("Approved").
        $desc = ($status->status_detail && $status->status_detail !== $status->status)
            ? $status->status_detail
            : $status->status;

        $fullComment = $comment
            ? "{$comment}\n— {$actor}"
            : "— {$actor}";

        $this->log($candidate->id, $desc, $fullComment);
    }

    public function logStaffAssigned(Candidate $candidate, string $staffName, string $comment = ''): void
    {
        $desc = "Staff ({$staffName}) assigned to {$candidate->name} {$candidate->surname}";
        $this->log($candidate->id, $desc, $comment);
    }

    public function logStaffRemoved(Candidate $candidate): void
    {
        $this->log($candidate->id, "Staff removed from {$candidate->name} {$candidate->surname}");
    }

    public function logCandidateUpdated(Candidate $candidate): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "Candidate details updated", "— {$actor}");
    }

    // Background check
    public function logBkChanged(Candidate $candidate, string $field, string $value): void
    {
        $labels = ['economy' => 'Economy', 'criminal_record' => 'Criminal Record', 'social' => 'Social'];
        $results = ['-1' => 'Pending', '0' => 'Clear', '1' => 'Found'];
        $actor = Auth::user()?->name ?? 'system';

        $fieldLabel = $labels[$field] ?? $field;
        $resultLabel = $results[$value] ?? $value;

        $this->log(
            $candidate->id,
            "Background check — {$fieldLabel}: {$resultLabel}",
            "— {$actor}"
        );
    }

    // Invoice
    public function logInvoiceSent(Candidate $candidate, bool $sent): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $desc = $sent ? 'Invoice marked as sent' : 'Invoice mark removed';
        $this->log($candidate->id, $desc, "— {$actor}");
    }

    public function logReported(Candidate $candidate, bool $reported): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $desc = $reported ? 'Order reported to SM' : 'Order unreported from SM';
        $this->log($candidate->id, $desc, "— {$actor}");
    }

    // Document operations
    public function logDocumentUploaded(Candidate $candidate, string $filename, string $type = 'CV'): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "{$type} document uploaded: {$filename}", "— {$actor}");
    }

    public function logDocumentDeleted(Candidate $candidate, string $filename, string $type = 'CV'): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "{$type} document deleted: {$filename}", "— {$actor}");
    }

    public function logInterviewTemplateUploaded(Candidate $candidate, string $filename): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "Interview template uploaded: {$filename}", "— {$actor}");
    }

    public function logBkDocumentUploaded(Candidate $candidate, string $filename, string $typeLabel): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "Background check document uploaded — {$typeLabel}: {$filename}", "— {$actor}");
    }

    public function logBkDocumentDeleted(Candidate $candidate, string $filename): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $this->log($candidate->id, "Background check document deleted: {$filename}", "— {$actor}");
    }

    // Manual / custom entry
    public function logManual(Candidate $candidate, string $desc, string $comment = ''): void
    {
        $actor = Auth::user()?->name ?? 'system';
        $comment = $comment ? "{$comment}\n— {$actor}" : "— {$actor}";
        $this->log($candidate->id, $desc, $comment);
    }
}
