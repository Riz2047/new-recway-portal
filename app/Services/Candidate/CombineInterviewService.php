<?php

declare(strict_types=1);

namespace App\Services\Candidate;

use App\Models\Candidate;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the "Combine Interview" (BK → Security) feature.
 *
 * Business rules (mirrors old system exactly):
 *
 *  1. Customer has combine_bk_and_security  = comma-separated service_type IDs
 *                  combine_status           = comma-separated status IDs that TRIGGER the transfer
 *                  combine_interview_service = target security-interview service_type ID (customer default)
 *
 *  2. Candidate can override the target via combine_interview_id (per-candidate choice).
 *
 *  3. When status changes to one of combine_status AND the candidate's current interview_id
 *     is in combine_bk_and_security:
 *       a) Resolve target service type (candidate override → customer default)
 *       b) If no target set → validation error (block status change)
 *       c) Find the first (lowest id) status for the target service's service_category
 *       d) Update candidate: interview_id = target, status = first-status
 *       e) Log history
 *       f) (optionally) trigger pending-status email on the NEW service type
 *
 * Key improvement over old system:
 *   - No recursive PHP include hack — pure, atomic, testable
 *   - Proper first-status lookup (not hardcoded to ID 1)
 *   - Validation before any DB write
 *   - Returns structured result so callers can surface errors to the UI
 */
class CombineInterviewService
{
    public function __construct(
        private readonly CandidateHistoryService $history,
    ) {
    }

    // -------------------------------------------------------------------------
    // Check whether a status change would trigger a combine transfer
    // -------------------------------------------------------------------------

    /**
     * Returns true when changing to $status on $candidate would trigger a
     * combine-interview transfer (regardless of whether it will succeed).
     */
    public function wouldTrigger(Candidate $candidate, Status $status): bool
    {
        $customer = $candidate->customer;
        if (! $customer) {
            return false;
        }

        if (! $this->statusIsInCombineList($status, $customer)) {
            return false;
        }

        return $this->serviceIsInCombineList($candidate, $customer);
    }

    /**
     * Returns the resolved target service type, or null if none configured.
     *
     * Resolution order (most specific first):
     *   1. candidate.combine_interview_id  (per-candidate FK override)
     *   2. customer.combine_interview_id   (customer-level FK, new column)
     *   3. customer.combine_interview_service (legacy string field, backward-compat)
     */
    public function resolveTargetServiceType(Candidate $candidate): ?ServiceType
    {
        // 1. Per-candidate FK override.
        if ($candidate->combine_interview_id) {
            $st = ServiceType::find($candidate->combine_interview_id);
            if ($st) {
                return $st;
            }
        }

        $customer = $candidate->customer;
        if (! $customer) {
            return null;
        }

        // 2. Customer-level FK (new column).
        if ($customer->combine_interview_id) {
            $st = ServiceType::find($customer->combine_interview_id);
            if ($st) {
                return $st;
            }
        }

        // 3. Legacy string field (backward-compat for rows where FK not yet set).
        $legacyId = (int) ($customer->combine_interview_service ?? 0);
        if ($legacyId > 0) {
            return ServiceType::find($legacyId);
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Execute the transfer
    // -------------------------------------------------------------------------

    /**
     * Attempt to execute the combine transfer.
     *
     * @return array{triggered:bool, success:bool, error?:string, target_service?:ServiceType, new_status?:Status}
     */
    public function execute(Candidate $candidate, Status $triggerStatus): array
    {
        $customer = $candidate->customer;

        // --- Guard: customer must exist ---
        if (! $customer) {
            return ['triggered' => false, 'success' => false];
        }

        // --- Guard: status must be in combine list ---
        if (! $this->statusIsInCombineList($triggerStatus, $customer)) {
            return ['triggered' => false, 'success' => false];
        }

        // --- Guard: candidate's service must be a "BK" service ---
        if (! $this->serviceIsInCombineList($candidate, $customer)) {
            return ['triggered' => false, 'success' => false];
        }

        // Combine DOES trigger — now validate the target.
        $targetService = $this->resolveTargetServiceType($candidate);

        if (! $targetService) {
            return [
                'triggered' => true,
                'success' => false,
                'error' => __(
                    'This status triggers a combine-interview transfer but no target security-interview service type is set. '
                    . 'Please set the "Security Interview Service Type" on the candidate or customer before changing to this status.'
                ),
            ];
        }

        // Find first status for target service category.
        $firstStatus = $this->resolveFirstStatus($targetService);

        if (! $firstStatus) {
            return [
                'triggered' => true,
                'success' => false,
                'error' => __('Could not find a starting status for the target service category.'),
            ];
        }

        // Execute the transfer atomically.
        DB::transaction(function () use ($candidate, $targetService, $firstStatus, $triggerStatus): void {
            $candidate->update([
                'interview_id' => $targetService->id,
                'status' => $firstStatus->id,
            ]);

            $candidate->refresh();

            $this->history->log(
                $candidate->id,
                "Combined interview: transferred to {$targetService->name}",
                "Previous service: {$triggerStatus->status}. "
                . "Target service: {$targetService->name}. "
                . "New status: {$firstStatus->status}."
            );
        });

        Log::info('CombineInterviewService: transfer executed', [
            'candidate_id' => $candidate->id,
            'order_id' => $candidate->order_id,
            'trigger_status' => $triggerStatus->status,
            'target_service' => $targetService->name,
            'new_status' => $firstStatus->status,
        ]);

        return [
            'triggered' => true,
            'success' => true,
            'target_service' => $targetService,
            'new_status' => $firstStatus,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function statusIsInCombineList(Status $status, Customer $customer): bool
    {
        $combineStatuses = array_filter(
            array_map('trim', explode(',', $customer->combine_status ?? ''))
        );

        return ! empty($combineStatuses) && in_array((string) $status->id, $combineStatuses, true);
    }

    private function serviceIsInCombineList(Candidate $candidate, Customer $customer): bool
    {
        $combineServices = array_filter(
            array_map('trim', explode(',', $customer->combine_bk_and_security ?? ''))
        );

        return ! empty($combineServices)
            && in_array((string) $candidate->interview_id, $combineServices, true);
    }

    /**
     * Find the first (lowest id) status that belongs to the target service's
     * service category. Falls back to the global lowest status id.
     */
    private function resolveFirstStatus(ServiceType $targetService): ?Status
    {
        $catId = $targetService->service_category_id;

        if ($catId) {
            $status = Status::where('status_type', $catId)->orderBy('id')->first();
            if ($status) {
                return $status;
            }
        }

        // Global fallback — lowest status id.
        return Status::orderBy('id')->first();
    }
}
