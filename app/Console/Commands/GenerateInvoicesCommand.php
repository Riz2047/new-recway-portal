<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Invoice\InvoiceService;
use Illuminate\Console\Command;

/**
 * Artisan equivalent of the old cron_generate_invoices.php script.
 *
 * Schedule in routes/console.php (or Kernel.php) e.g.:
 *   $schedule->command('invoices:generate')->dailyAt('09:00');
 *
 * Usage:
 *   php artisan invoices:generate               # All customers, respects calendar boundary
 *   php artisan invoices:generate --force       # All customers regardless of day
 *   php artisan invoices:generate --customer=5  # One specific customer
 *   php artisan invoices:generate --customer=5 --force
 */
class GenerateInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate
        {--customer= : Only run for this customer ID}
        {--force     : Skip calendar-boundary check and run regardless of day}
        {--dry-run   : Show what would happen without writing to the database}';

    protected $description = 'Auto-generate customer invoices based on their invoice_period setting';

    public function handle(InvoiceService $service): int
    {
        $customerId = $this->option('customer');
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN mode — no records will be written.');
        }

        $this->info('Starting invoice generation — ' . now()->toDateTimeString());

        // Single-customer mode.
        if ($customerId) {
            $customer = Customer::with('user')->find($customerId);
            if (! $customer) {
                $this->error("Customer #{$customerId} not found.");
                return self::FAILURE;
            }

            if (! $customer->invoice_period) {
                $this->warn("Customer #{$customerId} has no invoice_period set — nothing to do.");
                return self::SUCCESS;
            }

            if ($dryRun) {
                $this->line("Would generate invoice for customer #{$customerId} ({$customer->invoice_period}).");
                return self::SUCCESS;
            }

            $invoice = $service->generateForCustomer($customer, $force);

            if ($invoice) {
                $this->info("✓ Invoice #{$invoice->id} created — customer #{$customerId}, amount {$invoice->invoice_amount}, candidates " . $invoice->getCandidateCount());
            } else {
                $this->line("→ No invoice created for customer #{$customerId} (no billable candidates or wrong day).");
            }

            return self::SUCCESS;
        }

        // All-customers mode.
        if ($dryRun) {
            $customers = Customer::whereNotNull('invoice_period')->count();
            $this->line("Would process {$customers} customer(s).");
            return self::SUCCESS;
        }

        if ($force) {
            $this->warn('--force flag set: ignoring calendar boundary for all customers.');
        }

        $stats = $service->generateAll();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Customers processed', $stats['processed']],
                ['Skipped (wrong day)', $stats['skipped']],
                ['Invoices created',    $stats['invoices_created']],
                ['Errors',              $stats['errors']],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->error('Some customers had errors — check laravel.log for details.');
            return self::FAILURE;
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
