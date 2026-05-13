<?php

declare(strict_types=1);

namespace App\Services\Invoice;

use App\Models\Candidate;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Replicates cron_generate_invoices.php logic.
 *
 * For each customer with invoice_period set, checks whether today is the
 * right calendar boundary (daily/Monday/1st-of-month), then collects all
 * uninvoiced billable candidates and creates a CustomerInvoice record.
 *
 * "Billable" means the candidate reached a terminal status that warrants
 * billing, AND has not already been included in a previous invoice.
 */
class InvoiceService
{
    /**
     * Terminal status variables that trigger billing.
     * These match the old system's status IDs via variable names so we are
     * decoupled from numeric IDs that differ per installation.
     */
    private const BILLABLE_STATUS_VARIABLES = [
        'approved',
        'denied',
        'interview_interrupted',
        'not_show_up',
        'canceled',          // only when booked ≥24 h before cancel (handled below)
        'approval_received',
        'deviation',
    ];

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Run for all customers. Called by the artisan command.
     *
     * @return array{processed:int, skipped:int, invoices_created:int, errors:int}
     */
    public function generateAll(): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'invoices_created' => 0, 'errors' => 0];

        if (! Schema::hasTable('customers') || ! Schema::hasTable('customer_invoices')) {
            Log::warning('InvoiceService: required tables missing');
            return $stats;
        }

        $customers = Customer::with('user')
            ->whereNotNull('invoice_period')
            ->whereIn('invoice_period', [
                CustomerInvoice::PERIOD_DAY,
                CustomerInvoice::PERIOD_WEEK,
                CustomerInvoice::PERIOD_MONTH,
            ])
            ->get();

        foreach ($customers as $customer) {
            try {
                $result = $this->generateForCustomer($customer);
                $stats['processed']++;
                if ($result === null) {
                    $stats['skipped']++;
                } else {
                    $stats['invoices_created']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error('InvoiceService error for customer ' . $customer->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $stats;
    }

    /**
     * Generate (or skip) an invoice for a single customer.
     * Returns the created CustomerInvoice or null if nothing to invoice / not the right day.
     */
    public function generateForCustomer(Customer $customer, bool $forceRun = false): ?CustomerInvoice
    {
        $today = now()->toDateString();
        $period = $customer->invoice_period;

        if (! $forceRun && ! $this->shouldRunToday($period)) {
            Log::info("InvoiceService: skipping customer {$customer->id} ({$period}) — not the right day");
            return null;
        }

        $windowStart = $this->getWindowStart($period);
        $candidates = $this->getBillableCandidates($customer->id, $windowStart);

        if ($candidates->isEmpty()) {
            Log::info("InvoiceService: no billable candidates for customer {$customer->id}");
            return null;
        }

        $candidateIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
        $totalAmount = $candidates->sum(fn ($c) => (float) $c->service_cost + (float) $c->travel_cost);
        $candidateCount = count($candidateIds);

        $invoice = DB::transaction(function () use ($customer, $period, $totalAmount, $candidateIds, $candidateCount, $today): CustomerInvoice {
            $invoice = CustomerInvoice::create([
                'customer_id' => $customer->id,
                'period' => $period,
                'invoice_amount' => round($totalAmount, 2),
                'status' => CustomerInvoice::STATUS_TO_BE_INVOICED,
                'candidate_ids' => $candidateIds,
                'due_date' => now()->addDays(30)->toDateString(),
                'created_date' => now(),
                'notes' => "Auto-generated invoice for {$customer->user?->name} ({$customer->company}). "
                    . "Period: {$period}. Candidates: {$candidateCount}. "
                    . "Total: {$totalAmount}.",
            ]);

            // Mark candidates as invoice-generated to avoid re-picking next cycle.
            Candidate::whereIn('id', $candidateIds)->update(['invoice_genrated' => 1]);

            // Update customers.last_invoice_sent
            $customer->update(['last_invoice_sent' => $today]);

            return $invoice;
        });

        Log::info("InvoiceService: created invoice #{$invoice->id} for customer {$customer->id} — {$candidateCount} candidates, amount {$totalAmount}");

        return $invoice;
    }

    // -------------------------------------------------------------------------
    // Calendar boundary logic
    // -------------------------------------------------------------------------

    /**
     * Should we run today for this period?
     *  daily  → always yes
     *  weekly → only on Monday
     *  monthly → only on the 1st
     */
    private function shouldRunToday(string $period): bool
    {
        return match ($period) {
            CustomerInvoice::PERIOD_DAY => true,
            CustomerInvoice::PERIOD_WEEK => now()->isMonday(),
            CustomerInvoice::PERIOD_MONTH => now()->day === 1,
            default => false,
        };
    }

    /**
     * The start of the window we should look at for billable candidates.
     */
    private function getWindowStart(string $period): string
    {
        return match ($period) {
            CustomerInvoice::PERIOD_DAY => now()->subDay()->toDateString(),
            CustomerInvoice::PERIOD_WEEK => now()->startOfWeek()->subWeek()->toDateString(),
            CustomerInvoice::PERIOD_MONTH => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            default => now()->subDays(30)->toDateString(),
        };
    }

    // -------------------------------------------------------------------------
    // Candidate eligibility
    // -------------------------------------------------------------------------

    /**
     * Find all candidates belonging to this customer that:
     *   1. Have reached a billable status (via status.variable)
     *   2. Have NOT yet been included in any previous invoice (invoice_genrated = 0)
     *   3. Have NOT been manually marked as invoice_sent = 1
     *   4. Have a terminal history entry after $windowStart
     *
     * For "canceled" status: only if ≥24 h after the booked entry (matches old system).
     */
    private function getBillableCandidates(int $customerId, string $windowStart): \Illuminate\Database\Eloquent\Collection
    {
        if (! Schema::hasTable('candidates') || ! Schema::hasTable('statuses')) {
            return collect();
        }

        // Get billable status IDs from the statuses table.
        $billableStatusIds = \App\Models\Status::whereIn('variable', self::BILLABLE_STATUS_VARIABLES)
            ->pluck('id')
            ->toArray();

        if (empty($billableStatusIds)) {
            // Fallback: use any non-pending, non-booked status
            $billableStatusIds = \App\Models\Status::whereNotIn('variable', ['pending', 'booked', 'rebooking'])
                ->pluck('id')
                ->toArray();
        }

        $query = Candidate::with('statusRelation')
            ->where('cus_id', $customerId)
            ->where('expired', 0)
            ->where('invoice_genrated', 0)
            ->where('invoice_sent', 0)
            ->whereIn('status', $billableStatusIds);

        // Only candidates whose status changed within the billing window.
        if (Schema::hasTable('history')) {
            $query->whereExists(function ($sub) use ($windowStart, $billableStatusIds) {
                $sub->select(DB::raw(1))
                    ->from('history')
                    ->whereColumn('history.order_id', 'candidates.id')
                    ->where('history.date_time', '>=', $windowStart);
            });
        }

        $candidates = $query->get();

        // For "canceled" status: filter out those canceled < 24 h after booking.
        $canceledStatusIds = \App\Models\Status::where('variable', 'canceled')->pluck('id')->toArray();

        if (! empty($canceledStatusIds) && Schema::hasTable('history')) {
            $candidates = $candidates->filter(function (Candidate $c) use ($canceledStatusIds): bool {
                if (! in_array($c->status, $canceledStatusIds, true)) {
                    return true; // Not a canceled candidate — keep it.
                }

                // Must have been booked ≥ 24h before the cancel history entry.
                $bookedAt = $this->getFirstHistoryDateByVariable($c->id, 'booked');
                $canceledAt = $this->getLastHistoryDateByVariable($c->id, 'canceled');

                if (! $bookedAt || ! $canceledAt) {
                    return false; // Can't determine — exclude.
                }

                return $bookedAt->diffInHours($canceledAt) >= 24;
            })->values();
        }

        return $candidates;
    }

    private function getFirstHistoryDateByVariable(int $candidateId, string $statusVariable): ?Carbon
    {
        $statusDetail = \App\Models\Status::where('variable', $statusVariable)->value('status_detail');
        if (! $statusDetail) {
            return null;
        }

        $row = DB::table('history')
            ->where('order_id', $candidateId)
            ->where('desc', $statusDetail)
            ->orderBy('date_time')
            ->first();

        return $row ? Carbon::parse($row->date_time) : null;
    }

    private function getLastHistoryDateByVariable(int $candidateId, string $statusVariable): ?Carbon
    {
        $statusDetail = \App\Models\Status::where('variable', $statusVariable)->value('status_detail');
        if (! $statusDetail) {
            return null;
        }

        $row = DB::table('history')
            ->where('order_id', $candidateId)
            ->where('desc', $statusDetail)
            ->orderByDesc('date_time')
            ->first();

        return $row ? Carbon::parse($row->date_time) : null;
    }
}
